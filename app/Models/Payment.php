<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'user_id', 'auction_id', 'payment_type', 'amount',
        'status', 'transaction_ref', 'receipt_path',
        'gateway', 'gateway_ref', 'gateway_payload', 'payable_meta',
        'confirmed_at', 'due_at', 'refunded_at', 'failed_at', 'forfeited_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_type' => PaymentType::class,
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
            'payable_meta' => 'array',
            'confirmed_at' => 'datetime',
            'due_at' => 'datetime',
            'refunded_at' => 'datetime',
            'failed_at' => 'datetime',
            'forfeited_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payment');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }
}
