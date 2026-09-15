<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Exceptions\PaymentException;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\AuctionEventNotification;
use App\Services\Payments\PaymentDriver;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Premium subscriptions (client edits 24 · 25).
 *
 * Checkout reuses the platform payment gateway and ledger: a PENDING
 * subscription + a PENDING payment (PaymentType::SUBSCRIPTION, no auction) are
 * created together; PaymentService::handleCallback confirms the payment and
 * calls activateFromPayment(). users.premium_until is extended on activation and
 * remains the single source of truth for User::isPremium().
 *
 * There is no stored card, so auto-renew cannot charge by itself: at expiry an
 * auto-renewing subscription is marked EXPIRED and the citizen is notified to
 * renew (one tap — a new purchase stacks on top of any remaining time).
 */
class SubscriptionService
{
    public function __construct(private readonly PaymentGatewayInterface $gateway) {}

    /** @return Collection<int, SubscriptionPlan> */
    public function plans(): Collection
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();
    }

    /**
     * The subscription to show: the active one, else a checkout in progress,
     * else the most recent past one. Null when the user never subscribed.
     */
    public function current(User $user): ?Subscription
    {
        $base = Subscription::with('plan')->where('user_id', $user->id);

        return (clone $base)->where('status', SubscriptionStatus::ACTIVE)->where('expires_at', '>', now())->orderByDesc('expires_at')->first()
            ?? (clone $base)->where('status', SubscriptionStatus::PENDING)->where('created_at', '>=', now()->subDay())->latest()->first()
            ?? (clone $base)->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED])->orderByDesc('expires_at')->latest()->first();
    }

    /**
     * Start a checkout for a plan.
     *
     * @param  string  $channel  'web' | 'api' — selects the gateway return URL
     * @return array{redirect_url: string, ref: string}
     */
    public function subscribe(User $user, string $planCode, string $channel = 'api'): array
    {
        $plan = SubscriptionPlan::where('code', $planCode)->where('is_active', true)->first();

        if (! $plan) {
            throw PaymentException::planUnavailable();
        }

        if ($user->isStaff()) {
            throw PaymentException::staffNotAllowed();
        }

        return DB::transaction(function () use ($user, $plan, $channel) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'auction_id' => null,
                'payment_type' => PaymentType::SUBSCRIPTION,
                'amount' => (int) $plan->price,
                'status' => PaymentStatus::PENDING,
                'gateway' => PaymentDriver::current(),
                'payable_meta' => ['purpose' => 'subscription', 'plan_code' => $plan->code],
            ]);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::PENDING,
                'price' => (int) $plan->price,
                'auto_renew' => true,
                'payment_id' => $payment->id,
            ]);

            $route = $channel === 'api' ? 'api.v1.payments.callback' : 'payments.callback';
            $result = $this->gateway->charge($payment, [
                'description' => __('payments.subscription_description', ['plan' => $plan->name]),
                'success_url' => route($route, ['ref' => $payment->id, 'decision' => 'success']),
                'failure_url' => route($route, ['ref' => $payment->id, 'decision' => 'fail']),
            ]);

            $payment->update([
                'gateway_ref' => $result->ref,
                'gateway_payload' => $result->raw,
                'payable_meta' => ['purpose' => 'subscription', 'plan_code' => $plan->code, 'subscription_id' => $subscription->id],
            ]);

            AuditLog::log('SUBSCRIPTION_CHECKOUT', 'Subscription', $subscription->id, $user->id, $user->role?->value, [
                'plan' => $plan->code, 'ref' => $result->ref,
            ]);

            return [
                'redirect_url' => $result->redirectUrl ?? ($channel === 'api' ? route('api.v1.payments.callback', ['ref' => $payment->id]) : route('citizen.subscription')),
                'ref' => $result->ref,
            ];
        });
    }

    /**
     * Called by PaymentService once a SUBSCRIPTION payment is confirmed. A renewal
     * bought while still Premium starts at the current expiry, so no paid day is lost.
     */
    public function activateFromPayment(Payment $payment): ?Subscription
    {
        $subscription = Subscription::with(['plan', 'user'])->where('payment_id', $payment->id)->first();

        if (! $subscription || $subscription->status === SubscriptionStatus::ACTIVE) {
            return $subscription;
        }

        $user = $subscription->user;
        $start = $user && $user->isPremium() ? $user->premium_until->copy() : now();
        $expires = $subscription->plan->period->addTo($start);

        DB::transaction(function () use ($subscription, $user, $start, $expires) {
            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'started_at' => $start,
                'expires_at' => $expires,
            ]);

            if ($user && (! $user->premium_until || $user->premium_until->lessThan($expires))) {
                $user->forceFill(['premium_until' => $expires])->save();
            }
        });

        AuditLog::log('SUBSCRIPTION_ACTIVATED', 'Subscription', $subscription->id, $user?->id, null, [
            'expires_at' => $expires->toIso8601String(),
        ]);

        if ($user) {
            $this->notify($user, 'subscription_activated', [
                'plan' => $subscription->plan->name,
                'date' => $expires->format('Y-m-d'),
            ]);
        }

        return $subscription->refresh();
    }

    /** The checkout's payment failed — the pending subscription is closed. */
    public function failFromPayment(Payment $payment): void
    {
        Subscription::where('payment_id', $payment->id)
            ->where('status', SubscriptionStatus::PENDING)
            ->update(['status' => SubscriptionStatus::CANCELLED, 'cancelled_at' => now(), 'auto_renew' => false]);
    }

    /** Stop auto-renew; the paid period keeps running until expires_at. */
    public function cancelAutoRenew(User $user): ?Subscription
    {
        $subscription = $this->current($user);

        if ($subscription && $subscription->isActive() && $subscription->auto_renew) {
            $subscription->update(['auto_renew' => false, 'cancelled_at' => now()]);
            AuditLog::log('SUBSCRIPTION_AUTO_RENEW_OFF', 'Subscription', $subscription->id, $user->id);
        }

        return $subscription?->refresh();
    }

    /**
     * Scheduler: close subscriptions whose period ended and remind those about
     * to end. Returns [expired, reminded].
     *
     * @return array{0: int, 1: int}
     */
    public function sweep(): array
    {
        $expired = 0;
        Subscription::with(['plan', 'user'])
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('expires_at', '<=', now())
            ->get()
            ->each(function (Subscription $s) use (&$expired) {
                $s->update(['status' => $s->auto_renew ? SubscriptionStatus::EXPIRED : SubscriptionStatus::CANCELLED]);
                $expired++;

                // Only announce the end when no newer subscription keeps the user Premium.
                if ($s->user && ! $s->user->isPremium()) {
                    $this->notify($s->user, 'subscription_expired', ['plan' => $s->plan?->name ?? '']);
                }
            });

        $days = max(1, (int) setting('subscriptions.expiry_reminder_days', 7));
        $reminded = 0;
        Subscription::with(['plan', 'user'])
            ->where('status', SubscriptionStatus::ACTIVE)
            ->whereNull('expiry_reminded_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->get()
            ->each(function (Subscription $s) use (&$reminded) {
                $s->update(['expiry_reminded_at' => now()]);
                if ($s->user && $s->user->premium_until && $s->user->premium_until->lessThanOrEqualTo($s->expires_at)) {
                    $this->notify($s->user, 'subscription_expiring', [
                        'plan' => $s->plan?->name ?? '',
                        'days' => $s->daysRemaining(),
                        'date' => $s->expires_at->format('Y-m-d'),
                    ]);
                    $reminded++;
                }
            });

        return [$expired, $reminded];
    }

    /**
     * @param  array<string, string|int>  $params
     */
    private function notify(User $user, string $event, array $params): void
    {
        try {
            // The web page path contains /subscription, which the app also uses
            // as a routing fallback for these events.
            $user->notify(new AuctionEventNotification($event, $params, route('citizen.subscription')));
        } catch (\Throwable $e) {
            Log::warning('Subscription notification failed', ['user_id' => $user->id, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
