<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\DocumentType;
use App\Enums\PublicationPriority;
use App\Enums\SubscriptionPeriod;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Services\AuctionSessionService;
use App\Services\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

/**
 * Contract checks for the mobile fields/endpoints in BACKEND_REQUIREMENTS.pdf —
 * types matter as much as presence (§7 "quick check after each release").
 */
class ClientEditsApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
        Storage::fake('public');
    }

    public function test_auction_detail_exposes_session_sector_min_bid_and_priority(): void
    {
        $this->refs();
        $this->refCategory->update(['min_increment_percent' => 5]);
        $first = $this->makeAuction(['opening_price' => 100_000_000, 'status' => AuctionStatus::CLOSED, 'closed_at' => now()]);
        $second = app(AuctionSessionService::class)->reschedule($first, now()->addHour(), now()->addDays(2), 10, true);
        $second->update(['publication_priority' => PublicationPriority::PRIORITY]);

        $res = $this->getJson("/api/v1/auctions/{$second->id}")->assertOk();

        $this->assertIsInt($res->json('data.session.round'));
        $res->assertJsonPath('data.session.round', 2)
            ->assertJsonPath('data.session.code', $second->session_code)
            ->assertJsonPath('data.session.reschedule_count', 1)
            ->assertJsonPath('data.session.opening_price.amount', 900_000)
            ->assertJsonPath('data.session.original_opening_price.amount', 1_000_000)
            ->assertJsonPath('data.session.history.0.round', 1)
            ->assertJsonPath('data.session.history.0.status', 'CLOSED')
            ->assertJsonPath('data.session.history.0.result_label', __('auctions.session.result_no_bids'))
            ->assertJsonPath('data.sector.id', (string) $this->refCategory->id)
            ->assertJsonPath('data.sector.min_increment_percent', 5)
            ->assertJsonPath('data.min_bid.amount', 945_000)
            ->assertJsonPath('data.publication_priority', 'PRIORITY')
            ->assertJsonStructure(['data' => ['min_bid' => ['amount', 'formatted']]]);
        $this->assertEquals(10, $res->json('data.session.reduction_percent'));

        $this->getJson("/api/v1/auctions/{$second->id}/price")->assertOk()
            ->assertJsonPath('data.min_bid.amount', 945_000)
            ->assertJsonPath('data.min_increment_percent', 5);

        $list = $this->getJson('/api/v1/auctions')->assertOk();
        $row = collect($list->json('data'))->firstWhere('id', $second->id);
        $this->assertSame(2, $row['session_round']);
        $this->assertSame('PRIORITY', $row['publication_priority']);
    }

    public function test_bid_below_sector_minimum_returns_422_under_amount(): void
    {
        $this->refs();
        $this->refCategory->update(['min_increment_percent' => 5]);
        $auction = $this->makeAuction(['opening_price' => 100_000_000]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);
        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 1_040_000])
            ->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->postJson("/api/v1/auctions/{$auction->id}/bid", ['amount' => 1_050_000])->assertCreated();
    }

    public function test_participation_receipt_reference_and_viewer_role(): void
    {
        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();
        $payments = app(PaymentService::class);
        $result = $payments->initiateRegistration($auction, $user);
        $payments->handleCallback($result['ref'], 'success');
        Sanctum::actingAs($user, ['access']);

        $res = $this->getJson("/api/v1/auctions/{$auction->id}")->assertOk()
            ->assertJsonStructure(['data' => ['participation_receipt' => ['id', 'title', 'download_url']]])
            ->assertJsonMissingPath('data.result_document')
            ->assertJsonPath('meta.viewer.is_staff', false)
            ->assertJsonPath('meta.viewer.role', 'CITIZEN');

        $docId = $res->json('data.participation_receipt.id');
        $this->get("/api/v1/documents/{$docId}/download")->assertOk();

        $this->getJson('/api/v1/documents?type[]='.DocumentType::PARTICIPATION_RECEIPT->value)->assertOk()
            ->assertJsonPath('data.0.type', 'PARTICIPATION_RECEIPT')
            ->assertJsonPath('data.0.type_label', __('enums.document_type.PARTICIPATION_RECEIPT'));
    }

    public function test_book_purchase_closed_code_after_end(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000, 'end_time' => now()->subMinute()]);
        Sanctum::actingAs($this->makeCitizen(), ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/buy-book")
            ->assertStatus(422)->assertJsonPath('code', 'book_sales_closed');
    }

    public function test_subscription_endpoints(): void
    {
        Notification::fake();
        SubscriptionPlan::create([
            'code' => 'YEARLY', 'name_ar' => 'الباقة السنوية', 'period' => SubscriptionPeriod::YEARLY,
            'price' => 1_200_000, 'features' => ['ar' => ['تنبيه فوري', 'إشعارات بريد']], 'is_recommended' => true,
        ]);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/subscription')->assertOk()
            ->assertJsonPath('data.is_premium', false)
            ->assertJsonPath('data.subscription', null)
            ->assertJsonPath('data.plans.0.code', 'YEARLY')
            ->assertJsonPath('data.plans.0.price.amount', 12_000)
            ->assertJsonPath('data.plans.0.features.0', 'تنبيه فوري');

        $this->postJson('/api/v1/subscription', ['plan_code' => 'NOPE'])->assertStatus(422)->assertJsonPath('code', 'plan_unavailable');

        $ref = $this->postJson('/api/v1/subscription', ['plan_code' => 'YEARLY'])->assertOk()
            ->assertJsonStructure(['data' => ['redirect_url', 'ref']])->json('data.ref');

        $this->getJson("/api/v1/payments/{$ref}/status")->assertOk()->assertJsonPath('data.confirmed', false)
            ->assertJsonPath('data.payments.0.type', 'SUBSCRIPTION');

        app(PaymentService::class)->handleCallback($ref, 'success');

        $this->getJson('/api/v1/subscription')->assertOk()
            ->assertJsonPath('data.is_premium', true)
            ->assertJsonPath('data.subscription.status', 'ACTIVE')
            ->assertJsonPath('data.subscription.auto_renew', true)
            ->assertJsonPath('data.subscription.plan.period', 'YEARLY');

        $this->deleteJson('/api/v1/subscription')->assertOk()
            ->assertJsonPath('data.subscription.auto_renew', false)
            ->assertJsonPath('data.is_premium', true);
    }

    public function test_notification_preferences_round_trip_and_email_correction(): void
    {
        $cat = Category::create(['name_ar' => 'عقارات', 'name_fr' => 'Immobilier', 'name_en' => 'Real estate']);
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/preferences/notifications')->assertOk()
            ->assertJsonPath('data.channels.push', true)
            ->assertJsonPath('data.channels.email', false)
            ->assertJsonPath('data.auction_categories', [])
            ->assertJsonPath('data.email_requires_premium', true)
            ->assertJsonPath('data.available_categories.0.id', $cat->id);

        $this->putJson('/api/v1/preferences/notifications', [
            'channels' => ['push' => false, 'email' => true, 'sms' => false],
            'auction_categories' => [$cat->id],
            'new_auction_alerts' => true,
        ])->assertOk()
            ->assertJsonPath('data.channels.push', false)
            ->assertJsonPath('data.channels.email', false) // not Premium → corrected
            ->assertJsonPath('data.auction_categories', [$cat->id]);

        $user->forceFill(['premium_until' => now()->addMonth()])->save();
        $this->putJson('/api/v1/preferences/notifications', [
            'channels' => ['push' => true, 'email' => true, 'sms' => true],
            'auction_categories' => [],
            'new_auction_alerts' => false,
        ])->assertOk()
            ->assertJsonPath('data.channels.email', true)
            ->assertJsonPath('data.channels.sms', false) // no SMS provider
            ->assertJsonPath('data.new_auction_alerts', false);
    }

    public function test_staff_viewer_flag(): void
    {
        $staff = $this->makeCitizen(['role' => UserRole::SUPER_ADMIN]);
        $staff->syncRoles([UserRole::SUPER_ADMIN->value]);
        $auction = $this->makeAuction();
        Sanctum::actingAs($staff, ['access']);

        $this->getJson("/api/v1/auctions/{$auction->id}")->assertOk()
            ->assertJsonPath('meta.viewer.is_staff', true)
            ->assertJsonPath('meta.viewer.role', 'SUPER_ADMIN');
    }
}
