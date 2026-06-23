<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Auction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @group Auction registration
 *
 * The §4 flow: buy the condition book (a prerequisite that unlocks its
 * download), then start paid registration (the participation deposit) via the
 * payment gateway. Requires a verified (KYC-complete) account.
 */
class RegistrationController extends ApiController
{
    /**
     * Buy the condition book
     *
     * Creates the pending book-purchase payment and returns the gateway redirect
     * URL. Buying the book unlocks its download and is a prerequisite for
     * registering. Open `redirect_url` in a web view, then re-fetch the auction.
     */
    public function buyConditionBook(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->initiateBookPurchase($auction, $request->user());
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }

        return $this->ok([
            'redirect_url' => $result['redirect_url'],
            'ref' => $result['ref'],
        ]);
    }

    /**
     * Start registration
     *
     * Creates the pending deposit payment and returns the gateway redirect URL.
     * The condition book must already be purchased. Open `redirect_url` in a web
     * view; once the gateway returns, poll `GET payments/{ref}/status` (or
     * re-fetch the auction) to confirm.
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
