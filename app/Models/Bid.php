<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'auction_id', 'user_id', 'amount', 'bid_time',
        'ip_address', 'user_agent', 'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'bid_time' => 'datetime',
            'is_valid' => 'boolean',
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

    public function bidderAlias(): string
    {
        return 'Bidder_' . substr($this->user_id, 0, 4);
    }
}
