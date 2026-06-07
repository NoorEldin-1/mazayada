<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAppealController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Appeal::class);

        // whereHas('auction') makes appeals inherit per-entity isolation: the
        // auction sub-query is filtered by EntityScope, so an entity head only
        // sees appeals against their own entity's auctions (SUPER_ADMIN: all).
        $appeals = Appeal::whereHas('auction')
            ->with(['user', 'auction'])
            ->latest()
            ->paginate(20);

        return view('admin.appeals.index', compact('appeals'));
    }

    public function respond(Request $request, Appeal $appeal, NotificationService $notifications): RedirectResponse
    {
        $this->authorize('respond', $appeal);

        $request->validate([
            'admin_response' => ['required', 'string', 'max:2000'],
            // §4 step 10 — full set of transitions, not just the two terminals.
            'status' => ['required', 'in:UNDER_REVIEW,RESOLVED,REJECTED,ESCALATED'],
        ]);

        $status = AppealStatus::from($request->status);
        // Only terminal decisions stamp resolved_at; review/escalation keep it open.
        $isTerminal = in_array($status, [AppealStatus::RESOLVED, AppealStatus::REJECTED], true);

        $appeal->update([
            'admin_response' => $request->admin_response,
            'status' => $status,
            'resolved_at' => $isTerminal ? now() : null,
        ]);

        AuditLog::log('APPEAL_RESPONDED', 'Appeal', $appeal->id, null, null, [
            'status' => $request->status,
        ]);

        $notifications->appealUpdated($appeal->fresh());

        return back()->with('success', __('appeals.flash_responded'));
    }
}
