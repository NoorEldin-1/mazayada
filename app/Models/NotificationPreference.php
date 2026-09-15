<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A citizen's notification preferences (client edits 27-30). Absent row = the
 * defaults below (push on, email/SMS off, alerts on, every category).
 */
class NotificationPreference extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'push_enabled', 'email_enabled', 'sms_enabled',
        'new_auction_alerts', 'auction_categories',
    ];

    protected $attributes = [
        'push_enabled' => true,
        'email_enabled' => false,
        'sms_enabled' => false,
        'new_auction_alerts' => true,
    ];

    protected function casts(): array
    {
        return [
            'push_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'new_auction_alerts' => 'boolean',
            'auction_categories' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<int, int> */
    public function categoryIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) ($this->auction_categories ?? []))));
    }

    /** Whether an auction of $categoryId matches (empty list = all categories). */
    public function followsCategory(?int $categoryId): bool
    {
        $ids = $this->categoryIds();

        return $ids === [] || ($categoryId !== null && in_array($categoryId, $ids, true));
    }
}
