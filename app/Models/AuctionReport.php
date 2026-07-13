<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One issued auction report (تقرير المزاد): a frozen snapshot of the auction's
 * latest details, backed by a signed, verifiable PDF (the linked Document).
 *
 * Visibility mirrors the appeals workflow — a report is invisible to the
 * organising entity until the platform admin refers it (referred_to_entity_at).
 * Per-entity isolation is transitive through the auction (EntityScope), so a
 * `whereHas('auction')` in queries scopes reports to the account's own entity.
 */
class AuctionReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'auction_id', 'document_id', 'sequence_no', 'generated_by',
        'snapshot', 'referred_to_entity_at', 'referred_by',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'snapshot' => 'array',
            'referred_to_entity_at' => 'datetime',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** The signed PDF this report points at (nullable if generation ever failed). */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** Staff member who issued the report. */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** Platform admin who referred the report to the organising entity. */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Has this report been referred to the organising entity? */
    public function isReferred(): bool
    {
        return $this->referred_to_entity_at !== null;
    }

    /** Only reports the platform has referred to their organising entity. */
    public function scopeReferred(Builder $query): Builder
    {
        return $query->whereNotNull('referred_to_entity_at');
    }
}
