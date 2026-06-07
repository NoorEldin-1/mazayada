<?php

namespace App\Policies;

use App\Models\Appeal;
use App\Models\Auction;
use App\Models\Scopes\EntityScope;
use App\Models\User;

/**
 * Appeal authorization. SUPER_ADMIN bypasses via Gate::before.
 *
 * Appeals isolate per entity transitively through their auction: a staff member
 * may only respond to appeals filed against their own entity's auctions.
 */
class AppealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('appeals.viewAny');
    }

    public function respond(User $user, Appeal $appeal): bool
    {
        if (! $user->can('appeals.respond')) {
            return false;
        }

        if ($user->entity_id === null) {
            return true;
        }

        // Resolve the auction's entity ignoring the global scope, so the check
        // is explicit rather than relying on scope side effects.
        $entityId = Auction::withoutGlobalScope(EntityScope::class)
            ->whereKey($appeal->auction_id)
            ->value('entity_id');

        return $entityId === $user->entity_id;
    }
}
