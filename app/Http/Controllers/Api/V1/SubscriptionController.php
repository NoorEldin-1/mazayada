<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\SubscriptionPlanResource;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Premium subscription
 *
 * Premium membership (edits 24 · 25). The checkout reuses the payment gateway:
 * open `redirect_url` in a web view, then poll `GET payments/{ref}/status`.
 */
class SubscriptionController extends ApiController
{
    /**
     * My subscription + plans
     *
     * The current subscription (null if never subscribed) and every active plan,
     * in one call. `is_premium` is the authoritative flag for unlocking features.
     */
    public function show(Request $request, SubscriptionService $subscriptions): JsonResponse
    {
        return $this->ok($this->payload($request, $subscriptions));
    }

    /**
     * Subscribe
     *
     * Starts the checkout for a plan. Same response shape as every other payment.
     * A 422 carries a `code`: `plan_unavailable` or `staff_not_allowed`.
     *
     * @bodyParam plan_code string required The plan code. Example: YEARLY
     */
    public function store(Request $request, SubscriptionService $subscriptions): JsonResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', 'string', 'max:40'],
        ]);

        try {
            $result = $subscriptions->subscribe($request->user(), $validated['plan_code'], 'api');
        } catch (PaymentException $e) {
            return $this->fail($e->getMessage(), [], 422, $e->errorCode);
        }

        return $this->ok([
            'redirect_url' => $result['redirect_url'],
            'ref' => $result['ref'],
        ]);
    }

    /**
     * Stop auto-renew
     *
     * Turns auto-renew off; the paid period stays active until `expires_at`.
     * Returns the same payload as GET.
     */
    public function destroy(Request $request, SubscriptionService $subscriptions): JsonResponse
    {
        $subscriptions->cancelAutoRenew($request->user());

        return $this->ok($this->payload($request, $subscriptions), __('subscriptions.auto_renew_stopped'));
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, SubscriptionService $subscriptions): array
    {
        $user = $request->user()->fresh();
        $current = $subscriptions->current($user);

        return [
            'is_premium' => $user->isPremium(),
            'subscription' => $current ? (new SubscriptionResource($current))->resolve($request) : null,
            'plans' => SubscriptionPlanResource::collection($subscriptions->plans())->resolve($request),
        ];
    }
}
