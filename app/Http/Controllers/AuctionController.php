<?php

namespace App\Http\Controllers;

use App\Enums\AuctionStatus;
use App\Enums\DocumentType;
use App\Enums\InspectionQuestionStatus;
use App\Enums\PaymentStatus;
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
    /**
     * Public auction listing with an advanced, URL-driven filter sidebar.
     *
     * Every filter lives in the query string and the filter form carries NO `page`
     * field, so applying any filter resets to page 1 by construction while the
     * paginator links carry the active filters forward (withQueryString). That
     * pairing is what keeps pagination and filtering from ever contradicting.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $query = Auction::public()->with(['entity', 'category', 'wilaya', 'commune']);

        // Keyword — matches the title in any locale or the free-text asset location.
        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($w) use ($term): void {
                $w->where('title_ar', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_fr', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_en', 'LIKE', '%'.$term.'%')
                    ->orWhere('asset_location', 'LIKE', '%'.$term.'%');
            });
        }

        $query->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->category));
        $query->when($request->filled('wilaya'), fn ($q) => $q->where('wilaya_id', $request->wilaya));
        $query->when($request->filled('commune'), fn ($q) => $q->where('commune_id', $request->commune));
        $query->when($request->filled('type'), fn ($q) => $q->where('auction_type', $request->type));

        // Status — user-facing tokens (upcoming/live/closed) expand to enum values.
        if ($request->filled('status')) {
            $map = [
                'upcoming' => [AuctionStatus::PUBLISHED->value],
                'live' => [AuctionStatus::ACTIVE->value, AuctionStatus::EXTENDED->value],
                'closed' => [AuctionStatus::CLOSED->value],
            ];
            $values = collect((array) $request->status)
                ->flatMap(fn ($token) => $map[$token] ?? [])
                ->unique()
                ->all();
            // An unknown token set must yield nothing, not silently drop the filter.
            $query->whereIn('status', $values ?: ['__none__']);
        }

        // Multi-value asset filters.
        $query->when($request->filled('asset_class'), fn ($q) => $q->whereIn('asset_class', (array) $request->asset_class));
        $query->when($request->filled('condition'), fn ($q) => $q->whereIn('condition', (array) $request->condition));

        // Commercial-register requirement (explicit yes/no; blank = any). Checked
        // with has()+!== '' rather than filled() because "0" (not required) is a
        // meaningful value that filled() would wrongly treat as empty.
        if ($request->has('requires_cr') && $request->input('requires_cr') !== '') {
            $query->where('requires_commerce_register', $request->boolean('requires_cr'));
        }

        // Opening-price range — the sidebar speaks dinars; storage is centimes.
        $query->when($request->filled('price_min'), fn ($q) => $q->where('opening_price', '>=', (int) $request->price_min * 100));
        $query->when($request->filled('price_max'), fn ($q) => $q->where('opening_price', '<=', (int) $request->price_max * 100));

        // Sort.
        match ($request->input('sort')) {
            'price_asc' => $query->orderBy('opening_price'),
            'price_desc' => $query->orderByDesc('opening_price'),
            'most_bids' => $query->withCount(['bids as valid_bids_count' => fn ($b) => $b->where('is_valid', true)])->orderByDesc('valid_bids_count'),
            'ending_soon' => $query->orderByRaw('end_time IS NULL, end_time ASC'),
            default => $query->latest('start_time'),
        };

        $auctions = $query->paginate(5)->withQueryString();

        // A hand-edited page number past the end would render an empty grid —
        // bounce it back to the last real page (keeping the active filters).
        if ($auctions->currentPage() > $auctions->lastPage() && $auctions->lastPage() >= 1) {
            return redirect()->to($auctions->url($auctions->lastPage()));
        }

        $categories = \App\Models\Category::where('is_active', true)->get();
        $wilayas = \App\Models\Wilaya::all();

        // Pre-load the selected wilaya's communes so the cascading commune <select>
        // renders with its options (and preserves the chosen one) after a reload;
        // JS repopulates it on subsequent wilaya changes.
        $communes = $request->filled('wilaya')
            ? \App\Models\Commune::where('wilaya_id', $request->wilaya)->orderBy('code')->get()
            : collect();

        return view('auctions.index', compact('auctions', 'categories', 'wilayas', 'communes'));
    }

    /**
     * Live-search JSON for the sidebar's YouTube-style dropdown. Returns up to a
     * handful of public auctions matching the term by title (any locale) or asset
     * location; each row carries what the dropdown needs to render + a jump URL.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Auction::public()
            ->with(['category', 'wilaya'])
            ->where(function ($w) use ($term): void {
                $w->where('title_ar', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_fr', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_en', 'LIKE', '%'.$term.'%')
                    ->orWhere('asset_location', 'LIKE', '%'.$term.'%');
            })
            ->latest('start_time')
            ->limit(8)
            ->get()
            ->map(fn (Auction $a) => [
                'title' => $a->localizedTitle(),
                'url' => route('auctions.show', $a),
                'thumb' => $a->coverPhotoUrl(),
                // Isolated .money markup (same as <x-money>) so RTL never reorders
                // the digits/currency when the dropdown injects it. Rendered raw in
                // the browse JS — this is a web-only endpoint, not the mobile API.
                'price_html' => (string) dzd_html($a->currentPrice()),
                'wilaya' => $a->wilaya?->name,
                'category' => $a->category?->name,
                'live' => $a->isLive(),
                'status' => $a->status->label(),
            ]);

        return response()->json(['results' => $results]);
    }

    public function show(Auction $auction, AuctionService $auctions, PaymentService $payments): View
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

        // §4 step 7 — has the winner already completed the final payment? Drives
        // the pay-final CTA vs a "paid" confirmation on the closed panel.
        $finalPaymentConfirmed = auth()->check()
            && $auction->winner_user_id === auth()->id()
            && $payments->confirmedFinalPayment($auction, auth()->user());

        return view('auctions.show', compact(
            'auction', 'bids', 'participant', 'isParticipant',
            'questions', 'conditionBook', 'awardDocument', 'hasBookAccess',
            'canAppeal', 'userAppeal', 'finalPaymentConfirmed',
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

        // The ref may be the gateway's reference (mock/CIBWeb) or our own payment
        // id (Chargily browser return) — resolve either so the redirect lands on
        // the right auction.
        $payment = Payment::where('gateway_ref', $ref)->orWhere('id', $ref)->with('auction')->first();

        if ($ref) {
            $payments->handleCallback($ref, $decision);
        }

        $auction = $payment?->auction;
        $route = $auction ? redirect()->route('auctions.show', $auction) : redirect()->route('citizen.dashboard');

        // Report the ACTUAL outcome, not just the gateway's decision hint —
        // handleCallback re-verifies the order with the gateway before confirming.
        $confirmed = $payment && $payment->fresh()?->status === PaymentStatus::CONFIRMED;

        return $confirmed
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
