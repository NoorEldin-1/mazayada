<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAppealController extends Controller
{
    public function index(): View
    {
        $appeals = Appeal::with(['user', 'auction'])
            ->latest()
            ->paginate(20);

        return view('admin.appeals.index', compact('appeals'));
    }

    public function respond(Request $request, Appeal $appeal): RedirectResponse
    {
        $request->validate([
            'admin_response' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:RESOLVED,REJECTED'],
        ]);

        $appeal->update([
            'admin_response' => $request->admin_response,
            'status' => AppealStatus::from($request->status),
            'resolved_at' => now(),
        ]);

        AuditLog::log('APPEAL_RESPONDED', 'Appeal', $appeal->id, null, null, [
            'status' => $request->status,
        ]);

        return back()->with('success', __('appeals.flash_responded'));
    }
}
