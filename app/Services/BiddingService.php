<?php

namespace App\Services;

use App\Enums\AuctionStatus;
use App\Events\AuctionExtended;
use App\Events\BidPlaced;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BiddingService
{
    public function __construct(
        private readonly BidderAliasService $aliases,
    ) {
    }

    /**
     * Place a bid atomically.
     *
     * Concurrency strategy:
     *  - A short-lived cache lock per-auction prevents two bids hitting the DB
     *    transaction simultaneously and racing for the row lock.
     *  - Inside the lock we use DB row-level locking (lockForUpdate) as a
     *    second line of defense (and for replicas).
     *
     * @throws RuntimeException on any business-rule violation.
     */
    public function placeBid(
        Auction $auction,
        User $user,
        int $amountCentimes,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Bid {
        if ($amountCentimes <= 0) {
            throw new RuntimeException(__('المبلغ غير صالح.'));
        }

        if (! $user->canBid()) {
            throw new RuntimeException(__('لا تستوفي شروط المزايدة (KYC أو الحالة).'));
        }

        $rateKey = "bid_rate:{$user->id}:{$auction->id}";
        $maxPerMinute = (int) config('mazayada.bidding.max_per_minute', 10);
        $attempts = (int) Cache::get($rateKey, 0);
        if ($attempts >= $maxPerMinute) {
            throw new RuntimeException(__('تجاوزت الحد الأقصى للمزايدات (:max في الدقيقة).', ['max' => $maxPerMinute]));
        }

        $lock = Cache::lock("auction:{$auction->id}:bid", 3);

        try {
            $bid = $lock->block(2, function () use ($auction, $user, $amountCentimes, $ip, $userAgent) {
                return DB::transaction(function () use ($auction, $user, $amountCentimes, $ip, $userAgent) {
                    /** @var Auction $freshAuction */
                    $freshAuction = Auction::query()->lockForUpdate()->find($auction->id);
                    if (! $freshAuction) {
                        throw (new ModelNotFoundException)->setModel(Auction::class, [$auction->id]);
                    }

                    if (! in_array($freshAuction->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED], true)) {
                        throw new RuntimeException(__('المزايدة ليست نشطة حالياً.'));
                    }

                    if (now()->greaterThan($freshAuction->end_time)) {
                        throw new RuntimeException(__('انتهت مدة المزايدة.'));
                    }

                    $participant = $freshAuction->participants()
                        ->where('user_id', $user->id)
                        ->where('deposit_paid', true)
                        ->first();

                    if (! $participant) {
                        throw new RuntimeException(__('يجب التسجيل ودفع الكفالة أولاً.'));
                    }

                    $currentPrice = (int) ($freshAuction->bids()
                        ->where('is_valid', true)
                        ->max('amount') ?? $freshAuction->opening_price);

                    if ($amountCentimes <= $currentPrice) {
                        throw new RuntimeException(__('المبلغ يجب أن يكون أعلى من السعر الحالي.'));
                    }

                    $bid = Bid::create([
                        'auction_id' => $freshAuction->id,
                        'user_id' => $user->id,
                        'amount' => $amountCentimes,
                        'bid_time' => now(),
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                        'is_valid' => true,
                    ]);

                    $secondsRemaining = now()->diffInSeconds($freshAuction->end_time, false);
                    if ($secondsRemaining <= $freshAuction->extension_trigger_seconds && $secondsRemaining > 0) {
                        $freshAuction->update([
                            'end_time' => $freshAuction->end_time->copy()->addMinutes($freshAuction->extension_duration_minutes),
                            'status' => AuctionStatus::EXTENDED,
                        ]);
                        AuctionExtended::dispatch($freshAuction->fresh());
                    }

                    AuditLog::log('BID_PLACED', 'auction', $freshAuction->id, $user->id, $user->role?->value, [
                        'amount' => $amountCentimes,
                        'bid_id' => $bid->id,
                    ], $ip);

                    return $bid;
                });
            });
        } finally {
            optional($lock)->release();
        }

        if (! $bid) {
            throw new RuntimeException(__('فشل في تسجيل المزايدة، حاول مرة أخرى.'));
        }

        Cache::put($rateKey, $attempts + 1, 60);

        // Invalidate cached current price for the auction.
        Cache::forget("auction:{$auction->id}:current_price");

        BidPlaced::dispatch($bid, $this->aliases->aliasFor($user->id, $auction->id));

        return $bid;
    }
}
