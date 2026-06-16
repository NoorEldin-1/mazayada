<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Models\AuctionParticipant;
use App\Models\UserNotification;
use App\Rules\AlgerianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CitizenController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();

        // Stat tiles — cheap COUNT(*) aggregates.
        $activeCount = $user->participations()
            ->whereHas('auction', fn ($q) => $q->active())
            ->count();

        $wonCount = $user->wonAuctions()->count();

        $totalParticipations = $user->participations()->count();

        $kycStatus = $user->kyc_status;

        // Recent won auctions — iterated by the view, must be a Collection.
        $wonAuctions = $user->wonAuctions()
            ->with(['category', 'wilaya'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Compact summary for the dashboard panel; the full list lives on the
        // notifications page.
        $recentNotifications = $user->userNotifications()
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('citizen.dashboard', compact(
            'activeCount', 'wonCount', 'totalParticipations',
            'kycStatus', 'wonAuctions', 'recentNotifications'
        ));
    }

    public function myAuctions(Request $request): View
    {
        $user = auth()->user();

        // Drop rows whose auction is gone so the grouping closures below can
        // safely dereference $p->auction.
        $participations = $user->participations()
            ->with(['auction.category', 'auction.wilaya'])
            ->get()
            ->filter(fn ($p) => $p->auction !== null);

        $groups = [
            'active' => $participations->filter(fn ($p) => $p->auction->isLive()),
            'won' => $participations->filter(fn ($p) => $p->auction->winner_user_id === $user->id),
            'lost' => $participations->filter(
                fn ($p) => $p->auction->status === AuctionStatus::CLOSED
                    && $p->auction->winner_user_id !== $user->id
            ),
            'upcoming' => $participations->filter(fn ($p) => $p->auction->status === AuctionStatus::PUBLISHED),
        ];

        // Only honour a tab the page actually renders; anything else falls back.
        $tab = in_array($request->query('tab'), array_keys($groups), true)
            ? $request->query('tab')
            : 'active';

        $counts = array_map(fn ($group) => $group->count(), $groups);

        // The view renders Auction cards — hand it auctions, not participation rows.
        $auctions = $groups[$tab]->map(fn ($p) => $p->auction)->values();

        return view('citizen.my-auctions', compact('auctions', 'tab', 'counts'));
    }

    public function notifications(): View
    {
        $notifications = auth()->user()->userNotifications()
            ->latest('created_at')
            ->paginate(20);

        // Count all unread rows from the DB — not just the current page — so the
        // header badge matches the nav badge (both via unreadNotificationsCount()).
        $unreadCount = auth()->user()->unreadNotificationsCount();

        return view('citizen.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead(UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['is_read' => true]);

        return back()->with('success', __('notifications.flash_marked_read'));
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        auth()->user()->userNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', __('notifications.flash_all_marked_read'));
    }

    public function profile(): View
    {
        $user = auth()->user();

        return view('citizen.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Same strict rules as registration/KYC — profile edits must not be a
        // backdoor around them (spec §3.2): real Algerian phone, 5-digit postal.
        $request->validate([
            'phone' => ['sometimes', 'string', new AlgerianPhone, 'unique:users,phone,'.$user->id],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'commune_id' => ['sometimes', 'nullable', 'exists:communes,id'],
            'postal_code' => ['sometimes', 'nullable', 'regex:/^\d{5}$/'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Secret question/answer recovery (spec §8.4). Stored as a stable key;
            // the answer is hashed by the model cast.
            'secret_question' => ['sometimes', 'nullable', Rule::in(array_keys((array) __('auth.secret_questions')))],
            // A recovery question is useless without an answer: require one when a
            // question is selected and the account doesn't already have a stored answer.
            'secret_answer' => [
                Rule::requiredIf(fn () => $request->filled('secret_question') && ! $user->secret_answer),
                'nullable', 'string', 'min:2', 'max:200',
            ],
        ], [
            'secret_answer.required' => __('profile.secret_answer_required'),
        ]);

        $fields = $request->only([
            'phone', 'email', 'address', 'commune_id', 'postal_code', 'profession', 'secret_question',
        ]);

        // Only overwrite the stored answer when the user actually typed a new one.
        if ($request->filled('secret_answer')) {
            $fields['secret_answer'] = $request->input('secret_answer');
        }

        $user->update($fields);

        return back()->with('success', __('profile.flash_updated'));
    }
}
