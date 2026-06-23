<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort forward geocoding (free text address → coordinates) via Nominatim
 * (OpenStreetMap), biased to Algeria. Used as a fallback when an admin types an
 * asset address without dropping a pin on the map picker, so the public map
 * still renders. The picker remains the precise option.
 *
 * Nominatim usage policy: low-volume only, <=1 req/sec, a descriptive
 * User-Agent is required. For high-volume production geocoding, swap the
 * endpoint for a dedicated provider — only this class changes.
 */
class GeocodingService
{
    /**
     * Resolve an address to [latitude, longitude], or null on no match/failure.
     * A leading Google Plus Code (e.g. "QW55+CG7, …") is stripped so the
     * human-readable street part that follows can be matched.
     *
     * @return array{0: float, 1: float}|null
     */
    public function geocode(string $address): ?array
    {
        $query = $this->stripPlusCode(trim($address));

        if (mb_strlen($query) < 3) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'Mazayada').' auction platform',
            ])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'countrycodes' => 'dz',
                    'limit' => 1,
                    'accept-language' => app()->getLocale(),
                    'q' => $query,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $first = $response->json()[0] ?? null;
            if (! $first || ! isset($first['lat'], $first['lon'])) {
                return null;
            }

            return [(float) $first['lat'], (float) $first['lon']];
        } catch (\Throwable $e) {
            Log::warning('Geocoding failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Strip a leading Google Plus Code token (e.g. "QW55+CG7, ") that Nominatim
     * cannot resolve, leaving the address part. Falls back to the original
     * string when stripping would empty it.
     */
    private function stripPlusCode(string $value): string
    {
        $stripped = trim((string) preg_replace('/^\s*[A-Z0-9]{4,}\+[A-Z0-9]{2,}[\s,]*/i', '', $value));

        return $stripped !== '' ? $stripped : $value;
    }
}
