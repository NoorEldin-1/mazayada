<?php

namespace App\Policies;

use App\Enums\AppealStatus;
use App\Models\Appeal;
use App\Models\Auction;
use App\Models\Scopes\EntityScope;
use App\Models\User;

/**
 * Appeal authorization for the three-party workflow. SUPER_ADMIN bypasses the
 * whole policy via Gate::before, so the controllers/AppealService still guard
 * the state machine independently (a transition is rejected even for an admin).
 *
 * Two kinds of staff act on an appeal:
 *   - Platform admin (entity_id === null): forward / reject-at-intake / confirm.
 *   - Organising entity (entity_id !== null): the single decide() write.
 *
 * Appeals isolate per entity transitively through their auction, so a given
 * entity only ever sees/decides appeals filed against its own auctions.
 */
class AppealPolicy
{
    /**
     * The only abilities an entity-bound (otherwise read-only) account may
     * exercise: list its appeals, and decide one forwarded to it.
     */
    private const ENTITY_ABILITIES = ['viewAny', 'decide'];

    /**
     * Entity accounts are read-only everywhere except the two ENTITY_ABILITIES.
     * Platform staff (entity_id null) fall through; SUPER_ADMIN never reaches
     * here (short-circuited by Gate::before).
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
        return $user->can('appeals.viewAny');
    }

    /** Platform admin forwards a freshly-filed appeal to the organising entity. */
    public function forward(User $user, Appeal $appeal): bool
    {
        return $user->entity_id === null
            && $user->can('appeals.respond')
            && $appeal->status === AppealStatus::PENDING;
    }

    /** Platform admin rejects an obviously-invalid appeal without forwarding it. */
    public function rejectAtIntake(User $user, Appeal $appeal): bool
    {
        return $user->entity_id === null
            && $user->can('appeals.respond')
            && $appeal->status === AppealStatus::PENDING;
    }

    /** Platform admin confirms (or overrides) the entity's decision — terminal. */
    public function confirm(User $user, Appeal $appeal): bool
    {
        return $user->entity_id === null
            && $user->can('appeals.respond')
            && in_array($appeal->status, [AppealStatus::ENTITY_APPROVED, AppealStatus::ENTITY_REJECTED], true);
    }

    /**
     * The organising entity approves/rejects an appeal forwarded to it. Allowed
     * only for the entity that owns the appeal's auction, and only while the
     * appeal is actually awaiting that entity's decision.
     */
    public function decide(User $user, Appeal $appeal): bool
    {
        if ($user->entity_id === null || ! $user->can('appeals.decide')) {
            return false;
        }

        if ($appeal->status !== AppealStatus::FORWARDED_TO_ENTITY) {
            return false;
        }

        // Resolve the auction's entity ignoring the global scope, so the check is
        // explicit rather than relying on scope side effects.
        $entityId = Auction::withoutGlobalScope(EntityScope::class)
            ->whereKey($appeal->auction_id)
            ->value('entity_id');

        return $entityId === $user->entity_id;
    }
}
