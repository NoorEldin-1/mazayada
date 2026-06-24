<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuctionAppealRequest;
use App\Models\Auction;
use App\Services\AppealService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppealController extends Controller
{
    /**
     * The citizen's appeal-tracking page. Appeals are now filed from the auction
     * page itself (§ الطعون tab), so this view is read-only history.
     */
    public function index(): View
    {
        $appeals = auth()->user()->appeals()
            ->with('auction')
            ->latest()
            ->paginate(15);

        return view('citizen.appeals', compact('appeals'));
    }

    /**
     * File an appeal against a specific auction's result. Eligibility (closed +
     * within window + participant + valid bid) and the one-per-auction rule are
     * enforced by the request and the AppealService.
     */
    public function store(StoreAuctionAppealRequest $request, Auction $auction, AppealService $appeals): RedirectResponse
    {
        try {
            $appeals->submit(
                $request->user(),
                $auction,
                $request->validated('subject'),
                $request->validated('reason'),
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('appeals.flash_submitted'));
    }
}
