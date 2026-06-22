<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

/**
 * Auction authorization. SUPER_ADMIN is short-circuited by the Gate::before
 * hook in AuthServiceProvider, so these methods only run for other staff roles.
 *
 * Every mutating ability also enforces same-entity ownership: an entity staff
 * member (entity_id set) may only act on their own entity's auctions. A
 * platform-wide staff account (entity_id === null) is not entity-restricted.
 */
class AuctionPolicy
{
    /** The only abilities an entity-bound (read-only) account may exercise. */
    private const READ_ABILITIES = ['viewAny', 'view'];

    /**
     * Hard product rule: any account bound to a government entity (entity_id set)
     * is read-only — all auction management is centralised on the platform. Deny
     * every mutating ability up front so no entity account can ever create, edit,
     * publish, start, extend, cancel, delete or appraise an auction. SUPER_ADMIN
     * never reaches here (allowed earlier by the Gate::before hook); platform
     * staff (entity_id === null) fall through to the per-ability methods below.
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
        return $user->can('auctions.viewAny');
    }

    public function view(User $user, Auction $auction): bool
    {
        return $user->can('auctions.view') && $this->sameEntity($user, $auction);
    }

    public function create(User $user): bool
    {
        return $user->can('auctions.create');
    }

    public function update(User $user, Auction $auction): bool
    {
        return $user->can('auctions.update') && $this->sameEntity($user, $auction);
    }

    public function delete(User $user, Auction $auction): bool
    {
        return $user->can('auctions.delete') && $this->sameEntity($user, $auction);
    }

    public function publish(User $user, Auction $auction): bool
    {
        return $user->can('auctions.publish') && $this->sameEntity($user, $auction);
    }

    /** Starting a published auction is the same authority as publishing it. */
    public function start(User $user, Auction $auction): bool
    {
        return $this->publish($user, $auction);
    }

    public function extend(User $user, Auction $auction): bool
    {
        return $user->can('auctions.extend') && $this->sameEntity($user, $auction);
    }

    public function cancel(User $user, Auction $auction): bool
    {
        return $user->can('auctions.cancel') && $this->sameEntity($user, $auction);
    }

    public function appraise(User $user, Auction $auction): bool
    {
        return $user->can('auctions.appraise') && $this->sameEntity($user, $auction);
    }

    protected function sameEntity(User $user, Auction $auction): bool
    {
        return $user->entity_id === null || $user->entity_id === $auction->entity_id;
    }
}
