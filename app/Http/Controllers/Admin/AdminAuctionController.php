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

    /**
     * Read-only full detail of one auction and everything happening in it —
     * bids, registered participants, payments, inspection Q&A, documents and
     * delivery. The route-model binding is entity-scoped, so an entity account
     * only ever reaches its own auctions; the 'view' policy enforces the same.
     */
    public function show(Auction $auction): View
    {
        $this->authorize('view', $auction);

        $auction->load([
            'entity', 'category', 'wilaya', 'commune',
            'winner', 'createdByUser', 'entityUser', 'delivery.user',
        ]);

        $bids = $auction->bids()->with('user')->orderByDesc('bid_time')->get();
        $participants = $auction->participants()->with('user')->orderByDesc('registered_at')->get();
        $payments = $auction->payments()->with('user')->latest()->get();
        $questions = $auction->inspectionQuestions()->with(['user', 'answeredBy'])->latest()->get();
        $documents = $auction->documents()->latest()->get();
        $appeals = $auction->appeals()->with('user')->latest()->get();

        return view('admin.auctions.show', compact(
            'auction', 'bids', 'participants', 'payments', 'questions', 'documents', 'appeals',
        ));
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

        // Drop fully-empty specification rows before validation so a trailing
        // blank row never trips the per-row required rules.
        $request->merge(['specifications' => $this->normalizeSpecifications($request)]);

        $validated = $request->validate([
            'entity_id' => [$isSuperAdmin ? 'required' : 'nullable', 'exists:entities,id'],
            'entity_user_id' => ['nullable', 'exists:entity_users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_fr' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['required', 'string'],
            'description_fr' => ['nullable', 'string'],
            // Admin-authored condition-book terms — rendered verbatim in the
            // generated كراسة الشروط PDF (ar primary, fr optional).
            'condition_terms_ar' => ['nullable', 'string', 'max:5000'],
            'condition_terms_fr' => ['nullable', 'string', 'max:5000'],
            // Admin-authored award-document clauses — rendered in the وثيقة الترسية
            // PDF produced at auction close (ar primary, fr optional).
            'award_terms_ar' => ['nullable', 'string', 'max:5000'],
            'award_terms_fr' => ['nullable', 'string', 'max:5000'],
            // Repeatable, admin-authored asset specifications (ar required per
            // row, fr optional). Empty rows are pruned above before validation.
            'specifications' => ['nullable', 'array', 'max:30'],
            'specifications.*.title_ar' => ['required', 'string', 'max:150'],
            'specifications.*.title_fr' => ['nullable', 'string', 'max:150'],
            'specifications.*.body_ar' => ['required', 'string', 'max:2000'],
            'specifications.*.body_fr' => ['nullable', 'string', 'max:2000'],
            'condition' => ['required', 'string'],
            'auction_type' => ['required', 'string'],
            'opening_price' => ['required', 'numeric', 'min:0'],
            // Participation deposit is a PERCENTAGE of the opening price (default
            // 10%); the centimes amount is derived, never entered directly.
            'deposit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'book_price' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'wilaya_id' => ['required', 'exists:wilayas,id'],
            // Commune must belong to the chosen wilaya; mayor name is free text.
            'commune_id' => ['nullable', Rule::exists('communes', 'id')->where('wilaya_id', $request->wilaya_id)],
            'mayor_name' => ['nullable', 'string', 'max:255'],
            'asset_location' => ['nullable', 'string', 'max:255'],
            // Map coordinates (Leaflet picker). Both-or-neither so a point is always complete.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
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

        if (isset($validated['book_price'])) {
            $validated['book_price'] = (int) ($validated['book_price'] * 100);
        }

        // The participation deposit is DERIVED from the opening price by a
        // per-auction percentage (default 10%) — never entered directly. The
        // legacy entry fee is removed from the flow (kept at 0).
        $validated['deposit_percent'] = $validated['deposit_percent'] ?? 10;
        $validated['deposit_amount'] = (int) round($validated['opening_price'] * $validated['deposit_percent'] / 100);
        $validated['entry_fee'] = 0;
        // book_price is NOT NULL — a blank field means a free book (0).
        $validated['book_price'] = $validated['book_price'] ?? 0;

        $validated['asset_class'] = $validated['asset_class'] ?? 'MOVABLE';
        $validated['requires_commerce_register'] = $request->boolean('requires_commerce_register');
        $validated['max_extensions'] = $validated['max_extensions'] ?? 10;
        // Geocode a typed address when no map pin was dropped, so the public map renders.
        $validated = $this->fillCoordinatesFromAddress($validated);
        // Store an empty spec list as null to keep the column clean.
        $validated['specifications'] = $validated['specifications'] ?: null;
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

        // The edit form re-renders every existing spec as a row, so the submit
        // is the full desired set — pruning empty rows lets a cleared list reach
        // the assignment below as [] and clear the column.
        $request->merge(['specifications' => $this->normalizeSpecifications($request)]);

        $validated = $request->validate([
            'entity_id' => ['sometimes', 'exists:entities,id'],
            'entity_user_id' => ['nullable', 'exists:entity_users,id'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'title_fr' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['sometimes', 'string'],
            'description_fr' => ['nullable', 'string'],
            'condition_terms_ar' => ['nullable', 'string', 'max:5000'],
            'condition_terms_fr' => ['nullable', 'string', 'max:5000'],
            'award_terms_ar' => ['nullable', 'string', 'max:5000'],
            'award_terms_fr' => ['nullable', 'string', 'max:5000'],
            'specifications' => ['nullable', 'array', 'max:30'],
            'specifications.*.title_ar' => ['required', 'string', 'max:150'],
            'specifications.*.title_fr' => ['nullable', 'string', 'max:150'],
            'specifications.*.body_ar' => ['required', 'string', 'max:2000'],
            'specifications.*.body_fr' => ['nullable', 'string', 'max:2000'],
            'condition' => ['sometimes', 'string'],
            'auction_type' => ['sometimes', 'string'],
            'opening_price' => ['sometimes', 'numeric', 'min:0'],
            'deposit_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'book_price' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'date'],
            'wilaya_id' => ['sometimes', 'exists:wilayas,id'],
            'commune_id' => ['nullable', Rule::exists('communes', 'id')->where('wilaya_id', $request->input('wilaya_id', $auction->wilaya_id))],
            'mayor_name' => ['nullable', 'string', 'max:255'],
            'asset_location' => ['nullable', 'string', 'max:255'],
            // Map coordinates (Leaflet picker). Both-or-neither so a point is always complete.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
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

        if (isset($validated['book_price'])) {
            $validated['book_price'] = (int) ($validated['book_price'] * 100);
        }

        if ($request->has('requires_commerce_register')) {
            $validated['requires_commerce_register'] = $request->boolean('requires_commerce_register');
        }

        // book_price is NOT NULL — a submitted-but-blank value means a free book.
        if (array_key_exists('book_price', $validated) && $validated['book_price'] === null) {
            $validated['book_price'] = 0;
        }
        if (array_key_exists('max_extensions', $validated) && $validated['max_extensions'] === null) {
            $validated['max_extensions'] = 10;
        }
        // The legacy entry fee is no longer charged — keep it zeroed.
        $validated['entry_fee'] = 0;
        // Always present (merged above); store an empty list as null.
        $validated['specifications'] = ($validated['specifications'] ?? null) ?: null;
        $validated = $this->normalizeLeaseFields($validated, $auction);

        $price = $validated['opening_price'] ?? $auction->opening_price;

        // Re-derive the participation deposit whenever the opening price or the
        // percentage changes (deposit_amount is never submitted directly).
        if (array_key_exists('opening_price', $validated) || array_key_exists('deposit_percent', $validated)) {
            $percent = $validated['deposit_percent'] ?? (float) $auction->deposit_percent;
            $validated['deposit_percent'] = $percent;
            $validated['deposit_amount'] = (int) round($price * $percent / 100);
        }

        // Recompute the newspaper-announcement flag against the (possibly new) price.
        $threshold = (int) setting('fees.newspaper_announcement_threshold_centimes',
            config('mazayada.fees.newspaper_announcement_threshold_centimes', 20_000_000));
        $validated['requires_newspaper_announcement'] = $price > $threshold;

        // Geocode a typed address when no map pin was dropped, so the public map renders.
        $validated = $this->fillCoordinatesFromAddress($validated);

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
     * Normalize the submitted specifications: trim every field, keep the four
     * known keys per row, and drop rows that are entirely empty. Rows with a
     * partial value (e.g. a body but no title) survive so validation can flag
     * the missing required field. Re-indexed to a clean 0..n list.
     *
     * @return array<int, array{title_ar: string, title_fr: string, body_ar: string, body_fr: string}>
     */
    private function normalizeSpecifications(Request $request): array
    {
        $rows = $request->input('specifications', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(fn ($row) => [
                'title_ar' => trim((string) (is_array($row) ? ($row['title_ar'] ?? '') : '')),
                'title_fr' => trim((string) (is_array($row) ? ($row['title_fr'] ?? '') : '')),
                'body_ar' => trim((string) (is_array($row) ? ($row['body_ar'] ?? '') : '')),
                'body_fr' => trim((string) (is_array($row) ? ($row['body_fr'] ?? '') : '')),
            ])
            ->reject(fn (array $row) => $row['title_ar'] === '' && $row['title_fr'] === ''
                && $row['body_ar'] === '' && $row['body_fr'] === '')
            ->values()
            ->all();
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
     * When the admin supplied an asset address but no map pin, best-effort
     * geocode the address (Nominatim) so the public location map still renders.
     * Coordinates already chosen via the picker are never overwritten.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function fillCoordinatesFromAddress(array $validated): array
    {
        $hasCoords = ! empty($validated['latitude'] ?? null) && ! empty($validated['longitude'] ?? null);
        $address = $validated['asset_location'] ?? null;

        if ($hasCoords || empty($address)) {
            return $validated;
        }

        if ($coords = app(\App\Services\GeocodingService::class)->geocode($address)) {
            [$validated['latitude'], $validated['longitude']] = $coords;
        }

        return $validated;
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
