<?php

namespace App\Services;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BiddingService
{
    /**
     * Place a bid on an auction.
     *
     * @throws \Exception
     */
    public function placeBid(Auction $auction, User $user, int $amountCentimes, ?string $ip = null, ?string $userAgent = null): Bid
    {
        // Rate limiting: 10 bids per minute per user per auction
        $rateKey = "bid_rate:{$user->id}:{$auction->id}";
        $attempts = Cache::get($rateKey, 0);
        if ($attempts >= 10) {
            throw new \Exception('تجاوزت الحد الأقصى للمزايدات (10 في الدقيقة).');
        }

        return DB::transaction(function () use ($auction, $user, $amountCentimes, $ip, $userAgent, $rateKey, $attempts) {
            // Lock auction row
            $auction = Auction::lockForUpdate()->findOrFail($auction->id);

            // Validate auction status
            if (!in_array($auction->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED])) {
                throw new \Exception('المزايدة ليست نشطة حالياً.');
            }

            // Validate user is registered participant with deposit
            $participant = $auction->participants()
                ->where('user_id', $user->id)
                ->where('deposit_paid', true)
                ->first();

            if (!$participant) {
                throw new \Exception('يجب التسجيل ودفع الكفالة أولاً.');
            }

            // Validate amount
            $currentPrice = $auction->bids()->where('is_valid', true)->max('amount') ?? $auction->opening_price;
            if ($amountCentimes <= $currentPrice) {
                throw new \Exception('المبلغ يجب أن يكون أعلى من السعر الحالي (' . dzd($currentPrice) . ').');
            }

            // Create bid
            $bid = Bid::create([
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'amount' => $amountCentimes,
                'bid_time' => now(),
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'is_valid' => true,
            ]);

            // Auto-extension: if bid in last 30 seconds, extend by 5 minutes
            $secondsRemaining = now()->diffInSeconds($auction->end_time, false);
            if ($secondsRemaining <= $auction->extension_trigger_seconds && $secondsRemaining > 0) {
                $auction->update([
                    'end_time' => $auction->end_time->addMinutes($auction->extension_duration_minutes),
                    'status' => AuctionStatus::EXTENDED,
                ]);
            }

            // Update rate limiter
            Cache::put($rateKey, $attempts + 1, 60);

            // Audit log
            AuditLog::log('BID_PLACED', 'auction', $auction->id, $user->id, $user->role->value, [
                'amount' => $amountCentimes,
                'bid_id' => $bid->id,
            ], $ip);

            return $bid;
        });
    }
}
