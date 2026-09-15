<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A citizen's Premium subscription purchase (client edits 24-25). */
class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'subscription_plan_id', 'status', 'price',
        'started_at', 'expires_at', 'auto_renew', 'cancelled_at',
        'expiry_reminded_at', 'payment_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'price' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'auto_renew' => 'boolean',
            'cancelled_at' => 'datetime',
            'expiry_reminded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /** Whole days left in the paid period (server clock), 0 once over. */
    public function daysRemaining(): int
    {
        if (! $this->expires_at || ! $this->expires_at->isFuture()) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($this->expires_at) / 86400);
    }
}
