<?php

namespace App\Console\Commands;

use App\Enums\KycStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\KycStatusNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Suspends accounts that registered but never submitted their KYC within the
 * grace window (spec §3.3: PENDING > 30 days → account suspended). Accounts
 * already UNDER_REVIEW are exempt — they did their part and are waiting on us.
 */
class SuspendStaleKyc extends Command
{
    protected $signature = 'kyc:suspend-stale';
    protected $description = 'Suspend accounts whose KYC has stayed PENDING beyond the grace period';

    public function handle(): void
    {
        $graceDays = (int) config('mazayada.kyc.pending_grace_days', 30);
        $cutoff = now()->subDays($graceDays);

        $users = User::where('kyc_status', KycStatus::PENDING)
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($users as $user) {
            $user->update(['kyc_status' => KycStatus::SUSPENDED]);

            AuditLog::log('KYC_SUSPENDED', 'User', $user->id, null, null, [
                'reason' => 'kyc_grace_expired',
                'grace_days' => $graceDays,
            ]);

            $locale = $user->preferredLocale();
            UserNotification::record(
                $user->id,
                __('kyc.notif_suspended_title', [], $locale),
                __('kyc.notif_suspended_body', [], $locale),
                route('citizen.kyc'),
            );

            try {
                $user->notify(new KycStatusNotification('suspended'));
            } catch (\Throwable $e) {
                Log::error('KYC suspension email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            $this->info("Suspended stale KYC: {$user->id}");
        }

        $this->info("Suspended {$users->count()} account(s) for stale KYC.");
    }
}
