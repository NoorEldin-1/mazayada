<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class AdminAuctionStoreTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->refs();
    }

    /**
     * Auction creation is centralised on the platform: a SUPER_ADMIN (no entity
     * binding) creates auctions and assigns the owning entity. Entity-bound
     * accounts are read-only and cannot reach store() — see MultiTenancyTest.
     */
    private function admin(): User
    {
        $user = User::create([
            'nin' => '109823041175663999',
            'first_name_ar' => 'مشرف', 'last_name_ar' => 'النظام',
            'phone' => '0555123999', 'email' => 'admin@example.test',
            'birth_date' => '1985-01-01', 'password' => 'StrongP@ss123',
            'role' => UserRole::SUPER_ADMIN, 'entity_id' => null,
            'kyc_status' => KycStatus::COMPLETE, 'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true, 'email_verified' => true,
        ]);
        $user->assignRole(UserRole::SUPER_ADMIN->value);

        return $user;
    }

    public function test_sale_auction_creates_with_empty_lease_fields(): void
    {
        $payload = [
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'wilaya_id' => $this->refWilaya->id,
            'title_ar' => 'سيارة تجريبية',
            'description_ar' => 'وصف تجريبي',
            'condition' => 'NEW',
            'auction_type' => 'SALE',
            'asset_class' => 'MOVABLE',
            'opening_price' => 10000,
            'deposit_amount' => 1000,
            'entry_fee' => 500,
            'book_price' => 0,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'max_extensions' => 10,
            // Lease fields intentionally empty (hidden for SALE) — must not break.
            'lease_duration_years' => '',
            'lease_renewals' => '',
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $payload)
            ->assertRedirect(route('admin.auctions.index'));

        $this->assertDatabaseHas('auctions', [
            'title_ar' => 'سيارة تجريبية',
            'auction_type' => 'SALE',
            'lease_renewals' => 2, // DB default applied, not null
        ]);
    }

    public function test_deposit_is_derived_from_percentage_and_entry_fee_zeroed(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'title_ar' => 'مزاد بنسبة تأمين',
                'opening_price' => 20000,   // dinars
                'deposit_percent' => 10,
                'book_price' => 200,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'مزاد بنسبة تأمين')->firstOrFail();
        // 20,000 DZD × 10% = 2,000 DZD = 200,000 centimes.
        $this->assertSame(200_000, (int) $auction->deposit_amount);
        $this->assertSame('10.00', (string) $auction->deposit_percent);
        $this->assertSame(0, (int) $auction->entry_fee);
        $this->assertSame(20_000, (int) $auction->book_price);
    }

    public function test_deposit_percent_defaults_to_ten_when_blank(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'title_ar' => 'مزاد بدون نسبة',
                'opening_price' => 5000,
                // deposit_percent intentionally omitted → defaults to 10%.
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'مزاد بدون نسبة')->firstOrFail();
        $this->assertSame(50_000, (int) $auction->deposit_amount); // 5,000 × 10% = 500 DZD
        $this->assertSame('10.00', (string) $auction->deposit_percent);
    }

    public function test_uploaded_photos_are_stored_and_linked(): void
    {
        Storage::fake('public');

        $payload = [
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'wilaya_id' => $this->refWilaya->id,
            'title_ar' => 'سيارة بصور',
            'description_ar' => 'وصف',
            'condition' => 'GOOD',
            'auction_type' => 'SALE',
            'opening_price' => 10000,
            'deposit_amount' => 1000,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'photos' => [
                UploadedFile::fake()->image('p1.jpg'),
                UploadedFile::fake()->image('p2.png'),
            ],
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'سيارة بصور')->firstOrFail();
        $this->assertCount(2, $auction->photosArray());
        foreach ($auction->photosArray() as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    /** Base SALE payload for the spec tests; overrides merge on top. */
    private function salePayload(array $overrides = []): array
    {
        return array_merge([
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'wilaya_id' => $this->refWilaya->id,
            'title_ar' => 'سيارة بمواصفات',
            'description_ar' => 'وصف',
            'condition' => 'NEW',
            'auction_type' => 'SALE',
            'opening_price' => 10000,
            'deposit_amount' => 1000,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ], $overrides);
    }

    public function test_specifications_are_stored_and_empty_rows_pruned(): void
    {
        $payload = $this->salePayload([
            'specifications' => [
                ['title_ar' => 'المحرك', 'title_fr' => 'Moteur', 'body_ar' => '2000 سي سي', 'body_fr' => '2000 cc'],
                ['title_ar' => '', 'title_fr' => '', 'body_ar' => '', 'body_fr' => ''], // fully empty → pruned
                ['title_ar' => 'اللون', 'body_ar' => 'أبيض'],
            ],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'سيارة بمواصفات')->firstOrFail();

        $this->assertCount(2, $auction->specifications); // empty middle row dropped
        $this->assertSame('المحرك', $auction->specifications[0]['title_ar']);
        $this->assertSame('اللون', $auction->specifications[1]['title_ar']);
        // The localized accessor falls back fr → ar when fr is absent.
        $this->assertSame('أبيض', $auction->localizedSpecifications()[1]['body']);
    }

    public function test_specification_row_without_title_fails_validation(): void
    {
        $payload = $this->salePayload([
            'specifications' => [
                ['title_ar' => '', 'body_ar' => 'وصف بدون عنوان'], // partial row → invalid
            ],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $payload)
            ->assertSessionHasErrors('specifications.0.title_ar');
    }

    public function test_auction_stores_null_specifications_when_none_submitted(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload(['title_ar' => 'بدون مواصفات']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'بدون مواصفات')->firstOrFail();
        $this->assertNull($auction->specifications);
        $this->assertSame([], $auction->localizedSpecifications());
    }

    public function test_create_page_renders_specifications_section(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.auctions.create'))
            ->assertOk()
            ->assertSee(__('admin.auctions.sec_specifications'))
            ->assertSee(__('admin.auctions.spec_add'))
            // The <template> clone row rendered with its placeholder index.
            ->assertSee('specifications[__INDEX__][title_ar]', false);
    }

    public function test_edit_page_prefills_existing_specifications(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.auctions.store'), $this->salePayload([
            'title_ar' => 'سيارة للتعديل',
            'specifications' => [['title_ar' => 'المحرك', 'body_ar' => '2000cc']],
        ]));
        $auction = \App\Models\Auction::where('title_ar', 'سيارة للتعديل')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.auctions.edit', $auction))
            ->assertOk()
            ->assertSee('المحرك'); // stored spec title pre-filled into the input
    }

    public function test_admin_show_page_displays_specifications(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.auctions.store'), $this->salePayload([
            'title_ar' => 'سيارة بمواصفات للعرض',
            'specifications' => [
                ['title_ar' => 'المحرك', 'title_fr' => 'Moteur', 'body_ar' => '2000 سي سي', 'body_fr' => '2000 cc'],
            ],
        ]));
        $auction = \App\Models\Auction::where('title_ar', 'سيارة بمواصفات للعرض')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.auctions.show', $auction))
            ->assertOk()
            ->assertSee(__('admin.auctions.sec_specifications'))
            ->assertSee('المحرك')  // Arabic title shown prominently
            ->assertSee('Moteur'); // French shown in the muted sub-line
    }

    public function test_lease_auction_defaults_renewals_when_blank(): void
    {
        $payload = [
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'wilaya_id' => $this->refWilaya->id,
            'title_ar' => 'محل للإيجار',
            'description_ar' => 'وصف',
            'condition' => 'GOOD',
            'auction_type' => 'LEASE',
            'opening_price' => 50000,
            'deposit_amount' => 5000,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'lease_duration_years' => '',
            'lease_renewals' => '',
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $payload)
            ->assertRedirect(route('admin.auctions.index'));

        $this->assertDatabaseHas('auctions', [
            'title_ar' => 'محل للإيجار',
            'lease_duration_years' => 3,
            'lease_renewals' => 2,
        ]);
    }

    public function test_map_coordinates_are_stored(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'title_ar' => 'أصل بإحداثيات',
                'asset_location' => 'بسكرة، الجزائر',
                'latitude' => '34.8500000',
                'longitude' => '5.7333000',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'أصل بإحداثيات')->firstOrFail();
        $this->assertEqualsWithDelta(34.85, (float) $auction->latitude, 0.0000001);
        $this->assertEqualsWithDelta(5.7333, (float) $auction->longitude, 0.0000001);
        $this->assertSame('بسكرة، الجزائر', $auction->asset_location);
    }

    public function test_typed_address_is_geocoded_when_no_pin_is_dropped(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '36.7100000', 'lon' => '3.0800000', 'display_name' => 'Chéraga'],
            ], 200),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'title_ar' => 'أصل بعنوان فقط',
                // A Google Plus Code + address pasted from Maps; no pin dropped.
                'asset_location' => 'QW55+CG7, 2 Rte de Bouchaoui, Chéraga, Algeria',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'أصل بعنوان فقط')->firstOrFail();
        $this->assertEqualsWithDelta(36.71, (float) $auction->latitude, 0.0001);
        $this->assertEqualsWithDelta(3.08, (float) $auction->longitude, 0.0001);
    }

    public function test_explicit_pin_is_not_overwritten_by_geocoding(): void
    {
        Http::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'title_ar' => 'أصل بدبوس',
                'asset_location' => 'Chéraga, Algeria',
                'latitude' => '34.8500000',
                'longitude' => '5.7333000',
            ]))
            ->assertSessionHasNoErrors();

        $auction = \App\Models\Auction::where('title_ar', 'أصل بدبوس')->firstOrFail();
        $this->assertEqualsWithDelta(34.85, (float) $auction->latitude, 0.0001);
        // Coordinates were supplied → no geocoding request is made.
        Http::assertNothingSent();
    }

    public function test_one_coordinate_without_the_other_fails_validation(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'longitude' => '5.5', // latitude omitted → both-or-neither rule trips
            ]))
            ->assertSessionHasErrors('latitude');
    }

    public function test_out_of_range_coordinate_fails_validation(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->salePayload([
                'latitude' => '120', // outside [-90, 90]
                'longitude' => '5',
            ]))
            ->assertSessionHasErrors('latitude');
    }
}
