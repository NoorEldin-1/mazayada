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
        'deposit_paid', 'entry_fee_paid', 'book_purchased', 'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_original_owner' => 'boolean',
            'deposit_paid' => 'boolean',
            'entry_fee_paid' => 'boolean',
            'book_purchased' => 'boolean',
            'registered_at' => 'datetime',
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
}
