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
}
