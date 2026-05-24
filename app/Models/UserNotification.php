<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'title', 'body', 'channel',
        'is_read', 'action_url', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
