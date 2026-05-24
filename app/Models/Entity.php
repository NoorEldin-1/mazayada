<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    use HasUuids;

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

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }
}
