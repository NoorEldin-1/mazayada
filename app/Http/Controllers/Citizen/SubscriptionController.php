<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Services\NotificationPreferenceService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Citizen web page for Premium + notification preferences (edits 24-30): the
 * same services as the mobile API, so both channels always agree.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions, NotificationPreferenceService $preferences): View
    {
        $user = $request->user();

        return view('citizen.subscription', [
            'isPremium' => $user->isPremium(),
            'subscription' => $subscriptions->current($user),
            'plans' => $subscriptions->plans(),
            'prefs' => $preferences->payload($user),
        ]);
    }

    public function store(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $validated = $request->validate(['plan_code' => ['required', 'string', 'max:40']]);

        try {
            $result = $subscriptions->subscribe($request->user(), $validated['plan_code'], 'web');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($result['redirect_url']);
    }

    public function cancelAutoRenew(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $subscriptions->cancelAutoRenew($request->user());

        return back()->with('success', __('subscriptions.auto_renew_stopped'));
    }

    public function updatePreferences(Request $request, NotificationPreferenceService $preferences): RedirectResponse
    {
        $validated = $request->validate([
            'auction_categories' => ['nullable', 'array', 'max:100'],
            'auction_categories.*' => ['integer'],
        ]);

        $preferences->update($request->user(), [
            'channels' => [
                'push' => $request->boolean('push'),
                'email' => $request->boolean('email'),
                'sms' => $request->boolean('sms'),
            ],
            'auction_categories' => $validated['auction_categories'] ?? [],
            'new_auction_alerts' => $request->boolean('new_auction_alerts'),
        ]);

        return back()->with('success', __('subscriptions.preferences_saved'));
    }
}
