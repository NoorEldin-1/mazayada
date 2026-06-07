<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Delivery;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff scheduling and completion of asset deliveries (spec §4 step 9). Scoped
 * to the staff member's entity via the auction's EntityScope.
 */
class AdminDeliveryController extends Controller
{
    public function index(): View
    {
        $this->authorize('deliveries.manage');

        // Closed auctions that have a winner — the ones needing delivery.
        $auctions = Auction::where('status', AuctionStatus::CLOSED)
            ->whereNotNull('winner_user_id')
            ->with(['delivery', 'winner', 'category'])
            ->latest('closed_at')
            ->paginate(20);

        return view('admin.deliveries.index', compact('auctions'));
    }

    public function store(Request $request, Auction $auction, DeliveryService $deliveries): RedirectResponse
    {
        $this->authorize('deliveries.manage');

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $auction->winner_user_id) {
            return back()->with('error', __('deliveries.no_winner'));
        }

        $deliveries->schedule($auction, $validated, auth()->id());

        return back()->with('success', __('deliveries.flash_scheduled'));
    }

    public function markDelivered(Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        $this->authorize('deliveries.manage');

        $deliveries->markDelivered($delivery);

        return back()->with('success', __('deliveries.flash_delivered'));
    }
}
