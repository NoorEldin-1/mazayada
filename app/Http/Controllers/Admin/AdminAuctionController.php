<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuctionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\Wilaya;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAuctionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Auction::class);

        $query = Auction::with(['entity', 'category', 'wilaya']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('title_ar', 'LIKE', '%' . $request->search . '%');
        }

        $auctions = $query->latest()->paginate(15)->withQueryString();

        return view('admin.auctions.index', compact('auctions'));
    }

    public function create(): View
    {
        $this->authorize('create', Auction::class);

        $categories = Category::where('is_active', true)->get();
        $wilayas = Wilaya::orderBy('code')->get();
        $entities = Entity::where('is_active', true)->get();
        // A SUPER_ADMIN picks the entity (and the staff list is fetched live);
        // other staff are pinned to their own entity, so we pre-render its roster.
        $entityUsers = $this->staffForForm();

        return view('admin.auctions.create', compact('categories', 'wilayas', 'entities', 'entityUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Auction::class);

        // Only a SUPER_ADMIN chooses the owning entity. Entity staff always
        // create within their own entity — never trust a client-supplied value.
        $isSuperAdmin = auth()->user()->hasRole(UserRole::SUPER_ADMIN->value);

        $validated = $request->validate([
            'entity_id' => [$isSuperAdmin ? 'required' : 'nullable', 'exists:entities,id'],
            'entity_user_id' => ['nullable', 'exists:entity_users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_fr' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['required', 'string'],
            'description_fr' => ['nullable', 'string'],
            'condition' => ['required', 'string'],
            'auction_type' => ['required', 'string'],
            'opening_price' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'book_price' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'wilaya_id' => ['required', 'exists:wilayas,id'],
            // Commune must belong to the chosen wilaya; mayor name is free text.
            'commune_id' => ['nullable', Rule::exists('communes', 'id')->where('wilaya_id', $request->wilaya_id)],
            'mayor_name' => ['nullable', 'string', 'max:255'],
            'asset_location' => ['nullable', 'string', 'max:255'],
            // Asset photos (spec §4 step 1).
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:4096'],
            // A single short asset video — MP4, max 50 MB (duration is gated client-side).
            'video' => ['nullable', 'file', 'mimetypes:video/mp4', 'mimes:mp4', 'max:51200'],
            // Lifecycle fields (spec §2, §4).
            'asset_class' => ['nullable', 'string', 'in:MOVABLE,REAL_ESTATE,CUSTOMS'],
            'requires_commerce_register' => ['nullable', 'boolean'],
            'inspection_start' => ['nullable', 'date'],
            'inspection_end' => ['nullable', 'date', 'after_or_equal:inspection_start'],
            'inspection_location' => ['nullable', 'string', 'max:255'],
            'max_extensions' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'original_owner_nin' => ['nullable', 'digits:18'],
            'lease_duration_years' => ['nullable', 'integer', 'min:1', 'max:99'],
            'lease_renewals' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        // Convert DZD to centimes
        $validated['opening_price'] = (int) ($validated['opening_price'] * 100);
        $validated['deposit_amount'] = (int) ($validated['deposit_amount'] * 100);

        if (isset($validated['entry_fee'])) {
            $validated['entry_fee'] = (int) ($validated['entry_fee'] * 100);
        }

        if (isset($validated['book_price'])) {
            $validated['book_price'] = (int) ($validated['book_price'] * 100);
        }

        // entry_fee / book_price are NOT NULL — a blank field must become 0, not null.
        $validated['entry_fee'] = $validated['entry_fee'] ?? 0;
        $validated['book_price'] = $validated['book_price'] ?? 0;

        $validated['asset_class'] = $validated['asset_class'] ?? 'MOVABLE';
        $validated['requires_commerce_register'] = $request->boolean('requires_commerce_register');
        $validated['max_extensions'] = $validated['max_extensions'] ?? 10;
        $validated = $this->normalizeLeaseFields($validated);
        // §2.4 — assets above the threshold must also be announced in a national
        // newspaper (the platform announcement supplements, not replaces, it).
        $threshold = (int) setting('fees.newspaper_announcement_threshold_centimes',
            config('mazayada.fees.newspaper_announcement_threshold_centimes', 20_000_000));
        $validated['requires_newspaper_announcement'] = $validated['opening_price'] > $threshold;

        $validated['status'] = AuctionStatus::DRAFT;
        $validated['created_by'] = auth()->id();
        $validated['entity_id'] = $isSuperAdmin
            ? $validated['entity_id']
            : auth()->user()->entity_id;

        // A chosen staff member must belong to the auction's owning entity —
        // never trust a client-supplied id that points at another entity.
        $this->assertStaffBelongsToEntity($validated['entity_user_id'] ?? null, $validated['entity_id']);

        // Uploaded files are handled separately (storePhotos/storeVideo) — never mass-assign.
        unset($validated['photos'], $validated['video']);

        $auction = Auction::create($validated);

        if ($request->hasFile('photos')) {
            $auction->update(['photos' => $this->storePhotos($request, $auction)]);
        }

        if ($request->hasFile('video')) {
            $auction->update(['video' => $this->storeVideo($request, $auction)]);
        }

        AuditLog::log('AUCTION_CREATED', 'Auction', $auction->id, null, null, [
            'title' => $validated['title_ar'],
        ]);

        return redirect()->route('admin.auctions.index')
            ->with('success', __('admin.flash.auction_created'));
    }

    public function edit(Auction $auction): View
    {
        $this->authorize('update', $auction);

        $categories = Category::where('is_active', true)->get();
        $wilayas = Wilaya::orderBy('code')->get();
        $entities = Entity::where('is_active', true)->get();
        $entityUsers = $this->staffForForm();

        return view('admin.auctions.edit', compact('auction', 'categories', 'wilayas', 'entities', 'entityUsers'));
    }

    public function update(Request $request, Auction $auction): RedirectResponse
    {
        $this->authorize('update', $auction);

        if ($auction->status !== AuctionStatus::DRAFT) {
            return back()->withErrors(['status' => __('admin.flash.auction_edit_only_draft')]);
        }

        // Reassigning an auction to another entity is a SUPER_ADMIN-only action.
        $isSuperAdmin = auth()->user()->hasRole(UserRole::SUPER_ADMIN->value);

        $validated = $request->validate([
            'entity_id' => ['sometimes', 'exists:entities,id'],
            'entity_user_id' => ['nullable', 'exists:entity_users,id'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'title_fr' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['sometimes', 'string'],
            'description_fr' => ['nullable', 'string'],
            'condition' => ['sometimes', 'string'],
            'auction_type' => ['sometimes', 'string'],
            'opening_price' => ['sometimes', 'numeric', 'min:0'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'book_price' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'date'],
            'wilaya_id' => ['sometimes', 'exists:wilayas,id'],
            'commune_id' => ['nullable', Rule::exists('communes', 'id')->where('wilaya_id', $request->input('wilaya_id', $auction->wilaya_id))],
            'mayor_name' => ['nullable', 'string', 'max:255'],
            'asset_location' => ['nullable', 'string', 'max:255'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:4096'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4', 'mimes:mp4', 'max:51200'],
            'asset_class' => ['nullable', 'string', 'in:MOVABLE,REAL_ESTATE,CUSTOMS'],
            'requires_commerce_register' => ['nullable', 'boolean'],
            'inspection_start' => ['nullable', 'date'],
            'inspection_end' => ['nullable', 'date', 'after_or_equal:inspection_start'],
            'inspection_location' => ['nullable', 'string', 'max:255'],
            'max_extensions' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'original_owner_nin' => ['nullable', 'digits:18'],
            'lease_duration_years' => ['nullable', 'integer', 'min:1', 'max:99'],
            'lease_renewals' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        if (isset($validated['opening_price'])) {
            $validated['opening_price'] = (int) ($validated['opening_price'] * 100);
        }

        if (isset($validated['deposit_amount'])) {
            $validated['deposit_amount'] = (int) ($validated['deposit_amount'] * 100);
        }

        if (isset($validated['entry_fee'])) {
            $validated['entry_fee'] = (int) ($validated['entry_fee'] * 100);
        }

        if (isset($validated['book_price'])) {
            $validated['book_price'] = (int) ($validated['book_price'] * 100);
        }

        if ($request->has('requires_commerce_register')) {
            $validated['requires_commerce_register'] = $request->boolean('requires_commerce_register');
        }

        // NOT NULL columns: a submitted-but-blank value must not become null.
        if (array_key_exists('entry_fee', $validated) && $validated['entry_fee'] === null) {
            $validated['entry_fee'] = 0;
        }
        if (array_key_exists('book_price', $validated) && $validated['book_price'] === null) {
            $validated['book_price'] = 0;
        }
        if (array_key_exists('max_extensions', $validated) && $validated['max_extensions'] === null) {
            $validated['max_extensions'] = 10;
        }
        $validated = $this->normalizeLeaseFields($validated, $auction);

        // Recompute the newspaper-announcement flag against the (possibly new) price.
        $price = $validated['opening_price'] ?? $auction->opening_price;
        $threshold = (int) setting('fees.newspaper_announcement_threshold_centimes',
            config('mazayada.fees.newspaper_announcement_threshold_centimes', 20_000_000));
        $validated['requires_newspaper_announcement'] = $price > $threshold;

        if (! $isSuperAdmin) {
            unset($validated['entity_id']);
        }

        // A chosen staff member must belong to the auction's (possibly new) entity.
        if (array_key_exists('entity_user_id', $validated)) {
            $this->assertStaffBelongsToEntity(
                $validated['entity_user_id'],
                $validated['entity_id'] ?? $auction->entity_id,
            );
        }

        // Uploaded files are handled separately (storePhotos/storeVideo) — never mass-assign.
        unset($validated['photos'], $validated['video']);

        $auction->update($validated);

        // Newly uploaded photos are appended to any existing ones.
        if ($request->hasFile('photos')) {
            $existing = $auction->photos ? $auction->photos.';' : '';
            $auction->update(['photos' => $existing.$this->storePhotos($request, $auction)]);
        }

        // The asset video is a single file — a new upload replaces the old one.
        if ($request->hasFile('video')) {
            if ($auction->video) {
                Storage::disk('public')->delete($auction->video);
            }
            $auction->update(['video' => $this->storeVideo($request, $auction)]);
        }

        AuditLog::log('AUCTION_UPDATED', 'Auction', $auction->id);

        return redirect()->route('admin.auctions.index')
            ->with('success', __('admin.flash.auction_updated'));
    }

    public function destroy(Auction $auction): RedirectResponse
    {
        $this->authorize('delete', $auction);

        if ($auction->bids()->exists()) {
            return back()->withErrors(['delete' => __('admin.flash.auction_delete_has_bids')]);
        }

        $auctionId = $auction->id;
        $auction->delete();

        AuditLog::log('AUCTION_DELETED', 'Auction', $auctionId);

        return redirect()->route('admin.auctions.index')
            ->with('success', __('admin.flash.auction_deleted'));
    }

    public function publish(Auction $auction): RedirectResponse
    {
        $this->authorize('publish', $auction);

        if ($auction->status !== AuctionStatus::DRAFT) {
            return back()->withErrors(['status' => __('admin.flash.auction_publish_only_draft')]);
        }

        $auction->update(['status' => AuctionStatus::PUBLISHED]);

        AuditLog::log('AUCTION_PUBLISHED', 'Auction', $auction->id);

        return back()->with('success', __('admin.flash.auction_published'));
    }

    public function start(Auction $auction): RedirectResponse
    {
        $this->authorize('start', $auction);

        if ($auction->status !== AuctionStatus::PUBLISHED) {
            return back()->withErrors(['status' => __('admin.flash.auction_start_only_published')]);
        }

        $auction->update(['status' => AuctionStatus::ACTIVE]);

        AuditLog::log('AUCTION_STARTED', 'Auction', $auction->id);

        return back()->with('success', __('admin.flash.auction_started'));
    }

    public function extend(Auction $auction): RedirectResponse
    {
        $this->authorize('extend', $auction);

        if (! in_array($auction->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED], true)) {
            return back()->withErrors(['status' => __('admin.flash.auction_extend_only_active')]);
        }

        $minutes = (int) setting('bidding.extension_duration_minutes', 5);

        $auction->update([
            'status' => AuctionStatus::EXTENDED,
            'end_time' => $auction->end_time->copy()->addMinutes($minutes),
        ]);

        AuditLog::log('AUCTION_EXTENDED', 'Auction', $auction->id, null, null, [
            'minutes' => $minutes,
        ]);

        return back()->with('success', __('admin.flash.auction_extended'));
    }

    /**
     * Store uploaded asset photos on the PUBLIC disk (served via /storage) and
     * return a ';'-joined path string for the auctions.photos column.
     */
    private function storePhotos(Request $request, Auction $auction): string
    {
        $paths = [];
        foreach ($request->file('photos') as $file) {
            $paths[] = $file->store('auctions/'.$auction->id, 'public');
        }

        return implode(';', $paths);
    }

    /**
     * Store the single asset video on the PUBLIC disk and return its path for
     * the auctions.video column.
     */
    private function storeVideo(Request $request, Auction $auction): string
    {
        return $request->file('video')->store('auctions/'.$auction->id.'/video', 'public');
    }

    /**
     * Pre-rendered staff roster for the form. A SUPER_ADMIN picks the entity and
     * the list is fetched live (admin.entities.staff), so we return an empty set;
     * other staff are pinned to their own entity, so we hand back its roster.
     *
     * @return \Illuminate\Support\Collection<int, EntityUser>
     */
    private function staffForForm(): \Illuminate\Support\Collection
    {
        if (auth()->user()->hasRole(UserRole::SUPER_ADMIN->value)) {
            return collect();
        }

        return EntityUser::where('entity_id', auth()->user()->entity_id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Guard: a chosen entity staff member must belong to the auction's owning
     * entity. A null staff id (the field is optional) is always allowed.
     */
    private function assertStaffBelongsToEntity(?string $entityUserId, ?string $entityId): void
    {
        if (empty($entityUserId)) {
            return;
        }

        abort_unless(
            EntityUser::where('id', $entityUserId)->where('entity_id', $entityId)->exists(),
            422,
        );
    }

    /**
     * Lease columns only apply to LEASE auctions. For a SALE we drop them so the
     * DB defaults stand (lease_renewals is NOT NULL with a default); for a LEASE
     * we fill sensible defaults when the form left them empty.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeLeaseFields(array $validated, ?Auction $auction = null): array
    {
        $type = $validated['auction_type'] ?? $auction?->auction_type?->value;

        if ($type !== 'LEASE') {
            unset($validated['lease_duration_years'], $validated['lease_renewals']);

            return $validated;
        }

        if (empty($validated['lease_duration_years'])) {
            $validated['lease_duration_years'] = (int) setting('lease.default_duration_years',
                config('mazayada.lease.default_duration_years', 3));
        }
        if (! isset($validated['lease_renewals']) || $validated['lease_renewals'] === null) {
            $validated['lease_renewals'] = (int) setting('lease.max_renewals',
                config('mazayada.lease.max_renewals', 2));
        }

        return $validated;
    }

    /**
     * §4 step 2 — generate the signed condition book (cahier des charges) PDF
     * and notify watchers. Gated by documents.generate.
     */
    public function publishConditionBook(
        Auction $auction,
        \App\Services\DocumentService $documents,
        \App\Services\NotificationService $notifications,
    ): RedirectResponse {
        $this->authorize('documents.generate');

        $documents->generateConditionBook($auction);
        $notifications->conditionBookPublished($auction);

        AuditLog::log('CONDITION_BOOK_PUBLISHED', 'Auction', $auction->id);

        return back()->with('success', __('admin.flash.condition_book_published'));
    }

    public function cancel(Request $request, Auction $auction): RedirectResponse
    {
        $this->authorize('cancel', $auction);

        if (in_array($auction->status, [AuctionStatus::CLOSED, AuctionStatus::CANCELLED], true)) {
            return back()->withErrors(['status' => __('admin.flash.auction_cancel_invalid')]);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $auction->update(['status' => AuctionStatus::CANCELLED]);

        // Reason is kept in the immutable audit trail (no schema column needed).
        AuditLog::log('AUCTION_CANCELLED', 'Auction', $auction->id, null, null, [
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', __('admin.flash.auction_cancelled'));
    }
}
