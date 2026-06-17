<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @group Auction registration
 *
 * The §4 step-3 flow: acknowledge the condition book, then start paid
 * registration (deposit + entry fee + condition book) via the payment gateway.
 * Requires a verified (KYC-complete) account.
 */
class RegistrationController extends ApiController
{
    /**
     * Acknowledge the condition book
     *
     * Records that the citizen has read the condition book (§10.3) — a
     * prerequisite for starting registration.
     */
    public function acknowledgeBook(Auction $auction, Request $request): JsonResponse
    {
        $participant = AuctionParticipant::firstOrNew([
            'auction_id' => $auction->id,
            'user_id' => $request->user()->id,
        ]);

        $participant->condition_book_acknowledged_at = now();
        $participant->registered_at ??= now();
        $participant->save();

        return $this->ok(
            ['condition_book_acknowledged' => true],
            __('auctions.flash_book_acknowledged'),
        );
    }

    /**
     * Start registration
     *
     * Creates the pending payment(s) and returns the gateway redirect URL. Open
     * `redirect_url` in a web view; once the gateway returns, poll
     * `GET payments/{ref}/status` (or re-fetch the auction) to confirm.
     */
    public function startRegistration(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->initiateRegistration($auction, $request->user());
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }

        return $this->ok([
            'redirect_url' => $result['redirect_url'],
            'ref' => $result['ref'],
        ]);
    }
}
