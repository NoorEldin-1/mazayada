<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasUuids, Notifiable;

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

    protected $hidden = ['password', 'remember_token', 'secret_answer'];

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
            'kyc_status' => KycStatus::class,
            'account_status' => AccountStatus::class,
            'role' => UserRole::class,
        ];
    }

    public function fullNameAr(): string
    {
        return $this->first_name_ar . ' ' . $this->last_name_ar;
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
        return $this->role->isAdmin();
    }

    public function isPremium(): bool
    {
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function isKycComplete(): bool
    {
        return $this->kyc_status === KycStatus::COMPLETE;
    }
}
