<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates the money flows of the auction lifecycle (spec §4 steps 3, 7, 8):
 * registration (deposit + entry fee + condition book), final payment, refunds,
 * forfeiture. Participant flags are flipped ONLY after the gateway confirms —
 * never optimistically (the old broken registerParticipant did the opposite).
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly DocumentService $documents,
        private readonly NotificationService $notifications,
        private readonly FeeCalculator $fees,
    ) {}

    /**
     * Begin registration checkout. Registration now charges ONLY the
     * participation deposit (رسوم المشاركة = a % of the opening price). The
     * condition book is purchased separately BEFOREHAND (a prerequisite) and the
     * legacy entry fee was removed. Throws on any eligibility violation.
     *
     * @return array{redirect_url: string, ref: string}
     */
    public function initiateRegistration(Auction $auction, User $user): array
    {
        if (! $user->canBid()) {
            throw new RuntimeException(__('payments.not_eligible'));
        }

        // The condition book must be PURCHASED before registering — it replaces
        // the old free acknowledgement. A free book (no price) satisfies this.
        if (! $auction->hasBookAccess($user)) {
            throw new RuntimeException(__('payments.must_purchase_book'));
        }

        // §2.3 — professional customs goods require a valid Commerce Register.
        if ($auction->requires_commerce_register && ! $user->hasCommerceRegister()) {
            throw new RuntimeException(__('payments.commerce_register_required'));
        }

        $participant = $auction->participants()->where('user_id', $user->id)->first();
        if ($participant && $participant->isFullyRegistered()) {
            throw new RuntimeException(__('payments.already_registered'));
        }

        if ((int) $auction->deposit_amount <= 0) {
            throw new RuntimeException(__('payments.nothing_due'));
        }

        $driver = $this->driverName();

        return DB::transaction(function () use ($auction, $user, $driver) {
            $payment = $this->pending($auction, $user, PaymentType::DEPOSIT, (int) $auction->deposit_amount, $driver, [
                'purpose' => 'registration',
            ]);

            $result = $this->gateway->charge($payment, [
                'description' => __('payments.registration_description', ['auction' => $auction->localizedTitle()]),
            ]);

            $payment->update(['gateway_ref' => $result->ref, 'gateway_payload' => $result->raw]);

            AuditLog::log('PAYMENT_INITIATED', 'Auction', $auction->id, $user->id, $user->role?->value, [
                'purpose' => 'registration',
                'ref' => $result->ref,
            ]);

            return ['redirect_url' => $result->redirectUrl ?? route('auctions.show', $auction), 'ref' => $result->ref];
        });
    }

    /**
     * Begin a standalone condition-book (دفتر الشروط) purchase. Open to any
     * KYC-verified user — buying the book does NOT require participating, but it
     * IS a prerequisite for registering. Returns a gateway redirect URL.
     *
     * @return array{redirect_url: string, ref: string}
     */
    public function initiateBookPurchase(Auction $auction, User $user): array
    {
        if (! $user->canBid()) {
            throw new RuntimeException(__('payments.not_eligible'));
        }

        if ((int) $auction->book_price <= 0) {
            throw new RuntimeException(__('payments.book_free'));
        }

        if ($auction->hasBookAccess($user)) {
            throw new RuntimeException(__('payments.already_bought_book'));
        }

        $driver = $this->driverName();

        return DB::transaction(function () use ($auction, $user, $driver) {
            $payment = $this->pending($auction, $user, PaymentType::BOOK_PURCHASE, (int) $auction->book_price, $driver, [
                'purpose' => 'book_purchase',
            ]);

            $result = $this->gateway->charge($payment, [
                'description' => __('payments.book_purchase_description', ['auction' => $auction->localizedTitle()]),
            ]);

            $payment->update(['gateway_ref' => $result->ref, 'gateway_payload' => $result->raw]);

            AuditLog::log('PAYMENT_INITIATED', 'Auction', $auction->id, $user->id, $user->role?->value, [
                'purpose' => 'book_purchase',
                'ref' => $result->ref,
            ]);

            return ['redirect_url' => $result->redirectUrl ?? route('auctions.show', $auction), 'ref' => $result->ref];
        });
    }

    /**
     * Begin the winner's final payment (spec §7). Amount = buyer total (fees +
     * TVA) minus the already-confirmed deposit. Customs requires ≥ 20% now.
     *
     * @return array{redirect_url: string, ref: string}
     */
    public function initiateFinalPayment(Auction $auction, User $user): array
    {
        if ($auction->winner_user_id !== $user->id) {
            throw new RuntimeException(__('payments.not_winner'));
        }

        if ($this->confirmedFinalPayment($auction, $user)) {
            throw new RuntimeException(__('payments.final_already_paid'));
        }

        $fees = $this->fees->forAward($auction, (int) $auction->final_price);
        $confirmedDeposit = (int) $auction->payments()
            ->where('user_id', $user->id)
            ->where('payment_type', PaymentType::DEPOSIT)
            ->where('status', PaymentStatus::CONFIRMED)
            ->sum('amount');

        $amount = max(0, $fees->buyerTotal - $confirmedDeposit);
        $deadlineDays = $auction->finalPaymentDeadlineDays();
        $dueAt = ($auction->closed_at ?? now())->copy()->addDays($deadlineDays);

        $driver = $this->driverName();

        return DB::transaction(function () use ($auction, $user, $amount, $dueAt, $fees, $driver) {
            $payment = $this->pending($auction, $user, PaymentType::FINAL_PAYMENT, $amount, $driver, [
                'purpose' => 'final_payment',
                'customs_immediate_due' => $fees->customsImmediateDue,
            ]);
            $payment->update(['due_at' => $dueAt]);

            $result = $this->gateway->charge($payment, [
                'description' => __('payments.final_description', ['auction' => $auction->localizedTitle()]),
            ]);

            $payment->update(['gateway_ref' => $result->ref, 'gateway_payload' => $result->raw]);

            AuditLog::log('PAYMENT_INITIATED', 'Auction', $auction->id, $user->id, $user->role?->value, [
                'purpose' => 'final_payment',
                'ref' => $result->ref,
                'amount' => $amount,
            ]);

            return ['redirect_url' => $result->redirectUrl ?? route('auctions.show', $auction), 'ref' => $result->ref];
        });
    }

    /**
     * Handle the gateway return. Confirms (or fails) every PENDING payment
     * sharing the reference, then runs the post-confirmation side effects.
     */
    public function handleCallback(string $ref, string $decision): void
    {
        $payments = Payment::where('gateway_ref', $ref)
            ->where('status', PaymentStatus::PENDING)
            ->get();

        if ($payments->isEmpty()) {
            return;
        }

        $confirmed = $decision === 'success' && $this->gateway->confirm($ref)->isConfirmed();

        DB::transaction(function () use ($payments, $confirmed) {
            foreach ($payments as $payment) {
                $payment->update($confirmed
                    ? ['status' => PaymentStatus::CONFIRMED, 'confirmed_at' => now()]
                    : ['status' => PaymentStatus::FAILED, 'failed_at' => now()]);
            }
        });

        $first = $payments->first();
        $first->loadMissing(['auction', 'user']);
        $user = $first->user;
        $auction = $first->auction;

        if (! $confirmed) {
            AuditLog::log('PAYMENT_FAILED', 'Auction', $auction?->id ?? $ref, $user?->id);
            if ($user) {
                $this->notifications->paymentFailed($user, $first);
            }

            return;
        }

        $purpose = $first->payable_meta['purpose'] ?? ($this->isRegistration($payments) ? 'registration' : 'final_payment');

        if ($auction && $user) {
            match ($purpose) {
                'registration' => $this->completeRegistration($auction, $user, $payments),
                'book_purchase' => $this->completeBookPurchase($auction, $user),
                default => null,
            };
        }

        // Receipt + notification for the (anchor of the) confirmed set.
        $this->documents->generateReceipt($first);
        AuditLog::log('PAYMENT_CONFIRMED', 'Auction', $auction?->id ?? $ref, $user?->id, $user?->role?->value, [
            'ref' => $ref,
            'purpose' => $purpose,
        ]);
        if ($user) {
            $this->notifications->paymentConfirmed($user, $first);
        }
    }

    /** Refund a confirmed deposit (losing bidder — spec §8). */
    public function refundDeposit(Payment $payment): bool
    {
        if ($payment->status !== PaymentStatus::CONFIRMED || $payment->payment_type !== PaymentType::DEPOSIT) {
            return false;
        }

        $result = $this->gateway->refund($payment);
        if (! $result->isRefunded()) {
            return false;
        }

        $payment->update(['status' => PaymentStatus::REFUNDED, 'refunded_at' => now()]);
        AuditLog::log('DEPOSIT_REFUNDED', 'Auction', $payment->auction_id ?? '', $payment->user_id, null, [
            'amount' => (int) $payment->amount,
        ]);

        return true;
    }

    /** Forfeit a defaulting winner's deposit to the entity (spec §8). */
    public function forfeitDeposit(Payment $payment): bool
    {
        if ($payment->status !== PaymentStatus::CONFIRMED || $payment->payment_type !== PaymentType::DEPOSIT) {
            return false;
        }

        $payment->update(['status' => PaymentStatus::FORFEITED, 'forfeited_at' => now()]);
        AuditLog::log('DEPOSIT_FORFEITED', 'Auction', $payment->auction_id ?? '', $payment->user_id, null, [
            'amount' => (int) $payment->amount,
        ]);

        return true;
    }

    public function confirmedFinalPayment(Auction $auction, User $user): bool
    {
        return $auction->payments()
            ->where('user_id', $user->id)
            ->where('payment_type', PaymentType::FINAL_PAYMENT)
            ->where('status', PaymentStatus::CONFIRMED)
            ->exists();
    }

    private function completeRegistration(Auction $auction, User $user, Collection $payments): void
    {
        $participant = AuctionParticipant::firstOrNew([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
        ]);

        foreach ($payments as $payment) {
            match ($payment->payment_type) {
                PaymentType::DEPOSIT => $participant->deposit_paid = true,
                PaymentType::ENTRY_FEE => $participant->entry_fee_paid = true,
                PaymentType::BOOK_PURCHASE => $participant->book_purchased = true,
                default => null,
            };
        }

        $participant->registered_at = $participant->registered_at ?? now();
        $participant->save();

        AuditLog::log('PARTICIPANT_REGISTERED', 'Auction', $auction->id, $user->id, $user->role?->value);
    }

    /**
     * Mark a confirmed standalone book purchase. Unlocks the condition-book
     * download (Auction::hasBookAccess) and records the read-the-terms
     * acknowledgement, WITHOUT marking the user as a registered participant —
     * the deposit is still required to bid.
     */
    private function completeBookPurchase(Auction $auction, User $user): void
    {
        $participant = AuctionParticipant::firstOrNew([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
        ]);

        $participant->book_purchased = true;
        // Buying the book is also the legal "I have read the terms" step.
        $participant->condition_book_acknowledged_at = $participant->condition_book_acknowledged_at ?? now();
        // registered_at is NOT NULL; stamp the stub's creation time (mirrors the
        // old acknowledge flow). isFullyRegistered() still keys off deposit_paid,
        // so a book-only buyer is not treated as a registered bidder.
        $participant->registered_at = $participant->registered_at ?? now();
        $participant->save();

        AuditLog::log('CONDITION_BOOK_PURCHASED', 'Auction', $auction->id, $user->id, $user->role?->value);
    }

    private function isRegistration(Collection $payments): bool
    {
        return $payments->contains(fn (Payment $p) => in_array($p->payment_type, [
            PaymentType::DEPOSIT, PaymentType::ENTRY_FEE,
        ], true));
    }

    private function pending(Auction $auction, User $user, PaymentType $type, int $amount, string $driver, array $meta = []): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'auction_id' => $auction->id,
            'payment_type' => $type,
            'amount' => $amount,
            'status' => PaymentStatus::PENDING,
            'gateway' => $driver,
            'payable_meta' => $meta ?: null,
        ]);
    }

    private function driverName(): string
    {
        return setting('payments.mock', config('mazayada.payments.mock', true)) ? 'mock' : 'cibweb';
    }
}
