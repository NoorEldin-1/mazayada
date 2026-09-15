<?php

namespace App\Services;

use App\Enums\AuctionStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\NewAuctionMatchNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * New-auction alerts (client edits 26 · 28 · 29), sent in two waves:
 *
 *   1. premium wave — Premium subscribers, the moment the auction is published;
 *   2. public wave  — everyone else, `subscriptions.non_premium_alert_delay_minutes`
 *                     later (run by the auctions:dispatch-alerts scheduler).
 *
 * Recipients: citizens whose preferences keep new_auction_alerts on and whose
 * followed categories are empty (= all) or include the auction's category.
 * Channels: in-app always; push when enabled; email when enabled AND (the user
 * is Premium OR `subscriptions.email_requires_premium` is off).
 *
 * The waves are idempotent: premium_alerted_at / public_alerted_at are stamped
 * atomically before sending, so a re-run never double-notifies.
 */
class NewAuctionAlertService
{
    public function __construct(private readonly NotificationPreferenceService $preferences) {}

    public function publicWaveDelayMinutes(): int
    {
        return max(0, (int) setting('subscriptions.non_premium_alert_delay_minutes', 30));
    }

    public function dispatchPremiumWave(Auction $auction): int
    {
        if (! $this->claim($auction, 'premium_alerted_at')) {
            return 0;
        }

        return $this->send($auction, premium: true);
    }

    public function dispatchPublicWave(Auction $auction): int
    {
        if (! $this->claim($auction, 'public_alerted_at')) {
            return 0;
        }

        return $this->send($auction, premium: false);
    }

    /**
     * Scheduler entry point: send any wave that is due.
     *
     * @return array{premium: int, public: int}
     */
    public function dispatchDue(): array
    {
        $counts = ['premium' => 0, 'public' => 0];
        $statuses = [AuctionStatus::PUBLISHED, AuctionStatus::ACTIVE, AuctionStatus::EXTENDED];

        Auction::withoutGlobalScopes()->whereIn('status', $statuses)->whereNull('premium_alerted_at')
            ->get()->each(function (Auction $a) use (&$counts) {
                $counts['premium'] += $this->dispatchPremiumWave($a);
            });

        Auction::withoutGlobalScopes()->whereIn('status', $statuses)
            ->whereNotNull('premium_alerted_at')->whereNull('public_alerted_at')
            ->where('premium_alerted_at', '<=', now()->subMinutes($this->publicWaveDelayMinutes()))
            ->get()->each(function (Auction $a) use (&$counts) {
                $counts['public'] += $this->dispatchPublicWave($a);
            });

        return $counts;
    }

    /** Atomically stamp a wave column; false when another run already did. */
    private function claim(Auction $auction, string $column): bool
    {
        $claimed = Auction::withoutGlobalScopes()
            ->whereKey($auction->id)
            ->whereNull($column)
            ->update([$column => now()]) === 1;

        if ($claimed) {
            $auction->{$column} = now();
        }

        return $claimed;
    }

    private function send(Auction $auction, bool $premium): int
    {
        $sent = 0;

        $this->recipients($premium)->chunkById(200, function ($users) use ($auction, &$sent) {
            $prefs = NotificationPreference::whereIn('user_id', $users->pluck('id'))->get()->keyBy('user_id');

            foreach ($users as $user) {
                /** @var User $user */
                $pref = $prefs->get($user->id) ?? new NotificationPreference(['user_id' => $user->id]);

                if (! $pref->new_auction_alerts || ! $pref->followsCategory($auction->category_id)) {
                    continue;
                }

                $channels = [];
                if ($pref->push_enabled) {
                    $channels[] = 'push';
                }
                if ($pref->email_enabled && $this->preferences->emailAllowedFor($user)) {
                    $channels[] = 'mail';
                }

                try {
                    $user->notify(new NewAuctionMatchNotification($auction, $channels));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('New auction alert failed', ['user_id' => $user->id, 'auction_id' => $auction->id, 'error' => $e->getMessage()]);
                }
            }
        }, 'id');

        return $sent;
    }

    /** Citizens (never staff) split by current Premium status. */
    private function recipients(bool $premium): Builder
    {
        return User::query()
            ->whereNull('entity_id')
            ->whereIn('role', [UserRole::CITIZEN->value, UserRole::PREMIUM_CITIZEN->value])
            ->where('is_blacklisted', false)
            ->when($premium,
                fn (Builder $q) => $q->where('premium_until', '>', now()),
                fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('premium_until')->orWhere('premium_until', '<=', now())));
    }
}
