<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\EntityType;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\WilayaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entityA;
    private Entity $entityB;
    private Auction $auctionA;
    private Auction $auctionB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(WilayaSeeder::class);

        $category = Category::create(['name_ar' => 'مركبات', 'name_fr' => 'Véhicules', 'name_en' => 'Vehicles']);

        $this->entityA = $this->makeEntity('Entity A', 'الجهة أ');
        $this->entityB = $this->makeEntity('Entity B', 'الجهة ب');

        $this->auctionA = $this->makeAuction($this->entityA, $category->id, 'AUCTION-A-TITLE');
        $this->auctionB = $this->makeAuction($this->entityB, $category->id, 'AUCTION-B-TITLE');
    }

    public function test_entity_head_sees_only_their_entitys_auctions(): void
    {
        $head = $this->makeStaff(UserRole::ENTITY_HEAD, $this->entityA->id);

        $this->actingAs($head)
            ->get(route('admin.auctions.index'))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE')
            ->assertDontSee('AUCTION-B-TITLE');
    }

    public function test_entity_head_cannot_open_another_entitys_auction(): void
    {
        $head = $this->makeStaff(UserRole::ENTITY_HEAD, $this->entityA->id);

        // The EntityScope filters the route-model binding, so another entity's
        // auction simply does not exist for this user.
        $this->actingAs($head)
            ->get(route('admin.auctions.edit', $this->auctionB))
            ->assertNotFound();
    }

    public function test_super_admin_sees_all_entities_auctions(): void
    {
        $admin = $this->makeStaff(UserRole::SUPER_ADMIN, null);

        $this->actingAs($admin)
            ->get(route('admin.auctions.index'))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE')
            ->assertSee('AUCTION-B-TITLE');
    }

    public function test_appraiser_cannot_reach_auction_create(): void
    {
        $appraiser = $this->makeStaff(UserRole::APPRAISER, $this->entityA->id);

        $this->actingAs($appraiser)
            ->get(route('admin.auctions.create'))
            ->assertForbidden();
    }

    public function test_console_context_is_not_entity_scoped(): void
    {
        // No authenticated user (as in scheduled commands) → scope is a no-op,
        // so auctions:close / auctions:activate still see every auction.
        $this->assertSame(2, Auction::count());
    }

    private function makeEntity(string $name, string $nameAr): Entity
    {
        return Entity::create([
            'name' => $name,
            'name_ar' => $nameAr,
            'name_fr' => $name,
            'type' => EntityType::MUNICIPALITY,
            'wilaya_id' => 16,
            'is_active' => true,
        ]);
    }

    private function makeAuction(Entity $entity, int $categoryId, string $title): Auction
    {
        return Auction::create([
            'entity_id' => $entity->id,
            'category_id' => $categoryId,
            'title_ar' => $title,
            'opening_price' => 100000,
            'deposit_amount' => 10000,
            'entry_fee' => 5000,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(3),
            'status' => AuctionStatus::DRAFT,
            'auction_type' => AuctionType::SALE,
            'wilaya_id' => 16,
        ]);
    }

    private function makeStaff(UserRole $role, ?string $entityId): User
    {
        $user = User::create([
            'nin' => str_pad((string) random_int(0, 999999999999999999), 18, '1', STR_PAD_LEFT),
            'first_name_ar' => 'موظف',
            'last_name_ar' => 'تجربة',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'staff'.uniqid().'@mazayada.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => $role,
            'entity_id' => $entityId,
            'email_verified' => true,
        ]);

        $user->syncRoles([$role->value]);

        return $user;
    }
}
