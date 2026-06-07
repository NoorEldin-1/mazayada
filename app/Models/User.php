<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference
{
    use HasRoles, HasUuids, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'nin', 'id_card_number', 'passport_number', 'license_number',
        'first_name_ar', 'last_name_ar', 'first_name_fr', 'last_name_fr',
        'father_name', 'mother_fullname', 'birth_date', 'birth_place',
        'phone', 'email', 'address', 'commune_id', 'postal_code',
        'profession', 'nif', 'nis', 'rip', 'expected_income',
        'kyc_status', 'kyc_completed_at', 'kyc_submitted_at', 'kyc_rejection_reason',
        'is_blacklisted', 'blacklist_reason',
        'account_status', 'premium_until', 'secret_question', 'secret_answer',
        'password', 'role', 'entity_id', 'locale', 'phone_verified', 'email_verified',
        'failed_login_attempts', 'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'secret_answer',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'kyc_completed_at' => 'datetime',
            'kyc_submitted_at' => 'datetime',
            'premium_until' => 'datetime',
            'locked_until' => 'datetime',
            'is_blacklisted' => 'boolean',
            'phone_verified' => 'boolean',
            'email_verified' => 'boolean',
            'password' => 'hashed',
            'secret_answer' => 'hashed',
            'kyc_status' => KycStatus::class,
            'account_status' => AccountStatus::class,
            'role' => UserRole::class,
        ];
    }

    /**
     * Audit log: only track the security-sensitive and KYC fields.
     * Avoids logging password hashes or 2FA secrets even if accidentally fillable.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nin', 'email', 'phone', 'first_name_ar', 'last_name_ar',
                'kyc_status', 'kyc_submitted_at', 'kyc_rejection_reason',
                'account_status', 'is_blacklisted', 'blacklist_reason',
                'role', 'failed_login_attempts', 'locked_until',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    public function fullNameAr(): string
    {
        return trim($this->first_name_ar.' '.$this->last_name_ar);
    }

    public function fullNameFr(): string
    {
        return trim(($this->first_name_fr ?? '').' '.($this->last_name_fr ?? ''));
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * The government entity this account is bound to. NULL for citizens and the
     * SUPER_ADMIN (platform-wide). Non-null for entity staff — drives the
     * per-entity data isolation in the admin dashboard (see EntityScope).
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * The matching EntityUser membership row (management surface for staff).
     * The canonical login identity is always this User; EntityUser mirrors the
     * link for the staff-management UI.
     */
    public function entityMembership(): HasOne
    {
        return $this->hasOne(EntityUser::class);
    }

    public function biometrics(): HasOne
    {
        return $this->hasOne(UserBiometric::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(AuctionParticipant::class);
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function wonAuctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'winner_user_id');
    }

    public function watchlist(): BelongsToMany
    {
        return $this->belongsToMany(Auction::class, 'watchlists')->withPivot('created_at');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ENTITY_HEAD->value,
            UserRole::CONTENT_ADMIN->value,
        ]);
    }

    /**
     * Any government/platform staff member (the 6 non-citizen roles). Drives
     * admin-area access and the post-login redirect. Checks both the Spatie
     * roles and the legacy `role` column for backward compatibility.
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(UserRole::staffValues())
            || ($this->role?->isStaff() ?? false);
    }

    public function isPremium(): bool
    {
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function isKycComplete(): bool
    {
        return $this->kyc_status === KycStatus::COMPLETE;
    }

    public function isKycPending(): bool
    {
        return $this->kyc_status === KycStatus::PENDING;
    }

    public function isKycUnderReview(): bool
    {
        return $this->kyc_status === KycStatus::UNDER_REVIEW;
    }

    public function isKycRejected(): bool
    {
        return $this->kyc_status === KycStatus::REJECTED;
    }

    /**
     * Whether the citizen may upload documents / submit the KYC form. Only the
     * "not yet decided" states are editable — a submission under review or an
     * already-approved account is locked. A rejected account can resubmit.
     */
    public function kycCanSubmit(): bool
    {
        return in_array($this->kyc_status, [KycStatus::PENDING, KycStatus::REJECTED], true);
    }

    /**
     * The three identity documents required before a KYC request can be
     * submitted: ID card front, ID card back, and a selfie holding the ID.
     */
    public function hasAllKycDocuments(): bool
    {
        $bio = $this->biometrics;

        return $bio
            && $bio->id_front_path
            && $bio->id_back_path
            && $bio->selfie_with_id_path;
    }

    public function isBlacklisted(): bool
    {
        return (bool) $this->is_blacklisted;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function canBid(): bool
    {
        return $this->isKycComplete()
            && ! $this->isBlacklisted()
            && ! $this->isLocked()
            && $this->account_status === AccountStatus::ACTIVE;
    }

    /**
     * Cached unread notifications count — used by layout in 2 places, so
     * cache it on the instance for the request lifetime.
     */
    public function unreadNotificationsCount(): int
    {
        if (! array_key_exists('unread_notifications_count', $this->attributes)) {
            $this->attributes['unread_notifications_count'] = $this->userNotifications()
                ->where('is_read', false)
                ->count();
        }

        return (int) $this->attributes['unread_notifications_count'];
    }

    /**
     * Convenience accessor for layouts that expect $user->name.
     */
    public function getNameAttribute(): string
    {
        return $this->fullNameAr() ?: ($this->fullNameFr() ?: ($this->email ?? ''));
    }

    /**
     * Preferred UI language. Laravel reads this to localize queued
     * notifications and mailables sent to the user (HasLocalePreference).
     * Falls back to the platform default when not set.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?: config('locales.default', 'ar');
    }
}
