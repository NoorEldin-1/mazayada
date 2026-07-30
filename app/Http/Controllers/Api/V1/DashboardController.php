<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\PaymentType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\AuctionListResource;
use App\Http\Resources\Api\V1\MyAuctionResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Bid;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Dashboard
 *
 * The citizen's home summary and their participation history.
 */
class DashboardController extends ApiController
{
    /**
     * Dashboard summary
     *
     * Stat tiles, recent won auctions and the latest notifications.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $wonAuctions = $user->wonAuctions()
            ->with(['category', 'wilaya'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentNotifications = $user->userNotifications()
            ->latest('created_at')
            ->limit(5)
            ->get();

        return $this->ok([
            'stats' => [
                'active' => $user->participations()->whereHas('auction', fn ($q) => $q->active())->count(),
                'won' => $user->wonAuctions()->count(),
                'total_participations' => $user->participations()->count(),
                'pending_payments' => $user->payments()->where('status', \App\Enums\PaymentStatus::PENDING)->count(),
                'appeals_count' => $user->appeals()->count(),
                'has_pending_kyc' => $user->kyc_status === \App\Enums\KycStatus::UNDER_REVIEW,
                'has_pending_commercial_register' => $user->commercialRegister?->status === \App\Enums\CommercialRegisterStatus::PENDING,
                'upcoming_auctions' => $user->participations()->whereHas('auction', fn ($q) => $q->published())->count(),
            ],
            'kyc_status' => $user->kyc_status?->value,
            'commercial_register_status' => $user->commercialRegister?->status?->value,
            'won_auctions' => AuctionListResource::collection($wonAuctions)->resolve($request),
            'recent_notifications' => NotificationResource::collection($recentNotifications)->resolve($request),
        ]);
    }

    /**
     * My auctions
     *
     * The user's participations grouped by tab, each row carrying the viewer's own
     * state on that auction (highest bid, whether they lead it, deposit/book
     * flags, final-payment status) — everything the screen needs without a
     * follow-up request per row.
     *
     * Tabs are VIEWS over one set, not a partition: an auction can appear in more
     * than one (a won auction is also closed), and a participation whose auction
     * was later cancelled appears only under `all`. `all` is the exhaustive tab —
     * use it if you need every participation accounted for. Draft auctions are
     * never visible to citizens, so they cannot appear at all.
     *
     * @queryParam tab string One of all, active, won, lost, upcoming (default active). Example: active
     */
    public function myAuctions(Request $request): JsonResponse
    {
        $user = $request->user();

        $participations = $user->participations()
            ->with(['auction.category', 'auction.wilaya'])
            ->get()
            ->filter(fn ($p) => $p->auction !== null);

        $groups = [
            'all' => $participations,
            'active' => $participations->filter(fn ($p) => $p->auction->isLive()),
            'won' => $participations->filter(fn ($p) => $p->auction->winner_user_id === $user->id),
            'lost' => $participations->filter(
                fn ($p) => $p->auction->status === AuctionStatus::CLOSED && $p->auction->winner_user_id !== $user->id
            ),
            'upcoming' => $participations->filter(fn ($p) => $p->auction->status === AuctionStatus::PUBLISHED),
        ];

        $tab = in_array($request->query('tab'), array_keys($groups), true)
            ? $request->query('tab')
            : 'active';

        $selected = $groups[$tab]->values();
        $auctionIds = $selected->map(fn ($p) => $p->auction->id)->all();

        $myBids = $this->highestBidsFor($auctionIds, $user->id);
        $topBids = $this->highestBidsFor($auctionIds);
        $finalPayments = $this->finalPaymentStatuses($auctionIds, $user->id);

        $data = $selected->map(function ($participation) use ($user, $myBids, $topBids, $finalPayments) {
            $auction = $participation->auction;
            $mine = $myBids[$auction->id] ?? null;

            return (new MyAuctionResource($auction, [
                'deposit_paid' => (bool) $participation->deposit_paid,
                'book_purchased' => (bool) $participation->book_purchased,
                'registered_at' => $participation->registered_at?->toIso8601String(),
                'my_highest_bid' => $mine,
                // Closed: the declared winner. Still running: whoever currently
                // holds the top bid (ties can't happen — bids must strictly increase).
                'is_winning' => $auction->winner_user_id !== null
                    ? $auction->winner_user_id === $user->id
                    : ($mine !== null && $mine === ($topBids[$auction->id] ?? null)),
                'final_payment_status' => $finalPayments[$auction->id] ?? null,
            ]))->resolve(request());
        })->all();

        return $this->ok($data, null, [
            'tab' => $tab,
            'counts' => array_map(fn ($group) => $group->count(), $groups),
        ]);
    }

    /**
     * Highest VALID bid per auction, optionally for one bidder — one grouped
     * query instead of a lookup per row.
     *
     * @param  array<int, string>  $auctionIds
     * @return array<string, int>  auction id => amount in centimes
     */
    private function highestBidsFor(array $auctionIds, ?string $userId = null): array
    {
        if ($auctionIds === []) {
            return [];
        }

        return Bid::query()
            ->whereIn('auction_id', $auctionIds)
            ->where('is_valid', true)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->groupBy('auction_id')
            ->selectRaw('auction_id, MAX(amount) as amount')
            ->pluck('amount', 'auction_id')
            ->map(fn ($amount) => (int) $amount)
            ->all();
    }

    /**
     * The user's most recent final-payment status per auction (null where they
     * never started one).
     *
     * @param  array<int, string>  $auctionIds
     * @return array<string, string>  auction id => PaymentStatus value
     */
    private function finalPaymentStatuses(array $auctionIds, string $userId): array
    {
        if ($auctionIds === []) {
            return [];
        }

        return Payment::query()
            ->whereIn('auction_id', $auctionIds)
            ->where('user_id', $userId)
            ->where('payment_type', PaymentType::FINAL_PAYMENT)
            ->orderBy('created_at')
            ->get(['auction_id', 'status'])
            // Later rows overwrite earlier ones, leaving the latest attempt.
            ->reduce(function (array $carry, Payment $payment) {
                $carry[$payment->auction_id] = $payment->status?->value;

                return $carry;
            }, []);
    }
}
