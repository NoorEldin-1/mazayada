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
     * Final payment preview
     *
     * The winner's Decree 97-33 fee breakdown BEFORE paying: itemised fee lines
     * (localized labels + dinars), the deposit already credited, the net amount
     * still due, and the payment deadline. Read-only — nothing is charged. Lets the
     * mobile app show the full cost sheet before it sends the winner to the gateway.
     */
    public function finalPaymentPreview(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        $user = $request->user();

        if ($auction->winner_user_id !== $user->id) {
            return $this->fail(__('payments.not_winner'), [], 403);
        }

        $quote = $payments->finalPaymentQuote($auction, $user);
        $fees = $quote['fees'];

        return $this->ok([
            'already_paid' => $payments->confirmedFinalPayment($auction, $user),
            'lines' => array_map(fn (array $line) => [
                'key' => $line['key'],
                'label' => __($line['key']),
                'amount' => dinars($line['amount']),
                'formatted' => dzd($line['amount']),
            ], $fees->lines()),
            'confirmed_deposit' => dinars($quote['confirmed_deposit']),
            'amount_due' => dinars($quote['amount_due']),
            'amount_due_formatted' => dzd($quote['amount_due']),
            // Vehicles only: customs duty payable immediately on top of buyer total.
            'customs_immediate_due' => $fees->customsImmediateDue !== null ? dinars($fees->customsImmediateDue) : null,
            'due_at' => $quote['due_at']->toIso8601String(),
            'deadline_days' => $quote['deadline_days'],
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
