<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    private function staff(): User
    {
        $user = User::create([
            'nin' => '109823041175663999',
            'first_name_ar' => 'موظف', 'last_name_ar' => 'جهة',
            'phone' => '0555123999', 'email' => 'staff@example.test',
            'birth_date' => '1985-01-01', 'password' => 'StrongP@ss123',
            'role' => UserRole::ENTITY_HEAD, 'entity_id' => $this->refEntity->id,
            'kyc_status' => KycStatus::COMPLETE, 'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true, 'email_verified' => true,
        ]);
        $user->assignRole(UserRole::ENTITY_HEAD->value);

        return $user;
    }

    public function test_sale_auction_creates_with_empty_lease_fields(): void
    {
        $payload = [
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

        $this->actingAs($this->staff())
            ->post(route('admin.auctions.store'), $payload)
            ->assertRedirect(route('admin.auctions.index'));

        $this->assertDatabaseHas('auctions', [
            'title_ar' => 'سيارة تجريبية',
            'auction_type' => 'SALE',
            'lease_renewals' => 2, // DB default applied, not null
        ]);
    }

    public function test_uploaded_photos_are_stored_and_linked(): void
    {
        Storage::fake('public');

        $payload = [
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

        $this->actingAs($this->staff())
            ->post(route('admin.auctions.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = \App\Models\Auction::where('title_ar', 'سيارة بصور')->firstOrFail();
        $this->assertCount(2, $auction->photosArray());
        foreach ($auction->photosArray() as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_lease_auction_defaults_renewals_when_blank(): void
    {
        $payload = [
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

        $this->actingAs($this->staff())
            ->post(route('admin.auctions.store'), $payload)
            ->assertRedirect(route('admin.auctions.index'));

        $this->assertDatabaseHas('auctions', [
            'title_ar' => 'محل للإيجار',
            'lease_duration_years' => 3,
            'lease_renewals' => 2,
        ]);
    }
}
