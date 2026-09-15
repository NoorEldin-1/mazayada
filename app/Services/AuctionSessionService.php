<?php

namespace App\Services;

use App\Enums\AuctionStatus;
use App\Enums\DocumentType;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Re-scheduling an auction session (client edits 6-10).
 *
 * A reschedule never rewinds the finished session — its bids, participants and
 * documents stay as the historical record. Instead a NEW session row is created
 * from it: same asset and terms, a new time window, round + 1, and an opening
 * price reduced by the given percentage of the PREVIOUS session's price (so the
 * reduction compounds on every reschedule — edit 9). The chain is linked through
 * parent_auction_id / root_auction_id, which is what the session history reads.
 */
class AuctionSessionService
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly NewAuctionAlertService $alerts,
    ) {}

    /** Default reduction applied on reschedule (admin may override per reschedule). */
    public function defaultReductionPercent(): float
    {
        return (float) setting('auctions.reschedule_reduction_percent', 10);
    }

    /**
     * A session can be re-run once it finished WITHOUT an award (closed with no
     * winning bid, or cancelled) and only from the latest session of its chain.
     */
    public function canReschedule(Auction $auction): bool
    {
        $finishedWithoutAward = ($auction->status === AuctionStatus::CLOSED && $auction->winner_user_id === null)
            || $auction->status === AuctionStatus::CANCELLED;

        return $finishedWithoutAward && ! $this->hasNextSession($auction);
    }

    public function hasNextSession(Auction $auction): bool
    {
        return Auction::withoutGlobalScopes()->where('parent_auction_id', $auction->id)->exists();
    }

    /** New opening price (centimes) after reducing $price by $percent, rounded to a whole dinar. */
    public function reducedPrice(int $priceCentimes, float $percent): int
    {
        return (int) (round($priceCentimes * (1 - $percent / 100) / 100) * 100);
    }

    /**
     * @throws RuntimeException with a translated message when the session can't be re-run
     */
    public function reschedule(
        Auction $previous,
        Carbon $startTime,
        Carbon $endTime,
        ?float $reductionPercent,
        bool $publish,
        ?User $actor = null,
    ): Auction {
        // Reload so DB defaults (session_round, deposit_percent, …) are present
        // even on an instance that was just created in memory.
        $previous->refresh();

        if (! $this->canReschedule($previous)) {
            throw new RuntimeException(__('auctions.session.cannot_reschedule'));
        }

        if ($startTime->isPast() || $endTime->lessThanOrEqualTo($startTime)) {
            throw new RuntimeException(__('auctions.session.invalid_window'));
        }

        $percent = $reductionPercent ?? $this->defaultReductionPercent();
        if ($percent < 0 || $percent > 90) {
            throw new RuntimeException(__('auctions.session.invalid_reduction'));
        }

        $newPrice = $this->reducedPrice((int) $previous->opening_price, $percent);

        $session = DB::transaction(function () use ($previous, $startTime, $endTime, $percent, $newPrice, $publish, $actor) {
            /** @var Auction $session */
            $session = $previous->replicate([
                'session_code', 'status', 'winner_user_id', 'final_price', 'closed_at', 'settled_at',
                'extension_count', 'premium_alerted_at', 'public_alerted_at',
            ]);

            $session->fill([
                'session_round' => (int) $previous->session_round + 1,
                'parent_auction_id' => $previous->id,
                'root_auction_id' => $previous->root_auction_id ?? $previous->id,
                'reduction_percent' => $percent,
                'original_opening_price' => $previous->original_opening_price ?? $previous->opening_price,
                'opening_price' => $newPrice,
                'deposit_amount' => (int) round($newPrice * (float) $previous->deposit_percent / 100),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'extension_count' => 0,
                'status' => $publish ? AuctionStatus::PUBLISHED : AuctionStatus::DRAFT,
                'created_by' => $actor?->id ?? $previous->created_by,
            ]);

            // An inspection window that already lies in the past is meaningless
            // for the new session; the admin can set a fresh one on the draft.
            if ($previous->inspection_end && $previous->inspection_end->isPast()) {
                $session->inspection_start = null;
                $session->inspection_end = null;
            }

            $session->save();

            AuditLog::log('AUCTION_RESCHEDULED', 'Auction', $session->id, $actor?->id, $actor?->role?->value, [
                'previous_session' => $previous->id,
                'round' => $session->session_round,
                'reduction_percent' => $percent,
                'opening_price' => $newPrice,
            ]);

            return $session;
        });

        $this->copyMedia($previous, $session);

        // The new session sells its own condition book; re-issue it when the
        // previous session had one so citizens are not left without the terms.
        if ($previous->documents()->where('type', DocumentType::CONDITION_BOOK)->exists()) {
            try {
                $this->documents->generateConditionBook($session);
            } catch (\Throwable $e) {
                Log::warning('Condition book re-issue failed on reschedule', ['auction_id' => $session->id, 'error' => $e->getMessage()]);
            }
        }

        if ($publish) {
            $this->alerts->dispatchPremiumWave($session);
        }

        return $session->refresh();
    }

    /**
     * Copy the asset photos/video under the new session's own directory so that
     * deleting either session later (AuctionMediaService::purge) never strips
     * the other one's gallery.
     */
    private function copyMedia(Auction $previous, Auction $session): void
    {
        try {
            $disk = Storage::disk('public');
            $from = 'auctions/'.$previous->id.'/';
            $to = 'auctions/'.$session->id.'/';

            $move = function (?string $path) use ($disk, $from, $to): ?string {
                if (! $path) {
                    return $path;
                }
                $target = Str::startsWith($path, $from) ? $to.Str::after($path, $from) : $path;
                if ($target !== $path && $disk->exists($path)) {
                    $disk->copy($path, $target);
                }

                return $target !== $path && $disk->exists($target) ? $target : $path;
            };

            $photos = array_map($move, $previous->photosArray());

            $session->forceFill([
                'photos' => $photos ? implode(';', $photos) : $previous->photos,
                'video' => $move($previous->video),
            ])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Media copy failed on reschedule — sharing the previous files', [
                'auction_id' => $session->id, 'error' => $e->getMessage(),
            ]);
        }
    }
}
