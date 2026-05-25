<?php

namespace App\Models;

use App\Services\BidderAliasService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bid extends Model
{
    use HasUuids, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['auction_id', 'user_id', 'amount', 'is_valid'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bid');
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
        return app(BidderAliasService::class)->aliasFor($this->user_id, $this->auction_id);
    }
}
