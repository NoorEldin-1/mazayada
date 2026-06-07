<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Delivery;

/**
 * Schedules and records the physical hand-over of a won asset (spec §4 step 9).
 * Marking a delivery as delivered generates the signed delivery report.
 */
class DeliveryService
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array{scheduled_at?: string, address?: string, notes?: string}  $data
     */
    public function schedule(Auction $auction, array $data, ?string $createdBy = null): Delivery
    {
        $delivery = Delivery::updateOrCreate(
            ['auction_id' => $auction->id],
            [
                'user_id' => $auction->winner_user_id,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'address' => $data['address'] ?? $auction->asset_location,
                'notes' => $data['notes'] ?? null,
                'status' => DeliveryStatus::SCHEDULED,
                'created_by' => $createdBy,
            ],
        );

        AuditLog::log('DELIVERY_SCHEDULED', 'Auction', $auction->id, $createdBy);
        $this->notifications->deliveryUpdate($delivery);

        return $delivery;
    }

    public function markDelivered(Delivery $delivery): Delivery
    {
        $delivery->update([
            'status' => DeliveryStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        // Generate the signed delivery report (محضر التسليم) and link it.
        $report = $this->documents->generateDeliveryReport($delivery);
        $delivery->update(['report_document_id' => $report->id]);

        AuditLog::log('DELIVERY_COMPLETED', 'Auction', $delivery->auction_id);
        $this->notifications->deliveryUpdate($delivery->fresh());

        return $delivery;
    }

    public function updateStatus(Delivery $delivery, DeliveryStatus $status): Delivery
    {
        $delivery->update(['status' => $status]);
        AuditLog::log('DELIVERY_STATUS_CHANGED', 'Auction', $delivery->auction_id, null, null, [
            'status' => $status->value,
        ]);
        $this->notifications->deliveryUpdate($delivery->fresh());

        return $delivery;
    }
}
