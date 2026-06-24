<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Services\AppealService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAppealController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Appeal::class);

        // whereHas('auction') makes appeals inherit per-entity isolation: the
        // auction sub-query is filtered by EntityScope, so an entity account only
        // sees appeals against its own entity's auctions (SUPER_ADMIN: all).
        $query = Appeal::whereHas('auction')
            ->with(['user', 'auction', 'forwardedBy', 'resolvedBy'])
            ->latest();

        // An entity account only sees appeals once the platform has forwarded
        // them — a freshly-filed PENDING appeal is still in admin triage.
        if (auth()->user()->entity_id !== null) {
            $query->where('status', '!=', AppealStatus::PENDING->value);
        }

        $appeals = $query->paginate(20);

        return view('admin.appeals.index', compact('appeals'));
    }

    /** Platform admin: forward a pending appeal to the organising entity. */
    public function forward(Appeal $appeal, AppealService $appeals): RedirectResponse
    {
        $this->authorize('forward', $appeal);

        try {
            $appeals->forward($appeal, auth()->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('appeals.flash_forwarded'));
    }

    /** Platform admin: reject an invalid appeal at intake, skipping the entity. */
    public function rejectAtIntake(Request $request, Appeal $appeal, AppealService $appeals): RedirectResponse
    {
        $this->authorize('rejectAtIntake', $appeal);

        $request->validate(['admin_response' => ['required', 'string', 'max:2000']]);

        try {
            $appeals->rejectAtIntake($appeal, auth()->user(), $request->admin_response);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('appeals.flash_responded'));
    }

    /** Organising entity: approve/reject a forwarded appeal. */
    public function decide(Request $request, Appeal $appeal, AppealService $appeals): RedirectResponse
    {
        $this->authorize('decide', $appeal);

        $validated = $request->validate([
            'decision' => ['required', 'in:APPROVED,REJECTED'],
            'entity_response' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $appeals->entityDecide($appeal, AppealStatus::from($validated['decision']), $validated['entity_response']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('appeals.flash_entity_decided'));
    }

    /** Platform admin: confirm (or override) the entity's decision — terminal. */
    public function confirm(Request $request, Appeal $appeal, AppealService $appeals): RedirectResponse
    {
        $this->authorize('confirm', $appeal);

        $validated = $request->validate([
            'decision' => ['required', 'in:APPROVED,REJECTED'],
            'admin_response' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $appeals->confirm($appeal, auth()->user(), AppealStatus::from($validated['decision']), $validated['admin_response']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('appeals.flash_responded'));
    }
}
