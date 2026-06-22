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
    /** The only abilities an entity-bound (read-only) account may exercise. */
    private const READ_ABILITIES = ['viewAny'];

    /**
     * Hard product rule: any account bound to a government entity is read-only.
     * It may list/view the appeals filed against its own auctions but never
     * respond — appeal handling is centralised on the platform. SUPER_ADMIN is
     * short-circuited earlier by Gate::before; platform staff (entity_id null)
     * fall through to respond().
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->entity_id !== null && ! in_array($ability, self::READ_ABILITIES, true)) {
            return false;
        }

        return null;
    }

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
