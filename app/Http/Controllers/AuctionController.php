<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\InspectionQuestionStatus;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\InspectionQuestion;
use App\Models\Payment;
use App\Services\AuctionService;
use App\Services\BiddingService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AuctionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Auction::public()->with(['entity', 'category', 'wilaya']);

        if ($request->filled('q')) {
            $query->where('title_ar', 'LIKE', '%' . $request->q . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('wilaya')) {
            $query->where('wilaya_id', $request->wilaya);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('auction_type', $request->type);
        }

        $auctions = $query->latest('start_time')->paginate(12)->withQueryString();

        $categories = \App\Models\Category::where('is_active', true)->get();
        $wilayas = \App\Models\Wilaya::all();

        return view('auctions.index', compact('auctions', 'categories', 'wilayas'));
    }

    public function show(Auction $auction, AuctionService $auctions): View
    {
        // Lazy close-on-view: if the clock ran out but the auctions:close cron
        // hasn't run yet, finalise now so this visitor sees the canonical closed
        // panel (winner + final price) instead of a doomed-but-live bid form.
        // close() re-checks the status under a row lock, so a concurrent cron /
        // second visitor can't double-award. The scheduler remains the backstop.
        if ($auction->hasEnded()) {
            $auctions->close($auction);
        }

        $auction->load(['entity', 'category', 'wilaya', 'commune']);

        $bids = $auction->bids()
            ->where('is_valid', true)
            ->latest('bid_time')
            ->limit(10)
            ->get();

        $participant = null;
        if (auth()->check()) {
            $participant = AuctionParticipant::where('auction_id', $auction->id)
                ->where('user_id', auth()->id())
                ->first();
        }
        $isParticipant = $participant?->isFullyRegistered() ?? false;

        // Answered, public Q&A is shown to everyone (competition principle, §4 step 4).
        $questions = $auction->inspectionQuestions()
            ->where('is_public', true)
            ->where('status', InspectionQuestionStatus::ANSWERED)
            ->latest()
            ->get();

        // §4 step 2 — the condition book (a PAID download now; access is gated by
        // DocumentPolicy via hasBookAccess, so we don't filter on is_public here).
        // §4 step 6 — the award report, fetched only for the winner.
        $conditionBook = $auction->documents()
            ->where('type', DocumentType::CONDITION_BOOK)
            ->latest()
            ->first();

        $awardDocument = $auction->winner_user_id
            ? $auction->documents()->where('type', DocumentType::AWARD)->latest()->first()
            : null;

        // Whether the viewer may read the condition book (free book or purchased).
        // Drives the buy-vs-download UI and the registration gate.
        $hasBookAccess = auth()->check() && $auction->hasBookAccess(auth()->user());

        // § الطعون — appeal eligibility + this user's existing appeal (if any).
        // The tab renders when either is truthy: an eligible bidder may file one,
        // or a user who already filed can track its status.
        $canAppeal = auth()->check() && $auction->canBeAppealedBy(auth()->user());
        $userAppeal = auth()->check() ? $auction->appealBy(auth()->user()) : null;

        return view('auctions.show', compact(
            'auction', 'bids', 'participant', 'isParticipant',
            'questions', 'conditionBook', 'awardDocument', 'hasBookAccess',
            'canAppeal', 'userAppeal',
        ));
    }

    /**
     * §4 step 2 — begin a paid condition-book purchase through the gateway.
     * Buying the book unlocks its download and is a prerequisite for registering.
     */
    public function buyConditionBook(Auction $auction, PaymentService $payments): RedirectResponse
    {
        try {
            $result = $payments->initiateBookPurchase($auction, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * §4 step 3 — begin paid registration (the participation deposit) through the
     * payment gateway. The condition book must already be purchased.
     */
    public function startRegistration(Auction $auction, PaymentService $payments): RedirectResponse
    {
        try {
            $result = $payments->initiateRegistration($auction, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * §4 step 7 — begin the winner's final payment.
     */
    public function startFinalPayment(Auction $auction, PaymentService $payments): RedirectResponse
    {
        try {
            $result = $payments->initiateFinalPayment($auction, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Gateway return URL (mock + CIBWeb). Confirms/fails the payment set and
     * redirects back to the related auction.
     */
    public function paymentCallback(Request $request, PaymentService $payments): RedirectResponse
    {
        $ref = (string) $request->query('ref');
        $decision = (string) $request->query('decision', 'success');

        $payment = Payment::where('gateway_ref', $ref)->with('auction')->first();

        if ($ref) {
            $payments->handleCallback($ref, $decision);
        }

        $auction = $payment?->auction;
        $route = $auction ? redirect()->route('auctions.show', $auction) : redirect()->route('citizen.dashboard');

        return $decision === 'success'
            ? $route->with('success', __('payments.flash_confirmed'))
            : $route->with('error', __('payments.flash_failed'));
    }

    /**
     * §4 step 4 — a registered bidder asks a written question during inspection.
     */
    public function askQuestion(Request $request, Auction $auction): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        InspectionQuestion::create([
            'auction_id' => $auction->id,
            'user_id' => auth()->id(),
            'question' => $validated['question'],
            'status' => InspectionQuestionStatus::PENDING,
            'is_public' => true,
        ]);

        return back()->with('success', __('inspections.flash_asked'));
    }

    public function bid(Request $request, Auction $auction, BiddingService $bidding): RedirectResponse|JsonResponse
    {
        // The `amount` arrives in whole dinars — the unit shown across the UI
        // (the bid input speaks dinars like every <x-money> price). Money is
        // stored/broadcast in centimes, so convert at this boundary only;
        // BiddingService keeps receiving centimes.
        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $bid = $bidding->placeBid(
                $auction,
                auth()->user(),
                (int) $request->amount * 100,
                $request->ip(),
                $request->userAgent(),
            );
        } catch (RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'bid' => [
                    'amount' => $bid->amount,
                    'bid_time' => $bid->bid_time,
                    'alias' => $bid->bidderAlias(),
                ],
            ]);
        }

        return back()->with('success', __('auctions.flash_bid_placed'));
    }
}
