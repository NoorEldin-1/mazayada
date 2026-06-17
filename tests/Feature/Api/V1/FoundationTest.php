<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the API foundation: routing, the response envelope, JSON error
 * rendering, and header-based locale resolution.
 */
class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_the_unified_envelope(): void
    {
        $this->getJson('/api/v1/ping')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['status', 'version', 'locale'],
                'message',
                'meta',
            ])
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.version', 'v1');
    }

    public function test_unsupported_locale_falls_back_to_arabic(): void
    {
        // An unsupported Accept-Language tag must fall back to the platform default.
        $this->getJson('/api/v1/ping', ['Accept-Language' => 'de-DE'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'ar');
    }

    public function test_query_param_overrides_accept_language(): void
    {
        $this->getJson('/api/v1/ping?lang=fr', ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'fr');
    }

    public function test_accept_language_header_switches_locale(): void
    {
        $this->getJson('/api/v1/ping', ['Accept-Language' => 'fr-FR,fr;q=0.9'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'fr');
    }

    public function test_x_locale_header_switches_locale(): void
    {
        $this->getJson('/api/v1/ping', ['X-Locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'en');
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
