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
        'status', 'admin_response', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
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
}
