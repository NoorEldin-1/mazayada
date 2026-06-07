<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuctionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Wilaya;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.auctions.create', compact('categories', 'wilayas', 'entities'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Auction::class);

        // Only a SUPER_ADMIN chooses the owning entity. Entity staff always
        // create within their own entity — never trust a client-supplied value.
        $isSuperAdmin = auth()->user()->hasRole(UserRole::SUPER_ADMIN->value);

        $validated = $request->validate([
            'entity_id' => [$isSuperAdmin ? 'required' : 'nullable', 'exists:entities,id'],
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
            'asset_location' => ['nullable', 'string', 'max:255'],
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

        $validated['status'] = AuctionStatus::DRAFT;
        $validated['created_by'] = auth()->id();
        $validated['entity_id'] = $isSuperAdmin
            ? $validated['entity_id']
            : auth()->user()->entity_id;

        $auction = Auction::create($validated);

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

        return view('admin.auctions.edit', compact('auction', 'categories', 'wilayas', 'entities'));
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
            'asset_location' => ['nullable', 'string', 'max:255'],
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

        if (! $isSuperAdmin) {
            unset($validated['entity_id']);
        }

        $auction->update($validated);

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
