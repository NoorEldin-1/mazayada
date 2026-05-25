<?php

namespace Tests\Feature;

use App\Models\Commune;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GeoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_wilayas_endpoint_returns_data(): void
    {
        Wilaya::create(['id' => 40, 'code' => '40', 'name_ar' => 'خنشلة', 'name_fr' => 'Khenchela']);

        $response = $this->getJson('/api/v1/wilayas');

        $response->assertOk()
            ->assertJsonStructure([['id', 'code', 'name_ar', 'name_fr']])
            ->assertJsonFragment(['code' => '40']);
    }

    public function test_wilayas_endpoint_is_cached(): void
    {
        Cache::forget('geo:wilayas');
        Wilaya::create(['id' => 16, 'code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger']);

        $this->getJson('/api/v1/wilayas')->assertOk();

        $this->assertTrue(Cache::has('geo:wilayas'), 'wilayas response should be cached after first request');
    }

    public function test_communes_endpoint_returns_communes_for_wilaya(): void
    {
        $wilaya = Wilaya::create(['id' => 40, 'code' => '40', 'name_ar' => 'خنشلة', 'name_fr' => 'Khenchela']);
        Commune::create([
            'wilaya_id' => $wilaya->id,
            'code' => '4001',
            'name_ar' => 'خنشلة',
            'name_fr' => 'Khenchela',
            'postal_code' => '40000',
        ]);

        $response = $this->getJson("/api/v1/wilayas/{$wilaya->id}/communes");

        $response->assertOk()
            ->assertJsonStructure([['id', 'code', 'name_ar', 'name_fr', 'postal_code']])
            ->assertJsonFragment(['postal_code' => '40000']);
    }
}
