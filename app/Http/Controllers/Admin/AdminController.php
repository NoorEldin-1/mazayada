<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wilaya;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        // Auction-derived figures inherit per-entity isolation automatically
        // (Auction's EntityScope, active in the admin area). Payment/Bid figures
        // do so transitively via whereHas('auction').
        $stats = [
            'total_users' => User::count(),
            'pending_kyc' => User::where('kyc_status', KycStatus::UNDER_REVIEW)->count(),
            'active_auctions' => Auction::active()->count(),
            'total_bids' => Bid::whereHas('auction')->count(),
            'revenue' => (int) Payment::where('status', PaymentStatus::CONFIRMED)
                ->whereHas('auction')
                ->sum('amount'),
            'active_bidders' => Bid::whereHas('auction', fn ($q) => $q->active())
                ->distinct('user_id')
                ->count('user_id'),
        ];

        // Auction distribution by wilaya (top 8) — also entity-scoped.
        $byWilaya = Auction::query()
            ->selectRaw('wilaya_id, COUNT(*) as total')
            ->whereNotNull('wilaya_id')
            ->groupBy('wilaya_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $wilayaNames = Wilaya::whereIn('id', $byWilaya->pluck('wilaya_id'))->get()->keyBy('id');
        $wilayaDistribution = $byWilaya->map(fn ($row) => [
            'name' => $wilayaNames[$row->wilaya_id]->name ?? '—',
            'total' => (int) $row->total,
        ]);

        return view('admin.dashboard', compact('stats', 'wilayaDistribution'));
    }

    public function auditLogs(): View
    {
        $this->authorize('system.auditlogs.view');

        $logs = AuditLog::latest('created_at')->paginate(20);

        return view('admin.audit-logs', compact('logs'));
    }
}
