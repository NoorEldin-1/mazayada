<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Api\ApiController;
use App\Models\Auction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *
     * A 422 carries a stable `code` — branch on it rather than on the (translated)
     * message: `book_free` and `already_bought_book` both mean "go straight to
     * registration"; `commerce_register_required` and `not_eligible` mean "send the
     * user to the Commercial Register / KYC screen first".
     *
     * @response 422 {"message":"لقد اشتريت كراسة الشروط بالفعل.","code":"already_bought_book"}
     */
    public function buyConditionBook(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->initiateBookPurchase($auction, $request->user(), 'api');
        } catch (PaymentException $e) {
            return $this->fail($e->getMessage(), [], 422, $e->errorCode);
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
     *
     * A 422 carries a stable `code`: `must_purchase_book` (go back a step),
     * `already_registered` (proceed to bidding), `commerce_register_required`,
     * `not_eligible`, `nothing_due`.
     *
     * @response 422 {"message":"يجب شراء كراسة الشروط أولاً قبل التسجيل في المزايدة.","code":"must_purchase_book"}
     */
    public function startRegistration(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->initiateRegistration($auction, $request->user(), 'api');
        } catch (PaymentException $e) {
            return $this->fail($e->getMessage(), [], 422, $e->errorCode);
        }

        return $this->ok([
            'redirect_url' => $result['redirect_url'],
            'ref' => $result['ref'],
        ]);
    }
}
