<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\EntityType;
use App\Enums\KycStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Payment;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Support\ReportFilters;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\WilayaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entityA;
    private Entity $entityB;
    private Auction $auctionA;
    private Auction $auctionB;
    private User $citizenX;

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

        $this->citizenX = $this->makeCitizen();
        $citizenY = $this->makeCitizen();

        // entityA money: held deposit 1 000 دج + final payment 5 000 دج (both confirmed).
        $this->makePayment($this->auctionA, $this->citizenX, PaymentType::DEPOSIT, PaymentStatus::CONFIRMED, 100_000);
        $this->makePayment($this->auctionA, $this->citizenX, PaymentType::FINAL_PAYMENT, PaymentStatus::CONFIRMED, 500_000);
        // entityB money: held deposit 2 000 دج (confirmed) belonging to another citizen.
        $this->makePayment($this->auctionB, $citizenY, PaymentType::DEPOSIT, PaymentStatus::CONFIRMED, 200_000);
    }

    public function test_summary_math_is_status_aware(): void
    {
        // No auth / no admin route → EntityScope is a no-op, so this sees every row.
        $filters = ReportFilters::fromRequest(Request::create('/', 'GET'));
        $summary = app(FinancialReportService::class)->summary(
            $filters->applyTo(Payment::query()->whereHas('auction'))
        );

        $this->assertSame(800_000, $summary['gross_confirmed']);   // 100k + 500k + 200k
        $this->assertSame(500_000, $summary['net_revenue']);        // final payment only
        $this->assertSame(300_000, $summary['deposits_held']);      // both confirmed deposits
        $this->assertSame(3, $summary['txn_count']);
    }

    public function test_super_admin_sees_all_entities(): void
    {
        $admin = $this->makeStaff(UserRole::SUPER_ADMIN, null);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE')
            ->assertSee('AUCTION-B-TITLE');
    }

    public function test_entity_account_is_isolated_to_its_own_auctions(): void
    {
        $viewer = $this->makeStaff(UserRole::ENTITY_VIEWER, $this->entityA->id);

        $this->actingAs($viewer)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE')
            ->assertDontSee('AUCTION-B-TITLE');
    }

    public function test_citizen_sees_only_their_own_payments(): void
    {
        // citizenX only ever paid on auctionA, never on auctionB.
        $this->actingAs($this->citizenX)
            ->get(route('citizen.reports'))
            ->assertOk()
            ->assertSee('AUCTION-A-TITLE')
            ->assertDontSee('AUCTION-B-TITLE');
    }

    public function test_role_without_permission_is_forbidden(): void
    {
        $appraiser = $this->makeStaff(UserRole::APPRAISER, $this->entityA->id);

        $this->actingAs($appraiser)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_date_range_filter_excludes_out_of_range_transactions(): void
    {
        $admin = $this->makeStaff(UserRole::SUPER_ADMIN, null);

        // A window entirely in the past excludes every (now-dated) payment.
        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['from' => '2000-01-01', 'to' => '2000-01-02']))
            ->assertOk()
            ->assertSee(__('reports.no_transactions'))
            ->assertDontSee('AUCTION-A-TITLE');
    }

    public function test_csv_export_streams_scoped_rows(): void
    {
        $viewer = $this->makeStaff(UserRole::ENTITY_VIEWER, $this->entityA->id);

        $response = $this->actingAs($viewer)->get(route('admin.reports.export.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('AUCTION-A-TITLE', $csv);
        $this->assertStringNotContainsString('AUCTION-B-TITLE', $csv);
    }

    public function test_pdf_export_returns_a_pdf(): void
    {
        $admin = $this->makeStaff(UserRole::SUPER_ADMIN, null);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    // ===== Helpers =====

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
            'opening_price' => 100_000,
            'deposit_amount' => 10_000,
            'entry_fee' => 0,
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDay(),
            'status' => AuctionStatus::CLOSED,
            'auction_type' => AuctionType::SALE,
            'wilaya_id' => 16,
        ]);
    }

    private function makePayment(Auction $auction, User $user, PaymentType $type, PaymentStatus $status, int $amount): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'auction_id' => $auction->id,
            'payment_type' => $type,
            'amount' => $amount,
            'status' => $status,
            'gateway' => 'mock',
            'confirmed_at' => $status === PaymentStatus::CONFIRMED ? now() : null,
        ]);
    }

    private function makeCitizen(): User
    {
        return $this->makeUser(UserRole::CITIZEN, null);
    }

    private function makeStaff(UserRole $role, ?string $entityId): User
    {
        return $this->makeUser($role, $entityId);
    }

    private function makeUser(UserRole $role, ?string $entityId): User
    {
        $user = User::create([
            'nin' => str_pad((string) random_int(0, 999999999999999999), 18, '1', STR_PAD_LEFT),
            'first_name_ar' => 'مستخدم',
            'last_name_ar' => 'تجربة',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'user'.uniqid().'@mazayada.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => $role,
            'entity_id' => $entityId,
            'email_verified' => true,
            'kyc_status' => KycStatus::COMPLETE,
        ]);

        $user->syncRoles([$role->value]);

        return $user;
    }
}
