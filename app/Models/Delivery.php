<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Physical hand-over of a won asset to its winner (spec §4 step 9).
 */
class Delivery extends Model
{
    use HasUuids;

    protected $fillable = [
        'auction_id', 'user_id', 'scheduled_at', 'delivered_at',
        'status', 'address', 'notes', 'report_document_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'scheduled_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'report_document_id');
    }
}
