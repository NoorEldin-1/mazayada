<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Premium subscriptions overview (client edit 25, admin side): every purchase
 * with plan, status, period and renewal — filterable by status and plan.
 */
class AdminSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('subscriptions.manage');

        $query = Subscription::with(['user', 'plan'])->latest();

        if ($status = SubscriptionStatus::tryFrom((string) $request->query('status'))) {
            $query->where('status', $status);
        }
        if ($request->filled('plan')) {
            $query->where('subscription_plan_id', (int) $request->query('plan'));
        }
        if ($request->filled('search')) {
            $term = '%'.$request->query('search').'%';
            $query->whereHas('user', fn ($u) => $u->where('email', 'like', $term)
                ->orWhere('first_name_ar', 'like', $term)->orWhere('last_name_ar', 'like', $term));
        }

        $subscriptions = $query->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        $stats = [
            'active' => Subscription::where('status', SubscriptionStatus::ACTIVE)->where('expires_at', '>', now())->count(),
            'pending' => Subscription::where('status', SubscriptionStatus::PENDING)->count(),
            'auto_renew_off' => Subscription::where('status', SubscriptionStatus::ACTIVE)->where('auto_renew', false)->count(),
        ];

        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'stats'));
    }
}
