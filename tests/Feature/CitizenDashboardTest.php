<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AssetCondition;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Models\Wilaya;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the "Call to a member function count() on int" bug
 * caused by the controller sending $wonAuctions as an integer while the
 * view iterated it as a Collection.
 */
class CitizenDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_dashboard_renders_for_user_with_no_won_auctions(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('لم تفز بأي مزايدة بعد');
    }

    public function test_dashboard_renders_for_user_with_won_auctions(): void
    {
        $user = $this->makeUser();
        $auction = $this->makeAuction(['winner_user_id' => $user->id, 'final_price' => 5_000_000]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee($auction->title_ar)
            ->assertDontSee('لم تفز بأي مزايدة بعد');
    }

    private function makeUser(array $overrides = []): User
    {
        $defaults = [
            'nin' => '109823041175663812',
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'مستخدم',
            'phone' => '0555'.random_int(100000, 999999),
            'email' => 'dash'.uniqid().'@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ];

        $user = User::create(array_merge($defaults, $overrides));
        $user->assignRole(UserRole::CITIZEN->value);

        return $user;
    }

    private function makeAuction(array $overrides = []): Auction
    {
        $wilaya = Wilaya::firstOrCreate(['id' => 40], ['code' => '40', 'name_ar' => 'خنشلة', 'name_fr' => 'Khenchela']);

        $entity = Entity::firstOrCreate(
            ['name' => 'APC Khenchela'],
            ['name_ar' => 'بلدية خنشلة', 'name_fr' => 'APC Khenchela', 'type' => 'MUNICIPALITY', 'wilaya_id' => $wilaya->id]
        );

        $category = Category::firstOrCreate(
            ['name_ar' => 'مركبات'],
            ['name_fr' => 'Vehicules', 'name_en' => 'Vehicles']
        );

        return Auction::create(array_merge([
            'entity_id' => $entity->id,
            'category_id' => $category->id,
            'title_ar' => 'سيارة تويوتا 2018',
            'description_ar' => 'وصف',
            'condition' => AssetCondition::GOOD,
            'unit_count' => 1,
            'opening_price' => 1_000_000,
            'deposit_amount' => 100_000,
            'entry_fee' => 50_000,
            'book_price' => 300_000,
            'start_time' => now()->subDay(),
            'end_time' => now()->subHour(),
            'extension_trigger_seconds' => 30,
            'extension_duration_minutes' => 5,
            'status' => AuctionStatus::CLOSED,
            'auction_type' => AuctionType::SALE,
        ], $overrides));
    }
}
