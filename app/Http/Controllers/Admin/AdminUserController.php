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
        $users = User::latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load('biometrics', 'commune.wilaya')
            ->loadCount(['participations', 'bids', 'wonAuctions']);

        return view('admin.users.show', compact('user'));
    }

    public function blacklist(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $request->reason,
        ]);

        AuditLog::log('USER_BLACKLISTED', 'User', $user->id, null, null, [
            'reason' => $request->reason,
        ]);

        return back()->with('success', __('admin.flash.user_blacklisted'));
    }

    public function unblacklist(User $user): RedirectResponse
    {
        $user->update([
            'is_blacklisted' => false,
            'blacklist_reason' => null,
        ]);

        AuditLog::log('USER_UNBLACKLISTED', 'User', $user->id);

        return back()->with('success', __('admin.flash.user_unblacklisted'));
    }
}
