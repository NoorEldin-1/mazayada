<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminKycController extends Controller
{
    public function pending(): View
    {
        $users = User::where('kyc_status', KycStatus::PENDING)
            ->with('biometrics')
            ->latest()
            ->paginate(20);

        return view('admin.kyc.index', compact('users'));
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update([
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
        ]);

        AuditLog::log('KYC_APPROVED', 'User', $user->id);

        return back()->with('success', 'تم قبول التوثيق بنجاح.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'kyc_status' => KycStatus::SUSPENDED,
        ]);

        AuditLog::log('KYC_REJECTED', 'User', $user->id, null, null, [
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'تم رفض التوثيق.');
    }
}
