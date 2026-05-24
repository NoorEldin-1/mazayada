<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_users' => User::count(),
            'pending_kyc' => User::where('kyc_status', KycStatus::PENDING)->count(),
            'active_auctions' => Auction::active()->count(),
            'total_bids' => Bid::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function auditLogs(): View
    {
        $logs = AuditLog::latest('created_at')->paginate(20);

        return view('admin.audit-logs', compact('logs'));
    }
}
