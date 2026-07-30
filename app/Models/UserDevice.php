<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A mobile device registered to receive push notifications.
 *
 * The token is the identity: `register()` claims it for the current user, so a
 * device handed over to another account stops receiving the previous owner's
 * notifications. Stale tokens are pruned by the sender when the push provider
 * reports them as unregistered.
 */
class UserDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'token', 'platform', 'locale', 'app_version', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Claim a device token for a user, creating it or re-pointing an existing
     * row. Idempotent — the app may call this on every launch.
     */
    public static function register(User $user, string $token, string $platform, ?string $locale = null, ?string $appVersion = null): self
    {
        return self::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'locale' => $locale,
                'app_version' => $appVersion,
                'last_seen_at' => now(),
            ],
        );
    }
}
