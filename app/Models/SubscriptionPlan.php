<?php

namespace App\Models;

use App\Enums\SubscriptionPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A Premium plan (الباقة الشهرية / السنوية) — client edit 24. Price in centimes. */
class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code', 'name_ar', 'name_fr', 'name_en',
        'description_ar', 'description_fr', 'description_en',
        'period', 'price', 'features', 'is_recommended', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'period' => SubscriptionPeriod::class,
            'price' => 'integer',
            'features' => 'array',
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getNameAttribute(): string
    {
        return ($this->{'name_'.app()->getLocale()} ?? null) ?: (string) $this->name_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        return ($this->{'description_'.app()->getLocale()} ?? null) ?: $this->description_ar;
    }

    /**
     * Feature bullets for the active locale (falls back to Arabic), so adding a
     * feature needs no app release.
     *
     * @return array<int, string>
     */
    public function localizedFeatures(): array
    {
        $features = (array) ($this->features ?? []);

        return array_values(array_filter((array) (($features[app()->getLocale()] ?? null) ?: ($features['ar'] ?? []))));
    }
}
