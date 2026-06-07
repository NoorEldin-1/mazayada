<?php

namespace App\Models\Concerns;

use App\Models\Entity;
use App\Models\Scopes\EntityScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applied to models that carry a real `entity_id` column (currently Auction).
 * Registers the EntityScope so admin-dashboard queries are isolated per entity,
 * and exposes the owning-entity relationship.
 */
trait BelongsToEntity
{
    public static function bootBelongsToEntity(): void
    {
        static::addGlobalScope(new EntityScope());
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
