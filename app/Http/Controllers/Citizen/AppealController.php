<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppealController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $appeals = $user->appeals()
            ->with('auction')
            ->latest()
            ->paginate(15);

        // Auctions the user has taken part in, for the optional reference
        // dropdown. Deduped, with any deleted/null auctions filtered out.
        $auctions = $user->participations()
            ->with('auction')
            ->get()
            ->pluck('auction')
            ->filter()
            ->unique('id')
            ->values();

        return view('citizen.appeals', compact('appeals', 'auctions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            // Only an auction the user actually participated in may be referenced.
            'auction_id' => [
                'nullable',
                Rule::exists('auction_participants', 'auction_id')
                    ->where('user_id', auth()->id()),
            ],
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

        return back()->with('success', __('appeals.flash_submitted'));
    }
}
