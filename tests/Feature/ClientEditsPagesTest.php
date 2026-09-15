<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\PublicationArea;
use App\Enums\SubscriptionPeriod;
use App\Enums\UserRole;
use App\Models\PublicationPackage;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AuctionSessionService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * Render smoke-tests for every web/admin screen added or changed by the client
 * edits, plus the admin form round-trip for the sector % and publication fee.
 */
class ClientEditsPagesTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = $this->makeCitizen(['role' => UserRole::SUPER_ADMIN]);
        $admin->syncRoles([UserRole::SUPER_ADMIN->value]);

        return $admin;
    }

    private function package(): PublicationPackage
    {
        return PublicationPackage::create([
            'code' => 'LIST', 'name_ar' => 'القائمة', 'display_area' => PublicationArea::LISTING,
            'price' => 500_000, 'priority_price' => 300_000, 'duration_days' => 30,
        ]);
    }

    public function test_admin_screens_render(): void
    {
        $admin = $this->admin();
        $package = $this->package();
        $plan = SubscriptionPlan::create(['code' => 'MONTHLY', 'name_ar' => 'شهرية', 'period' => SubscriptionPeriod::MONTHLY, 'price' => 150_000]);
        $closed = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now()]);
        $next = app(AuctionSessionService::class)->reschedule($closed, now()->addDay(), now()->addDays(3), 10, false);
        $draft = $this->makeAuction(['status' => AuctionStatus::DRAFT, 'publication_package_id' => $package->id, 'publication_fee' => 500_000]);

        $this->actingAs($admin);

        foreach ([
            route('admin.auctions.index'),
            route('admin.auctions.index', ['status' => 'CLOSED']),
            route('admin.auctions.create'),
            route('admin.auctions.edit', $draft),
            route('admin.auctions.show', $draft),
            route('admin.auctions.show', $closed),
            route('admin.auctions.show', $next),
            route('admin.categories.create'),
            route('admin.categories.edit', $this->refCategory),
            route('admin.publication-packages.index'),
            route('admin.publication-packages.create'),
            route('admin.publication-packages.edit', $package),
            route('admin.subscription-plans.index'),
            route('admin.subscription-plans.create'),
            route('admin.subscription-plans.edit', $plan),
            route('admin.subscriptions.index'),
            route('admin.settings.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_public_and_citizen_screens_render(): void
    {
        $closed = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now(), 'book_price' => 300_000]);
        $next = app(AuctionSessionService::class)->reschedule($closed, now()->addDay(), now()->addDays(3), 10, true);
        SubscriptionPlan::create(['code' => 'YEARLY', 'name_ar' => 'سنوية', 'period' => SubscriptionPeriod::YEARLY, 'price' => 1_200_000, 'is_recommended' => true]);

        $this->get(route('auctions.show', $next))->assertOk()
            ->assertSee(__('auctions.session.badge', ['round' => 2]))
            ->assertSee($next->session_code);
        $this->get(route('auctions.index'))->assertOk();

        $citizen = $this->makeCitizen();
        $this->actingAs($citizen)->get(route('citizen.subscription'))->assertOk()->assertSee('سنوية');
        $this->actingAs($citizen)->get(route('citizen.documents'))->assertOk();
        $this->actingAs($citizen)->get(route('auctions.show', $closed))->assertOk()->assertSee(__('auctions.purchase_closed'));

        // Staff never see the purchase / registration actions.
        $active = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'book_price' => 300_000]);
        $this->actingAs($this->admin())->get(route('auctions.show', $active))->assertOk()
            ->assertSee(__('auctions.staff_no_participation'))
            ->assertDontSee(route('auctions.buy-book', $active));
    }

    public function test_citizen_can_save_preferences_and_start_a_subscription_checkout(): void
    {
        $this->refs();
        SubscriptionPlan::create(['code' => 'YEARLY', 'name_ar' => 'سنوية', 'period' => SubscriptionPeriod::YEARLY, 'price' => 1_200_000]);
        $citizen = $this->makeCitizen();

        $this->actingAs($citizen)->put(route('citizen.subscription.preferences'), [
            'push' => '1', 'new_auction_alerts' => '1', 'auction_categories' => [$this->refCategory->id],
        ])->assertRedirect();
        $this->assertSame([$this->refCategory->id], $citizen->notificationPreference()->first()->categoryIds());

        $this->actingAs($citizen)->post(route('citizen.subscription.store'), ['plan_code' => 'YEARLY'])->assertRedirect();
        $this->assertDatabaseHas('subscriptions', ['user_id' => $citizen->id, 'status' => 'PENDING']);
    }

    public function test_admin_store_computes_publication_fee_and_sector_override(): void
    {
        $admin = $this->admin();
        $this->refs();
        $package = $this->package();

        $this->actingAs($admin)->post(route('admin.auctions.store'), [
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'title_ar' => 'مزاد اختبار',
            'description_ar' => 'وصف',
            'condition' => 'GOOD',
            'auction_type' => 'SALE',
            'opening_price' => 100000,
            'start_time' => now()->addDay()->format('Y-m-d H:i'),
            'end_time' => now()->addDays(3)->format('Y-m-d H:i'),
            'wilaya_id' => $this->refWilaya->id,
            'min_increment_percent' => 7.5,
            'publication_package_id' => $package->id,
            'publication_priority' => 'PRIORITY',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $auction = \App\Models\Auction::where('title_ar', 'مزاد اختبار')->firstOrFail();
        $this->assertSame(800_000, (int) $auction->publication_fee);
        $this->assertSame('PRIORITY', $auction->publication_priority->value);
        $this->assertSame(7.5, $auction->minIncrementPercent());
        $this->assertTrue($auction->publicationFeeDue());
    }

    public function test_admin_can_reschedule_from_the_web(): void
    {
        $admin = $this->admin();
        $closed = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now(), 'opening_price' => 2_000_000]);

        $this->actingAs($admin)->post(route('admin.auctions.reschedule', $closed), [
            'start_time' => now()->addDay()->format('Y-m-d H:i'),
            'end_time' => now()->addDays(2)->format('Y-m-d H:i'),
            'reduction_percent' => 20,
            'publish' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $next = \App\Models\Auction::where('parent_auction_id', $closed->id)->firstOrFail();
        $this->assertSame(1_600_000, (int) $next->opening_price);
        $this->assertSame(AuctionStatus::PUBLISHED, $next->status);
    }
}
