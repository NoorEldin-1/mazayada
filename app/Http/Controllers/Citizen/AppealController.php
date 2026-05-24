<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppealController extends Controller
{
    public function index(): View
    {
        $appeals = auth()->user()->appeals()
            ->with('auction')
            ->latest()
            ->paginate(15);

        return view('citizen.appeals', compact('appeals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'auction_id' => ['nullable', 'exists:auctions,id'],
        ]);

        $appeal = Appeal::create([
            'user_id' => auth()->id(),
            'auction_id' => $request->auction_id,
            'subject' => $request->subject,
            'reason' => $request->reason,
            'status' => AppealStatus::SUBMITTED,
        ]);

        AuditLog::log('APPEAL_CREATED', 'Appeal', $appeal->id, null, null, [
            'subject' => $request->subject,
        ]);

        return back()->with('success', 'تم تقديم الطعن بنجاح.');
    }
}
