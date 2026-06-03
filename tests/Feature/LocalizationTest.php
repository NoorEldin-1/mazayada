<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Guardrail: every translation key must exist in EVERY supported locale.
 *
 * This is what makes "translate to all 3 languages" the default rather than an
 * afterthought — add a key to lang/ar/foo.php and forget lang/fr or lang/en,
 * and this test fails in CI with the exact list of what's missing.
 */
class LocalizationTest extends TestCase
{
    public function test_all_locales_have_identical_translation_keys(): void
    {
        $locales = (array) config('locales.supported');
        $this->assertNotEmpty($locales, 'No supported locales are configured in config/locales.php.');

        $keysByLocale = [];
        foreach ($locales as $locale) {
            $keysByLocale[$locale] = $this->collectKeys($locale);
        }

        // Reference set = the union of every key seen in any locale.
        $allKeys = collect($keysByLocale)->flatten()->unique()->sort()->values();

        $problems = [];
        foreach ($locales as $locale) {
            $missing = $allKeys->diff($keysByLocale[$locale])->values();
            if ($missing->isNotEmpty()) {
                $problems[] = "Locale [{$locale}] is missing {$missing->count()} key(s):\n  - "
                    .$missing->implode("\n  - ");
            }
        }

        $this->assertEmpty(
            $problems,
            "Translation key parity is broken across locales:\n\n".implode("\n\n", $problems)
        );
    }

    public function test_default_and_fallback_locales_are_supported(): void
    {
        $supported = (array) config('locales.supported');

        $this->assertContains(config('locales.default'), $supported);
        $this->assertContains(config('app.locale'), $supported);
        $this->assertContains(config('app.fallback_locale'), $supported);
    }

    /**
     * Collect every fully-qualified key ("group.key.subkey") for a locale by
     * scanning lang/<locale>/*.php and flattening each file with dot notation.
     */
    private function collectKeys(string $locale): Collection
    {
        $keys = collect();

        foreach (glob(lang_path($locale).'/*.php') as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $data = require $file;

            if (! is_array($data)) {
                continue;
            }

            foreach (Arr::dot($data) as $key => $value) {
                $keys->push("{$group}.{$key}");
            }
        }

        return $keys->sort()->values();
    }
}
