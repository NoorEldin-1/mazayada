<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\AppealStatus;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\EntityType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Appeal;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\WilayaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Read-only entity accounts: the institutional entity login and its staff
 * (UserRole::ENTITY_VIEWER) may only view their own entity's auctions and
 * appeals. All mutating actions are denied; auction management is centralised.
 */
class EntityReadOnlyTest extends TestCase
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

        $this->auctionA = $this->makeAuction($this->entityA->id, $category->id, 'AUCTION-A-TITLE');
        $this->auctionB = $this->makeAuction($this->entityB->id, $category->id, 'AUCTION-B-TITLE');
    }

    public function test_viewer_can_open_their_own_auction_detail(): void
    {
        $this->actingAs($this->viewer($this->entityA->id))
            ->get(route('admin.auctions.show', $this->auctionA))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE');
    }

    public function test_viewer_cannot_open_another_entitys_auction_detail(): void
    {
        // EntityScope filters the route-model binding — the other entity's
        // auction simply does not exist for this account.
        $this->actingAs($this->viewer($this->entityA->id))
            ->get(route('admin.auctions.show', $this->auctionB))
            ->assertNotFound();
    }

    public function test_viewer_cannot_reach_auction_create(): void
    {
        $this->actingAs($this->viewer($this->entityA->id))
            ->get(route('admin.auctions.create'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_edit_or_publish_own_auction(): void
    {
        $viewer = $this->viewer($this->entityA->id);

        $this->actingAs($viewer)
            ->get(route('admin.auctions.edit', $this->auctionA))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('admin.auctions.publish', $this->auctionA))
            ->assertForbidden();
    }

    public function test_viewer_cannot_store_an_auction(): void
    {
        $this->actingAs($this->viewer($this->entityA->id))
            ->post(route('admin.auctions.store'), [
                'entity_id' => $this->entityA->id,
                'category_id' => $this->auctionA->category_id,
                'wilaya_id' => 16,
                'title_ar' => 'محاولة',
                'description_ar' => 'وصف',
                'condition' => 'GOOD',
                'auction_type' => 'SALE',
                'opening_price' => 1000,
                'deposit_amount' => 100,
                'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
                'end_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }

    public function test_viewer_is_scoped_to_their_own_auctions_in_the_list(): void
    {
        $this->actingAs($this->viewer($this->entityA->id))
            ->get(route('admin.auctions.index'))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE')
            ->assertDontSee('AUCTION-B-TITLE');
    }

    public function test_viewer_is_redirected_off_the_global_dashboard(): void
    {
        // The dashboard surfaces platform-wide user/KYC figures — not theirs to
        // see — so an entity account is bounced to its own auctions.
        $this->actingAs($this->viewer($this->entityA->id))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.auctions.index'));
    }

    public function test_viewer_cannot_reach_entity_or_staff_management(): void
    {
        $viewer = $this->viewer($this->entityA->id);

        // "Auctions and appeals only" — entity/staff/user management is denied.
        $this->actingAs($viewer)->get(route('admin.entities.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.entity-staff.index'))->assertForbidden();
    }

    public function test_viewer_can_list_appeals(): void
    {
        $this->actingAs($this->viewer($this->entityA->id))
            ->get(route('admin.appeals.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_forward_or_confirm_appeals(): void
    {
        // Forwarding and confirming are the PLATFORM admin's actions — denied to
        // an entity account even on its own auction.
        $viewer = $this->viewer($this->entityA->id);

        $pending = $this->makeAppeal($this->auctionA, AppealStatus::PENDING);
        $this->actingAs($viewer)
            ->post(route('admin.appeals.forward', $pending))
            ->assertForbidden();

        $entityApproved = $this->makeAppeal($this->auctionA, AppealStatus::ENTITY_APPROVED, [
            'entity_decision' => AppealStatus::APPROVED,
        ]);
        $this->actingAs($viewer)
            ->post(route('admin.appeals.confirm', $entityApproved), [
                'decision' => 'APPROVED', 'admin_response' => 'رد',
            ])
            ->assertForbidden();
    }

    public function test_viewer_can_decide_a_forwarded_appeal_for_its_own_auction(): void
    {
        // The single deliberate write exception: an appeal the platform forwarded
        // to this entity may be approved/rejected.
        $viewer = $this->viewer($this->entityA->id);
        $appeal = $this->makeAppeal($this->auctionA, AppealStatus::FORWARDED_TO_ENTITY);

        $this->actingAs($viewer)
            ->post(route('admin.appeals.decide', $appeal), [
                'decision' => 'APPROVED',
                'entity_response' => 'قرار الجهة',
            ])
            ->assertRedirect();

        $appeal->refresh();
        $this->assertSame(AppealStatus::ENTITY_APPROVED, $appeal->status);
        $this->assertSame(AppealStatus::APPROVED, $appeal->entity_decision);
    }

    public function test_viewer_cannot_decide_a_not_yet_forwarded_appeal(): void
    {
        $viewer = $this->viewer($this->entityA->id);
        $pending = $this->makeAppeal($this->auctionA, AppealStatus::PENDING);

        $this->actingAs($viewer)
            ->post(route('admin.appeals.decide', $pending), [
                'decision' => 'APPROVED', 'entity_response' => 'قرار',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_decide_another_entitys_appeal(): void
    {
        $viewer = $this->viewer($this->entityA->id);
        $foreign = $this->makeAppeal($this->auctionB, AppealStatus::FORWARDED_TO_ENTITY);

        $this->actingAs($viewer)
            ->post(route('admin.appeals.decide', $foreign), [
                'decision' => 'APPROVED', 'entity_response' => 'قرار',
            ])
            ->assertForbidden();
    }

    public function test_institutional_account_logs_in_and_lands_on_its_auctions(): void
    {
        $institution = User::create([
            'account_type' => AccountType::INSTITUTION,
            'role' => UserRole::ENTITY_VIEWER,
            'entity_id' => $this->entityA->id,
            'email' => 'institution@mazayada.test',
            'password' => 'StrongP@ss123',
            'first_name_ar' => 'الجهة أ',
            'last_name_ar' => '',
            'kyc_status' => KycStatus::COMPLETE,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified' => true,
        ]);
        $institution->assignRole(UserRole::ENTITY_VIEWER->value);

        $this->post('/login', [
            'nin_or_email' => 'institution@mazayada.test',
            'password' => 'StrongP@ss123',
        ])->assertRedirect(route('admin.auctions.index'));

        $this->assertAuthenticatedAs($institution);
    }

    public function test_admin_creates_a_read_only_staff_account_with_login(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.entity-staff.store'), [
                'entity_id' => $this->entityA->id,
                'nin' => '109853041175663832',
                'professional_id_no' => '109823041175663832',
                'first_name_ar' => 'جاك',
                'last_name_ar' => 'سويم',
                'phone' => '0512345678',
                'email' => 'staffmember@mazayada.test',
                'username' => 'staff_member_1',
                'birth_date' => '2000-01-31',
                'password' => 'StrongP@ss123',
                'password_confirmation' => 'StrongP@ss123',
            ])
            ->assertRedirect(route('admin.entity-staff.index'));

        // The login account is a read-only viewer bound to the entity...
        $this->assertDatabaseHas('users', [
            'email' => 'staffmember@mazayada.test',
            'role' => UserRole::ENTITY_VIEWER->value,
            'entity_id' => $this->entityA->id,
        ]);
        // ...and the management mirror row is created (with its legacy password).
        $this->assertDatabaseHas('entity_users', [
            'username' => 'staff_member_1',
            'role' => UserRole::ENTITY_VIEWER->value,
        ]);
    }

    public function test_admin_can_open_an_entity_detail_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.entities.show', $this->entityA))
            ->assertOk()
            ->assertSee($this->entityA->name_ar);
    }

    public function test_admin_can_open_a_staff_members_detail_page(): void
    {
        $staffUser = $this->viewer($this->entityA->id);
        $member = EntityUser::create([
            'entity_id' => $this->entityA->id,
            'user_id' => $staffUser->id,
            'username' => 'staff_detail_user',
            'password' => 'StrongP@ss123',
            'full_name' => 'Detail User',
            'role' => UserRole::ENTITY_VIEWER->value,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.entity-staff.show', $member))
            ->assertOk()
            ->assertSee('staff_detail_user');
    }

    public function test_reseeding_roles_downgrades_legacy_entity_staff_to_viewer(): void
    {
        // A legacy entity-bound staff member created with a write role...
        $legacy = User::create([
            'nin' => $this->randomNin(),
            'first_name_ar' => 'مسؤول',
            'last_name_ar' => 'قديم',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'legacy'.Str::random(8).'@mazayada.test',
            'birth_date' => '1985-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::ENTITY_HEAD,
            'entity_id' => $this->entityA->id,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified' => true,
        ]);
        $legacy->assignRole(UserRole::ENTITY_HEAD->value);

        // ...is downgraded to read-only when roles are (re)synced on deploy.
        $this->seed(RolesPermissionsSeeder::class);

        $legacy->refresh();
        $this->assertSame(UserRole::ENTITY_VIEWER->value, $legacy->role->value);
        $this->assertTrue($legacy->hasRole(UserRole::ENTITY_VIEWER->value));
        $this->assertFalse($legacy->hasRole(UserRole::ENTITY_HEAD->value));
    }

    public function test_creating_an_entity_provisions_a_read_only_login(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.entities.store'), [
                'name' => 'Direction X',
                'name_ar' => 'مديرية إكس',
                'name_fr' => 'Direction X',
                'type' => EntityType::MUNICIPALITY->value,
                'wilaya_id' => 16,
                'email' => 'direction.x@mazayada.test',
                'password' => 'StrongP@ss123',
                'password_confirmation' => 'StrongP@ss123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.entities.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'direction.x@mazayada.test',
            'account_type' => AccountType::INSTITUTION->value,
            'role' => UserRole::ENTITY_VIEWER->value,
        ]);
    }

    // ===== helpers =====

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

    private function makeAuction(string $entityId, int $categoryId, string $title): Auction
    {
        return Auction::create([
            'entity_id' => $entityId,
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

    private function viewer(string $entityId): User
    {
        $user = User::create([
            'nin' => $this->randomNin(),
            'first_name_ar' => 'موظف',
            'last_name_ar' => 'جهة',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'viewer'.Str::random(8).'@mazayada.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::ENTITY_VIEWER,
            'entity_id' => $entityId,
            'kyc_status' => KycStatus::COMPLETE,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified' => true,
        ]);
        $user->assignRole(UserRole::ENTITY_VIEWER->value);

        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::create([
            'nin' => $this->randomNin(),
            'first_name_ar' => 'مشرف',
            'last_name_ar' => 'عام',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'super'.Str::random(8).'@mazayada.test',
            'birth_date' => '1980-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::SUPER_ADMIN,
            'entity_id' => null,
            'kyc_status' => KycStatus::COMPLETE,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified' => true,
        ]);
        $user->assignRole(UserRole::SUPER_ADMIN->value);

        return $user;
    }

    private function makeAppeal(Auction $auction, AppealStatus $status, array $overrides = []): Appeal
    {
        return Appeal::create(array_merge([
            'user_id' => $this->makeCitizen()->id,
            'auction_id' => $auction->id,
            'subject' => 'موضوع',
            'reason' => 'سبب',
            'status' => $status,
        ], $overrides));
    }

    private function makeCitizen(): User
    {
        return User::create([
            'nin' => $this->randomNin(),
            'first_name_ar' => 'مواطن',
            'last_name_ar' => 'تجربة',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'citizen'.Str::random(8).'@mazayada.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'email_verified' => true,
        ]);
    }

    private function randomNin(): string
    {
        return str_pad((string) random_int(0, 999999999999999999), 18, '1', STR_PAD_LEFT);
    }
}
