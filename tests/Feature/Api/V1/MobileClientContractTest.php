<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\NotificationChannel;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Events\PersonalAuctionEvent;
use App\Models\Bid;
use App\Models\Payment;
use App\Models\UserDevice;
use App\Models\UserNotification;
use App\Notifications\AuctionEventNotification;
use App\Services\NotificationService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

/**
 * The contract additions the Flutter client depends on.
 *
 * Each test names the request it answers (BE-n) so a future change that breaks
 * the mobile app fails here first, with the reason attached.
 */
class MobileClientContractTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    // ===== BE-15 — optional token on the public auction routes ==============

    public function test_auction_show_returns_null_viewer_for_a_guest(): void
    {
        $auction = $this->makeAuction();

        $this->getJson("/api/v1/auctions/{$auction->id}")
            ->assertOk()
            ->assertJsonPath('meta.viewer', null)
            ->assertJsonPath('data.has_book_access', false);
    }

    public function test_auction_show_resolves_a_bearer_token_and_returns_viewer_context(): void
    {
        $auction = $this->makeAuction(['book_price' => 0]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);

        // A real token, not Sanctum::actingAs — the whole bug was that a token on
        // an unguarded route resolved to nobody.
        $token = $user->createToken('phone', ['access'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/auctions/{$auction->id}")
            ->assertOk();

        $response->assertJsonPath('meta.viewer.is_participant', true);
        $response->assertJsonPath('meta.viewer.can_bid', true);
        // A free book means access even without a purchase.
        $response->assertJsonPath('data.has_book_access', true);
    }

    public function test_a_refresh_token_is_not_accepted_as_a_session_on_public_routes(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        $refresh = $user->createToken('phone-refresh', ['refresh'])->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$refresh}")
            ->getJson("/api/v1/auctions/{$auction->id}")
            ->assertOk()
            ->assertJsonPath('meta.viewer', null);
    }

    public function test_a_garbage_token_does_not_break_the_public_route(): void
    {
        $auction = $this->makeAuction();

        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson("/api/v1/auctions/{$auction->id}")
            ->assertOk()
            ->assertJsonPath('meta.viewer', null);
    }

    // ===== BE-1 / BE-6 — list resource fields ==============================

    public function test_auction_list_exposes_the_commerce_register_gate_and_outcome(): void
    {
        $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'requires_commerce_register' => true,
            'final_price' => 2_500_000,
            'closed_at' => now(),
        ]);

        $this->getJson('/api/v1/auctions?status[]=closed')
            ->assertOk()
            ->assertJsonPath('data.0.requires_commerce_register', true)
            // Money crosses the boundary in DINARS.
            ->assertJsonPath('data.0.final_price.amount', 25_000)
            ->assertJsonStructure(['data' => [['closed_at']]]);
    }

    // ===== BE-3 — my-auctions participation state ==========================

    public function test_my_auctions_reports_the_viewers_own_bidding_position(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();
        $rival = $this->makeCitizen();
        $this->makeParticipant($auction, $user);

        Bid::create([
            'auction_id' => $auction->id, 'user_id' => $user->id,
            'amount' => 1_200_000, 'bid_time' => now()->subMinute(), 'is_valid' => true,
        ]);
        Bid::create([
            'auction_id' => $auction->id, 'user_id' => $rival->id,
            'amount' => 1_500_000, 'bid_time' => now(), 'is_valid' => true,
        ]);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/my-auctions?tab=active')
            ->assertOk()
            ->assertJsonPath('data.0.my_highest_bid.amount', 12_000)
            // Outbid by the rival.
            ->assertJsonPath('data.0.is_winning', false)
            ->assertJsonPath('data.0.deposit_paid', true)
            ->assertJsonPath('data.0.final_payment_status', null);
    }

    public function test_my_auctions_marks_the_leading_bidder_as_winning(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);

        Bid::create([
            'auction_id' => $auction->id, 'user_id' => $user->id,
            'amount' => 1_200_000, 'bid_time' => now(), 'is_valid' => true,
        ]);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/my-auctions?tab=active')
            ->assertOk()
            ->assertJsonPath('data.0.is_winning', true);
    }

    public function test_my_auctions_surfaces_the_winners_final_payment_status(): void
    {
        $user = $this->makeCitizen();
        $auction = $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'winner_user_id' => $user->id,
            'final_price' => 2_000_000,
            'closed_at' => now(),
        ]);
        $this->makeParticipant($auction, $user);

        Payment::create([
            'user_id' => $user->id, 'auction_id' => $auction->id,
            'payment_type' => PaymentType::FINAL_PAYMENT, 'amount' => 500_000,
            'status' => PaymentStatus::PENDING, 'gateway' => 'mock',
        ]);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/my-auctions?tab=won')
            ->assertOk()
            ->assertJsonPath('data.0.is_winning', true)
            ->assertJsonPath('data.0.final_payment_status', PaymentStatus::PENDING->value);
    }

    public function test_my_auctions_exposes_an_exhaustive_all_tab(): void
    {
        $user = $this->makeCitizen();
        // CANCELLED belongs to no other tab — `all` is what accounts for it.
        $cancelled = $this->makeAuction(['status' => AuctionStatus::CANCELLED]);
        $this->makeParticipant($cancelled, $user);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/my-auctions?tab=all')
            ->assertOk()
            ->assertJsonPath('meta.counts.all', 1)
            ->assertJsonPath('meta.counts.active', 0)
            ->assertJsonCount(1, 'data');
    }

    // ===== BE-16 — machine-readable refusal codes ==========================

    public function test_registration_refusal_carries_a_stable_code(): void
    {
        // book_price > 0 and never purchased => must buy the book first.
        $auction = $this->makeAuction(['book_price' => 200_000]);
        $user = $this->makeCitizen();

        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/register")
            ->assertStatus(422)
            ->assertJsonPath('code', 'must_purchase_book');
    }

    public function test_book_purchase_refusal_carries_a_stable_code(): void
    {
        $auction = $this->makeAuction(['book_price' => 0]);
        $user = $this->makeCitizen();

        Sanctum::actingAs($user, ['access']);

        $this->postJson("/api/v1/auctions/{$auction->id}/buy-book")
            ->assertStatus(422)
            ->assertJsonPath('code', 'book_free');
    }

    // ===== BE-13 — payment status accepts either reference =================

    public function test_payment_status_resolves_by_gateway_ref_or_payment_id(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        $payment = Payment::create([
            'user_id' => $user->id, 'auction_id' => $auction->id,
            'payment_type' => PaymentType::DEPOSIT, 'amount' => 100_000,
            'status' => PaymentStatus::CONFIRMED, 'gateway' => 'mock',
            'gateway_ref' => 'MOCK-REF-123',
        ]);

        Sanctum::actingAs($user, ['access']);

        // The reference the checkout returned…
        $this->getJson('/api/v1/payments/MOCK-REF-123/status')
            ->assertOk()
            ->assertJsonPath('data.confirmed', true)
            ->assertJsonPath('data.gateway_ref', 'MOCK-REF-123');

        // …and the payment id, which is what the gateway echoes on the return URL.
        $this->getJson("/api/v1/payments/{$payment->id}/status")
            ->assertOk()
            ->assertJsonPath('data.gateway_ref', 'MOCK-REF-123');
    }

    public function test_payment_status_does_not_leak_another_users_payment(): void
    {
        $auction = $this->makeAuction();
        $owner = $this->makeCitizen();
        $stranger = $this->makeCitizen();

        Payment::create([
            'user_id' => $owner->id, 'auction_id' => $auction->id,
            'payment_type' => PaymentType::DEPOSIT, 'amount' => 100_000,
            'status' => PaymentStatus::CONFIRMED, 'gateway' => 'mock',
            'gateway_ref' => 'MOCK-PRIVATE',
        ]);

        Sanctum::actingAs($stranger, ['access']);

        $this->getJson('/api/v1/payments/MOCK-PRIVATE/status')->assertNotFound();
    }

    // ===== BE-11 — device registry ========================================

    public function test_device_registration_is_idempotent_and_repoints_a_reused_token(): void
    {
        $first = $this->makeCitizen();
        Sanctum::actingAs($first, ['access']);

        $this->postJson('/api/v1/devices', ['token' => 'TOK-1', 'platform' => 'android'])
            ->assertNoContent();
        // Same launch, called twice — still one row.
        $this->postJson('/api/v1/devices', ['token' => 'TOK-1', 'platform' => 'android'])
            ->assertNoContent();

        $this->assertSame(1, UserDevice::where('token', 'TOK-1')->count());

        // A second account signs in on the same handset: the previous owner must
        // stop receiving this device's push.
        $second = $this->makeCitizen();
        Sanctum::actingAs($second, ['access']);
        $this->postJson('/api/v1/devices', ['token' => 'TOK-1', 'platform' => 'android'])
            ->assertNoContent();

        $this->assertSame(1, UserDevice::where('token', 'TOK-1')->count());
        $this->assertSame($second->id, UserDevice::where('token', 'TOK-1')->first()->user_id);
    }

    public function test_device_unregistration_only_removes_your_own(): void
    {
        $owner = $this->makeCitizen();
        $stranger = $this->makeCitizen();
        UserDevice::register($owner, 'TOK-KEEP', 'ios');

        Sanctum::actingAs($stranger, ['access']);
        $this->deleteJson('/api/v1/devices', ['token' => 'TOK-KEEP'])->assertNoContent();

        $this->assertSame(1, UserDevice::where('token', 'TOK-KEEP')->count());

        Sanctum::actingAs($owner, ['access']);
        $this->deleteJson('/api/v1/devices', ['token' => 'TOK-KEEP'])->assertNoContent();

        $this->assertSame(0, UserDevice::where('token', 'TOK-KEEP')->count());
    }

    public function test_device_registration_validates_the_platform(): void
    {
        Sanctum::actingAs($this->makeCitizen(), ['access']);

        $this->postJson('/api/v1/devices', ['token' => 'TOK-X', 'platform' => 'windows'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('platform');
    }

    // ===== BE-2 — notification event type =================================

    public function test_notifications_expose_the_event_type(): void
    {
        $user = $this->makeCitizen();
        UserNotification::record(
            userId: $user->id,
            title: 'تم تجاوزك',
            body: 'قدّم شخص آخر عرضاً أعلى.',
            actionUrl: null,
            channel: NotificationChannel::IN_APP->value,
            event: 'outbid',
        );

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'outbid')
            ->assertJsonPath('data.0.channel', 'IN_APP');
    }

    public function test_the_in_app_channel_persists_the_event_key(): void
    {
        $user = $this->makeCitizen();

        $user->notify(new AuctionEventNotification('auction_won', [
            'auction' => 'سيارة', 'amount' => '10 000 دج', 'days' => 8,
        ]));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event' => 'auction_won',
        ]);
    }

    // ===== BE-10 — private per-user auction broadcast =====================

    public function test_outbid_broadcasts_on_the_private_participant_channel(): void
    {
        Event::fake([PersonalAuctionEvent::class]);

        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        app(NotificationService::class)->outbid($user, $auction, 1_500_000);

        Event::assertDispatched(PersonalAuctionEvent::class, function (PersonalAuctionEvent $e) use ($user, $auction) {
            $payload = $e->broadcastWith();

            return $e->userId === $user->id
                && $e->auctionId === $auction->id
                && $payload['type'] === 'outbid'
                // Broadcast money is in dinars, like the REST API.
                && $payload['new_price'] === 15_000;
        });
    }

    // ===== BE-14 — realtime config on /ping ===============================

    public function test_ping_publishes_the_public_realtime_config_without_the_secret(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.client.key' => 'public-app-key',
            'broadcasting.connections.reverb.client.host' => 'ws.example.test',
            'broadcasting.connections.reverb.client.port' => 443,
            'broadcasting.connections.reverb.client.scheme' => 'https',
            'broadcasting.connections.reverb.secret' => 'THE-SECRET',
        ]);

        $response = $this->getJson('/api/v1/ping')->assertOk();

        $response->assertJsonPath('data.realtime.key', 'public-app-key');
        $response->assertJsonPath('data.realtime.host', 'ws.example.test');
        $response->assertJsonPath('data.realtime.scheme', 'https');
        $this->assertStringNotContainsString('THE-SECRET', $response->getContent());
    }

    public function test_ping_returns_a_null_realtime_block_when_broadcasting_is_off(): void
    {
        config(['broadcasting.default' => 'null']);

        $this->getJson('/api/v1/ping')
            ->assertOk()
            ->assertJsonPath('data.realtime', null);
    }

    // ===== BE-12 — report money unit ======================================

    public function test_report_summary_returns_dinars_not_centimes(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        Payment::create([
            'user_id' => $user->id, 'auction_id' => $auction->id,
            'payment_type' => PaymentType::BOOK_PURCHASE, 'amount' => 300_000, // 3 000 DZD
            'status' => PaymentStatus::CONFIRMED, 'gateway' => 'mock',
        ]);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.summary.net_revenue.amount', 3_000)
            ->assertJsonPath('data.summary.book_sales.amount', 3_000)
            // Counts stay plain integers.
            ->assertJsonPath('data.summary.txn_count', 1)
            ->assertJsonPath('data.series.unit', 'DZD');
    }

    // ===== BE-7 — report export ===========================================

    public function test_report_export_returns_a_csv_download_not_the_json_envelope(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        Payment::create([
            'user_id' => $user->id, 'auction_id' => $auction->id,
            'payment_type' => PaymentType::BOOK_PURCHASE, 'amount' => 300_000,
            'status' => PaymentStatus::CONFIRMED, 'gateway' => 'mock',
        ]);

        Sanctum::actingAs($user, ['access']);

        $response = $this->get('/api/v1/reports/export/csv')->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        // UTF-8 BOM so Excel renders the Arabic headers.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
    }

    public function test_report_export_renders_a_pdf(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        Payment::create([
            'user_id' => $user->id, 'auction_id' => $auction->id,
            'payment_type' => PaymentType::BOOK_PURCHASE, 'amount' => 300_000,
            'status' => PaymentStatus::CONFIRMED, 'gateway' => 'mock',
        ]);

        Sanctum::actingAs($user, ['access']);

        $response = $this->get('/api/v1/reports/export/pdf')->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_report_export_rejects_an_unknown_format(): void
    {
        Sanctum::actingAs($this->makeCitizen(), ['access']);

        $this->getJson('/api/v1/reports/export/xlsx')->assertNotFound();
    }

    // ===== BE-8 — KYC prefill on the profile ==============================

    public function test_profile_returns_the_kyc_prefill_fields(): void
    {
        $user = $this->makeCitizen([
            'father_name' => 'محمد',
            'mother_name' => 'فاطمة',
            'mother_surname' => 'بن علي',
            'nif' => '000016001234567',
        ]);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.father_name', 'محمد')
            ->assertJsonPath('data.mother_name', 'فاطمة')
            ->assertJsonPath('data.mother_surname', 'بن علي')
            ->assertJsonPath('data.nif', '000016001234567')
            ->assertJsonStructure(['data' => ['rip', 'nis', 'wilaya_id', 'birth_date']]);
    }

    // ===== BE-4 — document filter options =================================

    public function test_document_filters_are_scoped_to_the_users_own_auctions(): void
    {
        $user = $this->makeCitizen();
        $auction = $this->makeAuction();
        $this->makeParticipant($auction, $user);

        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/documents/filters')
            ->assertOk()
            ->assertJsonStructure(['data' => ['categories', 'wilayas', 'entities', 'types', 'presets', 'sorts']])
            ->assertJsonPath('data.entities.0.name', $this->refEntity->name);
    }

    public function test_document_filters_are_empty_for_a_user_with_no_history(): void
    {
        Sanctum::actingAs($this->makeCitizen(), ['access']);

        $this->getJson('/api/v1/documents/filters')
            ->assertOk()
            ->assertJsonCount(0, 'data.categories')
            ->assertJsonCount(0, 'data.entities');
    }

    // ===== BE-9 — JSON document verification ==============================

    public function test_verify_reports_an_unknown_document_as_invalid_with_200(): void
    {
        // Failing to verify IS the answer — an error status would be
        // indistinguishable from a network problem for the QR scanner.
        $this->getJson('/api/v1/verify?doc=nope&sig=nope')
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.document', null);
    }
}
