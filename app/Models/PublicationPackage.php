<?php

namespace App\Models;

use App\Enums\PublicationArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin-managed publication package (باقة النشر): a display space on the
 * platform with its price for a normal publication and the surcharge for a
 * priority one (client edits 14-16). Money in centimes.
 */
class PublicationPackage extends Model
{
    protected $fillable = [
        'code', 'name_ar', 'name_fr', 'name_en',
        'description_ar', 'description_fr', 'description_en',
        'display_area', 'price', 'priority_price', 'duration_days',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'display_area' => PublicationArea::class,
            'price' => 'integer',
            'priority_price' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }

    /** Localized name for the active locale, falling back to Arabic. */
    public function getNameAttribute(): string
    {
        return ($this->{'name_'.app()->getLocale()} ?? null) ?: (string) $this->name_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        return ($this->{'description_'.app()->getLocale()} ?? null) ?: $this->description_ar;
    }
}
