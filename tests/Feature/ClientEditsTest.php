<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PublicationArea;
use App\Enums\PublicationPriority;
use App\Enums\SubscriptionPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Exceptions\PaymentException;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Document;
use App\Models\NotificationPreference;
use App\Models\PublicationPackage;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\UserNotification;
use App\Services\AuctionService;
use App\Services\AuctionSessionService;
use App\Services\BiddingService;
use App\Services\NewAuctionAlertService;
use App\Services\PaymentService;
use App\Services\PublicationFeeCalculator;
use App\Services\SubscriptionService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * Client edits (BACKEND_REQUIREMENTS.pdf) — service-level coverage for the
 * sector rule, sessions/rescheduling, publication rights, receipts, book-purchase
 * guards, Premium subscriptions and new-auction alerts.
 */
class ClientEditsTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
        Storage::fake('public');
    }

    // ===== Edits 11 · 12 — sector minimum increment =====

    public function test_sector_percent_sets_the_minimum_bid(): void
    {
        $this->refs();
        $this->refCategory->update(['min_increment_percent' => 5]);
        $auction = $this->makeAuction(['opening_price' => 100_000_000]); // 1 000 000 DZD

        $this->assertSame(5.0, $auction->minIncrementPercent());
        $this->assertSame(105_000_000, $auction->minBid());

        // The auction override wins over the sector default.
        $auction->update(['min_increment_percent' => 10]);
        $this->assertSame(110_000_000, $auction->fresh()->minBid());
    }

    public function test_bid_below_sector_minimum_is_refused_and_at_minimum_accepted(): void
    {
        $this->refs();
        $this->refCategory->update(['min_increment_percent' => 5]);
        $auction = $this->makeAuction(['opening_price' => 100_000_000]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user);

        try {
            app(BiddingService::class)->placeBid($auction, $user, 104_000_000);
            $this->fail('A bid under the sector minimum must be refused.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('5', $e->getMessage());
        }

        $bid = app(BiddingService::class)->placeBid($auction, $user, 105_000_000);
        $this->assertSame(105_000_000, (int) $bid->amount);
    }

    public function test_zero_percent_keeps_the_any_raise_rule(): void
    {
        $auction = $this->makeAuction(['opening_price' => 1_000_000]);
        $this->assertSame(1_000_100, $auction->minBid());
    }

    // ===== Edits 5-10 — sessions & rescheduling =====

    public function test_every_auction_gets_a_session_code_and_round_one(): void
    {
        $a = $this->makeAuction();
        $b = $this->makeAuction();

        $this->assertMatchesRegularExpression('/^SES-\d{4}-\d{4}$/', $a->session_code);
        $this->assertNotSame($a->session_code, $b->session_code);
        $this->assertSame(1, (int) $a->fresh()->session_round);
    }

    public function test_reschedule_creates_a_reduced_new_session_and_compounds(): void
    {
        $first = $this->makeAuction([
            'opening_price' => 100_000_000, 'deposit_percent' => 10,
            'status' => AuctionStatus::CLOSED, 'closed_at' => now()->subDay(),
        ]);
        $sessions = app(AuctionSessionService::class);

        $second = $sessions->reschedule($first, now()->addDay(), now()->addDays(8), 10, true);

        $this->assertSame(2, (int) $second->session_round);
        $this->assertSame($first->id, $second->parent_auction_id);
        $this->assertSame($first->id, $second->root_auction_id);
        $this->assertSame(90_000_000, (int) $second->opening_price);
        $this->assertSame(9_000_000, (int) $second->deposit_amount);
        $this->assertSame(100_000_000, (int) $second->original_opening_price);
        $this->assertSame(AuctionStatus::PUBLISHED, $second->status);
        $this->assertNotSame($first->session_code, $second->session_code);

        // The old session can no longer be re-run; the new one must finish first.
        $this->assertFalse($sessions->canReschedule($first));

        $second->update(['status' => AuctionStatus::CLOSED, 'closed_at' => now()]);
        $third = $sessions->reschedule($second->fresh(), now()->addDays(10), now()->addDays(12), 10, false);

        $this->assertSame(3, (int) $third->session_round);
        $this->assertSame(81_000_000, (int) $third->opening_price); // 10% off 900 000
        $this->assertSame($first->id, $third->root_auction_id);
        $this->assertSame(2, $third->rescheduleCount());

        $history = $third->sessionHistory();
        $this->assertSame([2, 1], $history->pluck('session_round')->map(fn ($r) => (int) $r)->all());
        $this->assertSame(__('auctions.session.result_no_bids'), $history->first()->sessionResultLabel());
    }

    public function test_an_awarded_session_cannot_be_rescheduled(): void
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'winner_user_id' => $winner->id, 'final_price' => 2_000_000]);

        $this->expectException(RuntimeException::class);
        app(AuctionSessionService::class)->reschedule($auction, now()->addDay(), now()->addDays(2), 10, true);
    }

    // ===== Edit 15 — priority publications list first =====

    public function test_priority_publications_are_listed_first(): void
    {
        $normal = $this->makeAuction(['status' => AuctionStatus::ACTIVE, 'start_time' => now()->subMinute()]);
        $priority = $this->makeAuction([
            'status' => AuctionStatus::ACTIVE, 'start_time' => now()->subDays(3),
            'publication_priority' => PublicationPriority::PRIORITY,
        ]);

        $ids = $this->getJson('/api/v1/auctions')->assertOk()->json('data.*.id');
        $this->assertSame($priority->id, $ids[0]);
        $this->assertContains($normal->id, $ids);
    }

    // ===== Edits 13-17 — publication fee =====

    public function test_publication_fee_is_package_price_plus_priority_surcharge(): void
    {
        $package = PublicationPackage::create([
            'code' => 'HOME', 'name_ar' => 'الرئيسية', 'display_area' => PublicationArea::HOMEPAGE,
            'price' => 500_000, 'priority_price' => 200_000, 'duration_days' => 30,
        ]);
        $calc = app(PublicationFeeCalculator::class);

        $this->assertSame(500_000, $calc->total($package, PublicationPriority::NORMAL));
        $this->assertSame(700_000, $calc->total($package, PublicationPriority::PRIORITY));
        $this->assertSame(0, $calc->total(null, PublicationPriority::PRIORITY));
    }

    public function test_admin_cannot_publish_until_the_publication_fee_is_paid(): void
    {
        $admin = $this->makeCitizen(['role' => UserRole::SUPER_ADMIN]);
        $admin->syncRoles([UserRole::SUPER_ADMIN->value]);
        $auction = $this->makeAuction(['status' => AuctionStatus::DRAFT, 'publication_fee' => 700_000]);

        $this->actingAs($admin)->post(route('admin.auctions.publish', $auction))->assertSessionHasErrors('status');
        $this->assertSame(AuctionStatus::DRAFT, $auction->fresh()->status);

        $this->actingAs($admin)->post(route('admin.auctions.publication-fee', $auction), ['reference' => 'VIR-1'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.auctions.publish', $auction))->assertSessionHasNoErrors();

        $fresh = $auction->fresh();
        $this->assertSame(AuctionStatus::PUBLISHED, $fresh->status);
        $this->assertSame('VIR-1', $fresh->publication_fee_ref);
    }

    // ===== Edits 3 · 4 — condition-book purchase guards =====

    public function test_book_cannot_be_bought_after_the_auction_ended(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000, 'end_time' => now()->subMinute()]);

        try {
            app(PaymentService::class)->initiateBookPurchase($auction, $this->makeCitizen());
            $this->fail('Book purchase must be closed once the auction ended.');
        } catch (PaymentException $e) {
            $this->assertSame('book_sales_closed', $e->errorCode);
        }
    }

    public function test_staff_cannot_buy_the_book_or_register(): void
    {
        $staff = $this->makeCitizen(['role' => UserRole::SUPER_ADMIN]);
        $staff->syncRoles([UserRole::SUPER_ADMIN->value]);
        $auction = $this->makeAuction(['book_price' => 300_000]);

        foreach (['initiateBookPurchase', 'initiateRegistration'] as $method) {
            try {
                app(PaymentService::class)->{$method}($auction, $staff);
                $this->fail("{$method} must refuse staff accounts.");
            } catch (PaymentException $e) {
                $this->assertSame('staff_not_allowed', $e->errorCode);
            }
        }
    }

    // ===== Edits 21-23 — receipts =====

    public function test_confirmed_registration_issues_a_participation_receipt(): void
    {
        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();
        $service = app(PaymentService::class);

        $result = $service->initiateRegistration($auction, $user);
        $service->handleCallback($result['ref'], 'success');

        $receipt = Document::where('type', DocumentType::PARTICIPATION_RECEIPT)->where('user_id', $user->id)->first();
        $this->assertNotNull($receipt);
        $this->assertSame($auction->session_code, $receipt->meta['session_code']);
        $this->assertSame(1, $receipt->meta['participant_no']);
        $this->assertSame(100_000, $receipt->meta['deposit']);

        // Re-delivery of the callback must not issue a second receipt.
        $service->handleCallback($result['ref'], 'success');
        $this->assertSame(1, Document::where('type', DocumentType::PARTICIPATION_RECEIPT)->count());
    }

    public function test_close_issues_a_result_document_for_every_participant(): void
    {
        $auction = $this->makeAuction();
        $winner = $this->makeCitizen();
        $loser = $this->makeCitizen();
        $this->makeParticipant($auction, $winner);
        $this->makeParticipant($auction, $loser);
        Bid::create(['auction_id' => $auction->id, 'user_id' => $loser->id, 'amount' => 1_100_000, 'bid_time' => now()->subMinutes(2), 'is_valid' => true]);
        Bid::create(['auction_id' => $auction->id, 'user_id' => $winner->id, 'amount' => 1_200_000, 'bid_time' => now()->subMinute(), 'is_valid' => true]);

        app(AuctionService::class)->close($auction);

        $docs = Document::where('type', DocumentType::AUCTION_RESULT)->get()->keyBy('user_id');
        $this->assertCount(2, $docs);
        $this->assertTrue($docs[$winner->id]->meta['is_winner']);
        $this->assertSame(1, $docs[$winner->id]->meta['my_rank']);
        $this->assertSame(2, $docs[$loser->id]->meta['my_rank']);
        $this->assertSame(1_200_000, $docs[$loser->id]->meta['final_price']);
    }

    // ===== Edits 24-25 — Premium subscription =====

    public function test_subscription_checkout_activates_premium_on_confirmation(): void
    {
        Notification::fake();
        $plan = $this->makePlan();
        $user = $this->makeCitizen();
        $subscriptions = app(SubscriptionService::class);

        $result = $subscriptions->subscribe($user, 'YEARLY');
        $payment = \App\Models\Payment::where('gateway_ref', $result['ref'])->firstOrFail();
        $this->assertSame(PaymentType::SUBSCRIPTION, $payment->payment_type);
        $this->assertNull($payment->auction_id);
        $this->assertFalse($user->fresh()->isPremium());

        app(PaymentService::class)->handleCallback($result['ref'], 'success');

        $user->refresh();
        $this->assertTrue($user->isPremium());
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->expires_at->isAfter(now()->addDays(360)));
        $this->assertSame(PaymentStatus::CONFIRMED, $payment->fresh()->status);

        $subscriptions->cancelAutoRenew($user);
        $this->assertFalse($subscription->fresh()->auto_renew);
        $this->assertTrue($user->fresh()->isPremium(), 'Stopping auto-renew keeps the paid period.');
    }

    public function test_sweep_expires_subscriptions_and_removes_premium(): void
    {
        Notification::fake();
        $plan = $this->makePlan();
        $user = $this->makeCitizen(['premium_until' => now()->subMinute()]);
        Subscription::create([
            'user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => SubscriptionStatus::ACTIVE,
            'price' => $plan->price, 'started_at' => now()->subYear(), 'expires_at' => now()->subMinute(), 'auto_renew' => true,
        ]);

        [$expired] = app(SubscriptionService::class)->sweep();

        $this->assertSame(1, $expired);
        $this->assertSame(SubscriptionStatus::EXPIRED, Subscription::first()->status);
        $this->assertFalse($user->fresh()->isPremium());
    }

    // ===== Edits 26 · 28 · 29 — new-auction alerts =====

    public function test_alerts_go_to_premium_first_then_to_others_after_the_delay(): void
    {
        $this->refs();
        $premium = $this->makeCitizen(['premium_until' => now()->addMonth()]);
        $regular = $this->makeCitizen();
        $notInterested = $this->makeCitizen();
        NotificationPreference::create(['user_id' => $notInterested->id, 'auction_categories' => [999]]);

        $auction = $this->makeAuction(['status' => AuctionStatus::PUBLISHED]);
        $alerts = app(NewAuctionAlertService::class);

        $this->assertSame(1, $alerts->dispatchPremiumWave($auction));
        $this->assertSame(0, $alerts->dispatchPremiumWave($auction->fresh()), 'Waves are idempotent.');
        $this->assertTrue(UserNotification::where('user_id', $premium->id)->where('event', 'new_auction_match')->exists());
        $this->assertFalse(UserNotification::where('user_id', $regular->id)->exists());

        // Not due yet.
        $this->assertSame(0, $alerts->dispatchDue()['public']);

        Auction::whereKey($auction->id)->update(['premium_alerted_at' => now()->subHour()]);
        $this->assertSame(1, $alerts->dispatchDue()['public']);
        $this->assertTrue(UserNotification::where('user_id', $regular->id)->where('event', 'new_auction_match')->exists());
        $this->assertFalse(UserNotification::where('user_id', $notInterested->id)->exists());
    }

    private function makePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'code' => 'YEARLY', 'name_ar' => 'الباقة السنوية', 'period' => SubscriptionPeriod::YEARLY,
            'price' => 1_200_000, 'features' => ['ar' => ['تنبيه فوري'], 'fr' => [], 'en' => ['Instant alerts']],
            'is_recommended' => true,
        ]);
    }
}
