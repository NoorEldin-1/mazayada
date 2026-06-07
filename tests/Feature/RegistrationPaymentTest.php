<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\AuctionParticipant;
use App\Models\Document;
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

    public function test_paid_registration_confirms_and_flips_flags(): void
    {
        $auction = $this->makeAuction(['deposit_amount' => 100_000, 'entry_fee' => 50_000, 'book_price' => 0]);
        $user = $this->makeCitizen();
        // Acknowledge the condition book first (required before registering).
        $this->makeParticipant($auction, $user, [
            'deposit_paid' => false, 'entry_fee_paid' => false,
            'condition_book_acknowledged_at' => now(),
        ]);

        $service = app(PaymentService::class);
        $result = $service->initiateRegistration($auction, $user);

        // Two PENDING payments created (deposit + entry fee), sharing one ref.
        $this->assertSame(2, Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::PENDING)->count());
        $this->assertStringContainsString('ref=', $result['redirect_url']);

        $service->handleCallback($result['ref'], 'success');

        $this->assertSame(2, Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::CONFIRMED)->count());

        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        $this->assertTrue($participant->deposit_paid);
        $this->assertTrue($participant->entry_fee_paid);

        // A receipt document was generated.
        $this->assertDatabaseHas('documents', ['type' => 'PAYMENT_RECEIPT', 'user_id' => $user->id]);
    }

    public function test_failed_callback_leaves_flags_false(): void
    {
        $auction = $this->makeAuction(['book_price' => 0]);
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user, [
            'deposit_paid' => false, 'entry_fee_paid' => false,
            'condition_book_acknowledged_at' => now(),
        ]);

        $service = app(PaymentService::class);
        $result = $service->initiateRegistration($auction, $user);
        $service->handleCallback($result['ref'], 'fail');

        $this->assertSame(0, Payment::where('auction_id', $auction->id)->where('status', PaymentStatus::CONFIRMED)->count());
        $participant = AuctionParticipant::where('auction_id', $auction->id)->where('user_id', $user->id)->first();
        $this->assertFalse($participant->deposit_paid);
    }

    public function test_cannot_register_without_acknowledging_book(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();
        $this->makeParticipant($auction, $user, ['deposit_paid' => false, 'entry_fee_paid' => false]);

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateRegistration($auction, $user);
    }

    public function test_commerce_register_is_required_for_customs_goods(): void
    {
        $auction = $this->makeAuction(['requires_commerce_register' => true, 'book_price' => 0]);
        $user = $this->makeCitizen(['commerce_register_no' => null]);
        $this->makeParticipant($auction, $user, [
            'deposit_paid' => false, 'entry_fee_paid' => false,
            'condition_book_acknowledged_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateRegistration($auction, $user);
    }
}
