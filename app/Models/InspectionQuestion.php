<?php

namespace App\Models;

use App\Enums\InspectionQuestionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A bidder's written question during the inspection window (spec §4 step 4).
 * Answered questions are surfaced publicly on the auction page.
 */
class InspectionQuestion extends Model
{
    use HasUuids;

    protected $fillable = [
        'auction_id', 'user_id', 'question', 'answer',
        'answered_by', 'status', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'status' => InspectionQuestionStatus::class,
            'is_public' => 'boolean',
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

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
