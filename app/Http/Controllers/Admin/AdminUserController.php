<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $this->authorize('users.viewAny');

        $users = User::latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function blacklisted(): View
    {
        $this->authorize('users.viewAny');

        $users = User::where('is_blacklisted', true)->latest('updated_at')->paginate(20);

        return view('admin.users.blacklisted', compact('users'));
    }

    public function show(User $user): View
    {
        $this->authorize('users.viewAny');

        $user->load('biometrics', 'commune.wilaya')
            ->loadCount(['participations', 'bids', 'wonAuctions']);

        return view('admin.users.show', compact('user'));
    }

    public function blacklist(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.blacklist');

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $request->reason,
        ]);

        // Kill any live sessions immediately — a blacklisted user must lose
        // access now, not at next login (spec §8.4).
        invalidate_user_sessions($user->id);

        AuditLog::log('USER_BLACKLISTED', 'User', $user->id, null, null, [
            'reason' => $request->reason,
        ]);

        return back()->with('success', __('admin.flash.user_blacklisted'));
    }

    public function unblacklist(User $user): RedirectResponse
    {
        $this->authorize('users.blacklist');

        $user->update([
            'is_blacklisted' => false,
            'blacklist_reason' => null,
        ]);

        AuditLog::log('USER_UNBLACKLISTED', 'User', $user->id);

        return back()->with('success', __('admin.flash.user_unblacklisted'));
    }
}
