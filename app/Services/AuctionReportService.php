<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionReport;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Issues and refers auction reports (تقارير المزادات).
 *
 * generate() freezes the auction's latest details into a signed PDF (via
 * DocumentService) and records an AuctionReport row with a per-auction sequence
 * number. refer() is the admin→entity handoff — it stamps referred_to_entity_at
 * so the organising entity can finally see the report, mirroring the appeals
 * forward step, and notifies the entity's institutional account.
 */
class AuctionReportService
{
    public function __construct(
        private DocumentService $documents,
        private NotificationService $notifications,
    ) {}

    /**
     * Issue a fresh report capturing the auction AS OF NOW. Any number may be
     * issued per auction; each gets the next sequence number.
     */
    public function generate(Auction $auction, User $actor): AuctionReport
    {
        return DB::transaction(function () use ($auction, $actor) {
            $sequenceNo = (int) AuctionReport::where('auction_id', $auction->id)->max('sequence_no') + 1;

            $document = $this->documents->generateAuctionReport($auction, $sequenceNo);

            $report = AuctionReport::create([
                'auction_id' => $auction->id,
                'document_id' => $document->id,
                'sequence_no' => $sequenceNo,
                'generated_by' => $actor->id,
                'snapshot' => [
                    'status' => $auction->status->value,
                    'title' => $auction->localizedTitle(),
                    'current_price' => $auction->currentPrice(),
                    'final_price' => $auction->final_price !== null ? (int) $auction->final_price : null,
                    'bid_count' => $auction->bidCount(),
                ],
            ]);

            AuditLog::log('AUCTION_REPORT_GENERATED', 'AuctionReport', $report->id, null, null, [
                'auction_id' => $auction->id,
                'sequence_no' => $sequenceNo,
                'document_id' => $document->id,
            ]);

            return $report;
        });
    }

    /**
     * Refer a report to its auction's organising entity, making it visible in the
     * entity's reports module. Idempotent — re-referring keeps the first stamp.
     */
    public function refer(AuctionReport $report, User $admin): void
    {
        if ($report->isReferred()) {
            return;
        }

        $report->update([
            'referred_to_entity_at' => now(),
            'referred_by' => $admin->id,
        ]);

        AuditLog::log('AUCTION_REPORT_REFERRED', 'AuctionReport', $report->id, null, null, [
            'auction_id' => $report->auction_id,
        ]);

        $this->notifications->auctionReportReferred($report->fresh('auction'));
    }
}
