<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Auction;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *
     * A 422 carries a stable `code`: `not_winner` or `final_already_paid`.
     *
     * @response 422 {"message":"تم إتمام الدفع النهائي مسبقاً.","code":"final_already_paid"}
     */
    public function startFinalPayment(Auction $auction, Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->initiateFinalPayment($auction, $request->user(), 'api');
        } catch (PaymentException $e) {
            return $this->fail($e->getMessage(), [], 422, $e->errorCode);
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
            return $this->fail(__('payments.not_winner'), [], 403, 'not_winner');
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
     * Gateway return URL for the MOBILE checkout. Confirms (or fails) the payment
     * set for the reference and reports the resulting status. Idempotent — safe to
     * call more than once, and session-less, so it works inside a web view (the
     * web `payments.callback` sits behind `auth` and would redirect to the login
     * page instead).
     *
     * The client does not have to let this URL load: detecting the
     * `/api/v1/payments/callback` prefix is enough to close the web view and poll
     * `GET payments/{ref}/status`. The authoritative confirmation is the gateway's
     * signed server-to-server webhook either way.
     *
     * @unauthenticated
     *
     * @queryParam ref string required The gateway reference OR our payment id. Example: MOCK-ABC123
     * @queryParam decision string The gateway decision (success|fail). Example: success
     */
    public function callback(Request $request, PaymentService $payments): JsonResponse
    {
        $ref = (string) $request->query('ref');
        $decision = (string) $request->query('decision', 'success');

        if ($ref !== '') {
            $payments->handleCallback($ref, $decision);
        }

        $confirmed = $ref !== '' && $this->forReference($ref)
            ->where('status', \App\Enums\PaymentStatus::CONFIRMED)->exists();

        return $this->ok(
            ['ref' => $ref, 'confirmed' => $confirmed],
            $confirmed ? __('payments.flash_confirmed') : __('payments.flash_failed'),
        );
    }

    /**
     * Payment status
     *
     * Returns the status of every payment row sharing a reference for the
     * authenticated user. Use this to poll after the gateway web view returns.
     *
     * `{ref}` accepts EITHER the gateway reference returned by
     * `POST auctions/{auction}/register|buy-book|final-payment` OR our own payment
     * id — the id is what the gateway echoes back on the return URL, so the client
     * can poll with whichever value it holds.
     */
    public function status(string $ref, Request $request): JsonResponse
    {
        $payments = $this->forReference($ref)
            ->where('user_id', $request->user()->id)
            ->get();

        abort_if($payments->isEmpty(), 404);

        return $this->ok([
            'ref' => $ref,
            // Echo the canonical gateway reference so a client that polled by
            // payment id can switch to it (and vice versa).
            'gateway_ref' => $payments->first()->gateway_ref,
            'confirmed' => $payments->every(fn (Payment $p) => $p->status === \App\Enums\PaymentStatus::CONFIRMED),
            'payments' => PaymentResource::collection($payments)->resolve($request),
        ]);
    }

    /**
     * Payments matching a reference in either form — the gateway's own reference
     * or our payment id. Mirrors PaymentService::handleCallback, which resolves
     * both, so polling never disagrees with what the callback just confirmed.
     */
    private function forReference(string $ref): \Illuminate\Database\Eloquent\Builder
    {
        return Payment::query()
            ->where(fn ($q) => $q->where('gateway_ref', $ref)->orWhere('id', $ref));
    }
}
