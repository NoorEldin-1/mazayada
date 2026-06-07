<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Entity-level data isolation for multi-tenant admin dashboards (spec Section 8).
 *
 * The rule, deliberately narrow so it never surprises the public site:
 *   - Only constrains queries while INSIDE the admin dashboard (admin.* routes).
 *   - SUPER_ADMIN and platform-wide accounts (entity_id === null) are never
 *     constrained — they see everything.
 *   - Guests, console commands and queued jobs have no authenticated user, so
 *     the scope is a no-op (auctions:activate / auctions:close / kyc:* keep
 *     seeing every row).
 *
 * Child resources (Payment, Document, Appeal, AuctionParticipant, Bid) inherit
 * the isolation transitively: admin controllers query them via
 * whereHas('auction', ...) and this scope filters that sub-query automatically.
 */
class EntityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user) {
            return; // guests + console/queue contexts
        }

        // Platform-wide accounts (citizens + SUPER_ADMIN carry a null entity_id).
        $entityId = $user->entity_id ?? null;
        if ($entityId === null) {
            return;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(UserRole::SUPER_ADMIN->value)) {
            return;
        }

        // Public browsing stays unrestricted; isolation applies only in /admin.
        if (! request()->routeIs('admin.*')) {
            return;
        }

        $builder->where($model->getTable().'.entity_id', $entityId);
    }
}
