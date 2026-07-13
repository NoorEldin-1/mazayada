<?php

namespace App\Models;

use App\Enums\CommercialRegisterStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's Commercial Register (السجل التجاري) submission. See the migration for
 * the field list. Editable only while PENDING has not yet been decided or after
 * a REJECTED decision (canSubmit) — an approved record is locked.
 */
class CommercialRegister extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'company_name', 'register_number', 'tax_number', 'activity_type', 'start_date',
        'register_document_path', 'tax_card_document_path',
        'status', 'rejection_reason', 'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommercialRegisterStatus::class,
            'start_date' => 'date',
            'submitted_at' => 'datetime',
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

    /** The user may (re)submit while there is no decision to preserve — PENDING or REJECTED. */
    public function canSubmit(): bool
    {
        return in_array($this->status, [
            CommercialRegisterStatus::PENDING,
            CommercialRegisterStatus::REJECTED,
        ], true);
    }

    public function isApproved(): bool
    {
        return $this->status === CommercialRegisterStatus::APPROVED;
    }

    /** An admin-approved register — the state that unlocks bidding. */
    public function isValid(): bool
    {
        return $this->isApproved();
    }
}
