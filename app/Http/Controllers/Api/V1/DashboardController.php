<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\AuctionListResource;
use App\Http\Resources\Api\V1\NotificationResource;
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
     * The user's participations grouped by tab (active | won | lost | upcoming).
     *
     * @queryParam tab string One of active, won, lost, upcoming (default active). Example: active
     */
    public function myAuctions(Request $request): JsonResponse
    {
        $user = $request->user();

        $participations = $user->participations()
            ->with(['auction.category', 'auction.wilaya'])
            ->get()
            ->filter(fn ($p) => $p->auction !== null);

        $groups = [
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

        $auctions = $groups[$tab]->map(fn ($p) => $p->auction)->values();

        return $this->ok(
            AuctionListResource::collection($auctions)->resolve($request),
            null,
            [
                'tab' => $tab,
                'counts' => array_map(fn ($group) => $group->count(), $groups),
            ],
        );
    }
}
