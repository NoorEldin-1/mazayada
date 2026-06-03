<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\App;

/**
 * Gives reference-data models (Category, Wilaya, Commune, Entity ...) a single
 * `name` accessor that returns the column matching the active locale, e.g.
 * `name_ar` / `name_fr` / `name_en`.
 *
 * Views should use `$model->name` instead of `$model->name_ar`, so the right
 * language shows automatically. Missing translations fall back gracefully:
 * active locale → ar → fr → en → first non-empty → ''.
 *
 * A model may override the resolution order via:
 *   protected array $localizedNameFallback = ['fr', 'ar', 'en'];
 */
trait HasLocalizedName
{
    public function getNameAttribute(): string
    {
        $order = array_merge(
            [App::getLocale()],
            property_exists($this, 'localizedNameFallback')
                ? $this->localizedNameFallback
                : [config('locales.default', 'ar'), 'ar', 'fr', 'en']
        );

        foreach ($order as $locale) {
            $value = $this->attributes["name_{$locale}"] ?? null;

            if (! empty($value)) {
                return $value;
            }
        }

        // Last resort: any populated name_* column on the row.
        foreach ($this->attributes as $key => $value) {
            if (str_starts_with($key, 'name_') && ! empty($value)) {
                return $value;
            }
        }

        return '';
    }
}
