<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
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

class User extends Authenticatable
{
    use HasRoles, HasUuids, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'nin', 'id_card_number', 'passport_number', 'license_number',
        'first_name_ar', 'last_name_ar', 'first_name_fr', 'last_name_fr',
        'father_name', 'mother_fullname', 'birth_date', 'birth_place',
        'phone', 'email', 'address', 'commune_id', 'postal_code',
        'profession', 'nif', 'nis', 'rip', 'expected_income',
        'kyc_status', 'kyc_completed_at', 'is_blacklisted', 'blacklist_reason',
        'account_status', 'premium_until', 'secret_question', 'secret_answer',
        'password', 'role', 'phone_verified', 'email_verified',
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
                'kyc_status', 'account_status', 'is_blacklisted', 'blacklist_reason',
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

    public function isPremium(): bool
    {
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function isKycComplete(): bool
    {
        return $this->kyc_status === KycStatus::COMPLETE;
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
}
