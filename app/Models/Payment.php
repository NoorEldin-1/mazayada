<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'auction_id', 'payment_type', 'amount',
        'status', 'transaction_ref', 'receipt_path',
        'confirmed_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_type' => PaymentType::class,
            'status' => PaymentStatus::class,
            'confirmed_at' => 'datetime',
            'refunded_at' => 'datetime',
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
}
