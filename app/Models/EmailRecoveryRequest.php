<?php

namespace App\Models;

use App\Enums\EmailRecoveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A citizen's "lost my email" request (see the migration for the field list).
 * Created by EmailRecoveryService::submit and decided by an admin; approving it
 * replaces the account's email with new_email.
 */
class EmailRecoveryRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'nin', 'birth_date', 'phone', 'old_email', 'new_email',
        'selfie_with_id_path',
        'status', 'rejection_reason',
        'submitted_at', 'review_started_at', 'reviewed_at', 'reviewed_by',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailRecoveryStatus::class,
            'birth_date' => 'date',
            'submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Still awaiting a decision (PENDING or UNDER_REVIEW). */
    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
