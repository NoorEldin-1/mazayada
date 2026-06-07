<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InspectionQuestionStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InspectionQuestion;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff answering of bidder inspection questions (spec §4 step 4). Scoped to the
 * staff member's entity through the auction's EntityScope.
 */
class AdminInspectionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inspections.answer');

        // whereHas('auction') inherits the admin EntityScope, so staff only see
        // questions on their own entity's auctions.
        $query = InspectionQuestion::with(['auction', 'user'])
            ->whereHas('auction');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', InspectionQuestionStatus::PENDING);
        }

        $questions = $query->latest()->paginate(20)->withQueryString();

        return view('admin.inspections.index', compact('questions'));
    }

    public function answer(Request $request, InspectionQuestion $question, NotificationService $notifications): RedirectResponse
    {
        $this->authorize('inspections.answer');

        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:2000'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $question->update([
            'answer' => $validated['answer'],
            'answered_by' => auth()->id(),
            'status' => InspectionQuestionStatus::ANSWERED,
            'is_public' => $request->boolean('is_public', true),
        ]);

        AuditLog::log('INSPECTION_ANSWERED', 'InspectionQuestion', $question->id);
        $notifications->inspectionAnswered($question->fresh());

        return back()->with('success', __('inspections.flash_answered'));
    }

    public function reject(InspectionQuestion $question): RedirectResponse
    {
        $this->authorize('inspections.answer');

        $question->update([
            'status' => InspectionQuestionStatus::REJECTED,
            'answered_by' => auth()->id(),
        ]);

        AuditLog::log('INSPECTION_REJECTED', 'InspectionQuestion', $question->id);

        return back()->with('success', __('inspections.flash_rejected'));
    }
}
