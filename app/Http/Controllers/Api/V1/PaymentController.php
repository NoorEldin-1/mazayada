<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Auction;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @group Payments
 *
 * The winner's final payment plus the gateway return handling and a status
 * endpoint the app polls after the gateway web view closes.
 */
class PaymentController extends ApiController
{
    /**
     * Start final payment
     *
     * Begins the winner's final payment (§4 step 7) and returns the gateway
     * redirect URL.
     */
    public function startFinalPayment(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->initiateFinalPayment($auction, $request->user());
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }

        return $this->ok([
            'redirect_url' => $result['redirect_url'],
            'ref' => $result['ref'],
        ]);
    }

    /**
     * Payment callback
     *
     * Gateway return URL. Confirms (or fails) the payment set for the reference
     * and reports the resulting status. Idempotent — safe to call more than once.
     *
     * @unauthenticated
     *
     * @queryParam ref string required The gateway reference. Example: MOCK-ABC123
     * @queryParam decision string The gateway decision (success|fail). Example: success
     */
    public function callback(Request $request, PaymentService $payments): JsonResponse
    {
        $ref = (string) $request->query('ref');
        $decision = (string) $request->query('decision', 'success');

        if ($ref !== '') {
            $payments->handleCallback($ref, $decision);
        }

        $confirmed = $ref !== '' && Payment::where('gateway_ref', $ref)
            ->where('status', \App\Enums\PaymentStatus::CONFIRMED)->exists();

        return $this->ok(
            ['ref' => $ref, 'confirmed' => $confirmed],
            $confirmed ? __('payments.flash_confirmed') : __('payments.flash_failed'),
        );
    }

    /**
     * Payment status
     *
     * Returns the status of every payment row sharing a gateway reference for the
     * authenticated user. Use this to poll after the gateway web view returns.
     */
    public function status(string $ref, Request $request): JsonResponse
    {
        $payments = Payment::where('gateway_ref', $ref)
            ->where('user_id', $request->user()->id)
            ->get();

        abort_if($payments->isEmpty(), 404);

        return $this->ok([
            'ref' => $ref,
            'payments' => PaymentResource::collection($payments)->resolve($request),
        ]);
    }
}
