<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPeriod;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Premium plans (client edit 24 — "الباقات" on the admin side). Gated by
 * 'subscriptions.manage'. Price entered in dinars; features one per line per
 * language.
 */
class AdminSubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $this->authorize('subscriptions.manage');

        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('sort_order')->orderBy('price')->get();

        return view('admin.subscription-plans.index', compact('plans'));
    }

    public function create(): View
    {
        $this->authorize('subscriptions.manage');

        return view('admin.subscription-plans.create', ['plan' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('subscriptions.manage');

        $plan = SubscriptionPlan::create($this->validated($request));

        AuditLog::log('SUBSCRIPTION_PLAN_CREATED', 'SubscriptionPlan', (string) $plan->id, null, null, ['code' => $plan->code]);

        return redirect()->route('admin.subscription-plans.index')->with('success', __('subscriptions.admin.flash_saved'));
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        $this->authorize('subscriptions.manage');

        return view('admin.subscription-plans.edit', ['plan' => $subscriptionPlan]);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $this->authorize('subscriptions.manage');

        $subscriptionPlan->update($this->validated($request, $subscriptionPlan));

        AuditLog::log('SUBSCRIPTION_PLAN_UPDATED', 'SubscriptionPlan', (string) $subscriptionPlan->id);

        return redirect()->route('admin.subscription-plans.index')->with('success', __('subscriptions.admin.flash_saved'));
    }

    public function toggle(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $this->authorize('subscriptions.manage');

        $subscriptionPlan->update(['is_active' => ! $subscriptionPlan->is_active]);

        return back()->with('success', __('subscriptions.admin.flash_saved'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('subscription_plans', 'code')->ignore($plan?->id)],
            'name_ar' => ['required', 'string', 'max:150'],
            'name_fr' => ['nullable', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_fr' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'period' => ['required', Rule::enum(SubscriptionPeriod::class)],
            'price' => ['required', 'numeric', 'min:50', 'max:10000000'],
            'features_ar' => ['nullable', 'string', 'max:3000'],
            'features_fr' => ['nullable', 'string', 'max:3000'],
            'features_en' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $lines = fn (?string $text) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));

        return [
            'code' => strtoupper($data['code']),
            'name_ar' => $data['name_ar'],
            'name_fr' => $data['name_fr'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'description_fr' => $data['description_fr'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'period' => $data['period'],
            'price' => (int) round($data['price'] * 100),
            'features' => [
                'ar' => $lines($data['features_ar'] ?? null),
                'fr' => $lines($data['features_fr'] ?? null),
                'en' => $lines($data['features_en'] ?? null),
            ],
            'is_recommended' => $request->boolean('is_recommended'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
