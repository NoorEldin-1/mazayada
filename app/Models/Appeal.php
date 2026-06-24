<?php

namespace App\Models;

use App\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appeal extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'auction_id', 'subject', 'reason',
        'status', 'admin_response', 'entity_decision', 'entity_response',
        'forwarded_by', 'resolved_by',
        'forwarded_at', 'entity_decided_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'entity_decision' => AppealStatus::class,
            'forwarded_at' => 'datetime',
            'entity_decided_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** Platform admin who forwarded the appeal to the entity. */
    public function forwardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }

    /** Platform admin who confirmed the final decision. */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
