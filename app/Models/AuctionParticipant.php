<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionParticipant extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'auction_id', 'user_id', 'is_original_owner',
        'deposit_paid', 'entry_fee_paid', 'book_purchased',
        'condition_book_acknowledged_at', 'blacklisted_for_default', 'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_original_owner' => 'boolean',
            'deposit_paid' => 'boolean',
            'entry_fee_paid' => 'boolean',
            'book_purchased' => 'boolean',
            'blacklisted_for_default' => 'boolean',
            'condition_book_acknowledged_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    /**
     * Fully registered = the participation deposit is confirmed. The condition
     * book is bought BEFORE registering (a prerequisite, not part of this), and
     * the legacy entry fee was removed from the flow.
     */
    public function isFullyRegistered(): bool
    {
        return (bool) $this->deposit_paid;
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
