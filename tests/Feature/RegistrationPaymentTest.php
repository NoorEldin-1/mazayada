<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\AuctionParticipant;
use App\Models\Payment;
use App\Services\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class RegistrationPaymentTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    public function test_paid_registration_charges_only_the_deposit(): void
    {
        // A free book satisfies the prerequisite, so registration charges just
        // the participation deposit (no entry fee, no book bundled in).
        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'book_price' => 0]);
        $user = $this->makeCitizen();

        $service = app(PaymentService::class);
        $result = $service->initiateRegistration($auction, $user);

        $pending = Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::PENDING)->get();
        $this->assertCount(1, $pending);
        $this->assertSame(PaymentType::DEPOSIT, $pending->first()->payment_type);
        $this->assertSame(100_000, (int) $pending->first()->amount);
        $this->assertStringContainsString('ref=', $result['redirect_url']);

        $service->handleCallback($result['ref'], 'success');

        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        $this->assertTrue($participant->deposit_paid);
        $this->assertTrue($participant->isFullyRegistered());
        $this->assertDatabaseHas('documents', ['type' => 'PAYMENT_RECEIPT', 'user_id' => $user->id]);
    }

    public function test_failed_callback_leaves_deposit_unpaid(): void
    {
        $auction = $this->makeAuction(['book_price' => 0]);
        $user = $this->makeCitizen();

        $service = app(PaymentService::class);
        $result = $service->initiateRegistration($auction, $user);
        $service->handleCallback($result['ref'], 'fail');

        $this->assertSame(0, Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::CONFIRMED)->count());
        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        // Registration creates the participant only on a confirmed callback.
        $this->assertTrue($participant === null || ! $participant->deposit_paid);
    }

    public function test_cannot_register_without_buying_the_priced_book(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000]);
        $user = $this->makeCitizen();

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateRegistration($auction, $user);
    }

    public function test_buying_the_book_unlocks_access_then_registration(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000, 'deposit_amount' => 100_000]);
        $user = $this->makeCitizen();

        $this->assertFalse($auction->hasBookAccess($user));

        $service = app(PaymentService::class);
        $book = $service->initiateBookPurchase($auction, $user);

        $pending = Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::PENDING)->get();
        $this->assertCount(1, $pending);
        $this->assertSame(PaymentType::BOOK_PURCHASE, $pending->first()->payment_type);
        $this->assertSame(300_000, (int) $pending->first()->amount);

        $service->handleCallback($book['ref'], 'success');

        $this->assertTrue($auction->fresh()->hasBookAccess($user));
        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        $this->assertTrue($participant->book_purchased);
        // Buying the book does NOT register the bidder — the deposit is still due.
        $this->assertFalse($participant->isFullyRegistered());

        // Now registration succeeds and charges only the deposit.
        $reg = $service->initiateRegistration($auction->fresh(), $user);
        $service->handleCallback($reg['ref'], 'success');
        $this->assertTrue($participant->fresh()->isFullyRegistered());
    }

    public function test_cannot_buy_the_book_twice(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000]);
        $user = $this->makeCitizen();

        $service = app(PaymentService::class);
        $service->handleCallback($service->initiateBookPurchase($auction, $user)['ref'], 'success');

        $this->expectException(RuntimeException::class);
        $service->initiateBookPurchase($auction->fresh(), $user);
    }

    public function test_free_book_grants_access_without_purchase(): void
    {
        $auction = $this->makeAuction(['book_price' => 0]);
        $user = $this->makeCitizen();

        $this->assertTrue($auction->hasBookAccess($user));

        // A free book cannot be "bought".
        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateBookPurchase($auction, $user);
    }

    public function test_commerce_register_is_required_for_customs_goods(): void
    {
        // A user with no APPROVED CommercialRegister (see CommercialRegisterTest
        // for the full module) cannot register on a register-gated auction.
        $auction = $this->makeAuction(['requires_commerce_register' => true, 'book_price' => 0]);
        $user = $this->makeCitizen();

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateRegistration($auction, $user);
    }
}
