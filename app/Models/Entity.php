<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\EntityType;
use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entity extends Model
{
    // NOTE: the raw `name` column is an internal admin label (e.g. "DGD - الجمارك").
    // The localized `->name` accessor below returns the display name
    // (name_ar / name_fr) instead; the raw label is still reachable via
    // $entity->getRawOriginal('name') if ever needed.
    use HasLocalizedName, HasUuids;

    protected $fillable = [
        'name', 'name_ar', 'name_fr', 'type', 'wilaya_id',
        'commune_id', 'address', 'phone', 'email', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => EntityType::class,
            'is_active' => 'boolean',
        ];
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function entityUsers(): HasMany
    {
        return $this->hasMany(EntityUser::class);
    }

    /**
     * The entity's own institutional login account (read-only). Provisioned
     * alongside the entity; distinct from the individual staff in entityUsers().
     */
    public function account(): HasOne
    {
        return $this->hasOne(User::class)
            ->where('account_type', AccountType::INSTITUTION->value);
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }
}
