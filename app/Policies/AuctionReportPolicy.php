<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\AuctionReport;
use App\Models\Scopes\EntityScope;
use App\Models\User;

/**
 * Authorization for auction reports (تقارير المزادات). SUPER_ADMIN bypasses the
 * whole policy via Gate::before.
 *
 * Two audiences, mirroring the appeals split:
 *   - Platform admin (entity_id === null): generate + refer + view any report.
 *   - Organising entity (entity_id !== null): read-only, and ONLY reports that
 *     have been referred to it AND belong to one of its own auctions.
 */
class AuctionReportPolicy
{
    /** The only abilities an entity-bound (read-only) account may exercise. */
    private const ENTITY_ABILITIES = ['viewAny', 'view'];

    /**
     * Entity accounts are read-only everywhere except ENTITY_ABILITIES. Platform
     * staff fall through; SUPER_ADMIN never reaches here (Gate::before).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->entity_id !== null && ! in_array($ability, self::ENTITY_ABILITIES, true)) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('auction-reports.viewAny');
    }

    /**
     * View a single report's PDF. Platform staff may view any; an entity account
     * may view only a report referred to it and belonging to its own auction.
     */
    public function view(User $user, AuctionReport $report): bool
    {
        if (! $user->can('auction-reports.view')) {
            return false;
        }

        if ($user->entity_id === null) {
            return true; // platform staff
        }

        // Entity account: report must be referred AND on one of its auctions.
        if (! $report->isReferred()) {
            return false;
        }

        $entityId = Auction::withoutGlobalScope(EntityScope::class)
            ->whereKey($report->auction_id)
            ->value('entity_id');

        return $entityId === $user->entity_id;
    }

    /** Issue a fresh report for an auction — platform staff only. */
    public function generate(User $user, Auction $auction): bool
    {
        return $user->entity_id === null && $user->can('auction-reports.generate');
    }

    /** Refer a report to its organising entity — platform staff only. */
    public function refer(User $user, AuctionReport $report): bool
    {
        return $user->entity_id === null && $user->can('auction-reports.refer');
    }
}
