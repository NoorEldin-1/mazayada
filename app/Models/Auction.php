<?php

namespace App\Models;

use App\Enums\AssetClass;
use App\Enums\AssetCondition;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Models\Concerns\BelongsToEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Auction extends Model
{
    use BelongsToEntity, HasUuids, LogsActivity;

    protected $fillable = [
        'entity_id', 'category_id', 'title_ar', 'title_fr', 'title_en',
        'description_ar', 'description_fr', 'condition', 'unit_count',
        'asset_location', 'latitude', 'longitude',
        'opening_price', 'deposit_amount', 'entry_fee', 'book_price',
        'start_time', 'end_time', 'extension_trigger_seconds', 'extension_duration_minutes',
        'status', 'winner_user_id', 'final_price',
        'auction_type', 'asset_class', 'lease_duration_years', 'lease_renewals',
        'requires_commerce_register', 'requires_newspaper_announcement',
        'inspection_start', 'inspection_end', 'inspection_location',
        'max_extensions', 'extension_count', 'original_owner_nin',
        'closed_at', 'settled_at',
        'created_by', 'appraiser_id',
        'wilaya_id', 'commune_id', 'mayor_name', 'photos', 'video',
        'entity_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuctionStatus::class,
            'auction_type' => AuctionType::class,
            'asset_class' => AssetClass::class,
            'condition' => AssetCondition::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'inspection_start' => 'datetime',
            'inspection_end' => 'datetime',
            'closed_at' => 'datetime',
            'settled_at' => 'datetime',
            'opening_price' => 'integer',
            'deposit_amount' => 'integer',
            'entry_fee' => 'integer',
            'book_price' => 'integer',
            'final_price' => 'integer',
            'max_extensions' => 'integer',
            'extension_count' => 'integer',
            'requires_commerce_register' => 'boolean',
            'requires_newspaper_announcement' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'opening_price', 'deposit_amount', 'entry_fee',
                'start_time', 'end_time', 'winner_user_id', 'final_price',
                'requires_commerce_register',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('auction');
    }

    // Relationships
    // entity() is provided by the BelongsToEntity trait (also registers EntityScope).

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /** Entity staff member (موظف الجهة) responsible for this auction, if any. */
    public function entityUser(): BelongsTo
    {
        return $this->belongsTo(EntityUser::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function createdByUser(): BelongsTo
    {
        // created_by stores the User id of the staff member who created the
        // auction (see AdminAuctionController::store). The FK targets users.
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AuctionParticipant::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function inspectionQuestions(): HasMany
    {
        return $this->hasMany(InspectionQuestion::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /** Citizens who added this auction to their watchlist (notification targets). */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'watchlists')->withPivot('created_at');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED]);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AuctionStatus::PUBLISHED);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AuctionStatus::PUBLISHED,
            AuctionStatus::ACTIVE,
            AuctionStatus::EXTENDED,
            AuctionStatus::CLOSED,
        ]);
    }

    // Helpers
    public function currentPrice(): int
    {
        return Cache::remember(
            "auction:{$this->id}:current_price",
            now()->addSeconds(5),
            fn () => (int) ($this->bids()->where('is_valid', true)->max('amount') ?? $this->opening_price)
        );
    }

    public function bidCount(): int
    {
        return $this->bids()->where('is_valid', true)->count();
    }

    public function isLive(): bool
    {
        return in_array($this->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED]);
    }

    public function photosArray(): array
    {
        return $this->photos ? array_filter(explode(';', $this->photos)) : [];
    }

    /**
     * Public URLs for every uploaded photo (served via the storage symlink).
     * Empty when the asset has no photos — callers fall back to a placeholder.
     */
    public function photoUrls(): array
    {
        return array_map(
            fn (string $path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
            $this->photosArray(),
        );
    }

    /**
     * First photo URL for listing cards, or null when none uploaded.
     */
    public function coverPhotoUrl(): ?string
    {
        return $this->photoUrls()[0] ?? null;
    }

    /**
     * Public URL for the single short asset video, or null when none uploaded.
     */
    public function videoUrl(): ?string
    {
        return $this->video
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->video)
            : null;
    }

    public function formatPrice(int $centimes): string
    {
        return number_format($centimes / 100, 0, ',', ' ') . ' ' . __('common.currency');
    }

    /**
     * Localized auction title for the active locale, falling back ar → fr → en.
     * Mirrors HasLocalizedName but for the title_* columns.
     */
    public function localizedTitle(): string
    {
        foreach ([app()->getLocale(), 'ar', 'fr', 'en'] as $locale) {
            $value = $this->{'title_'.$locale} ?? null;
            if (! empty($value)) {
                return $value;
            }
        }

        return $this->title_ar ?? '';
    }

    public function isCustoms(): bool
    {
        return $this->asset_class === AssetClass::CUSTOMS;
    }

    /**
     * Final-payment deadline in days for this asset class (CPC Art. 373):
     * movables 8 days, real estate 15 days. Customs is treated as movable.
     */
    public function finalPaymentDeadlineDays(): int
    {
        $key = ($this->asset_class ?? AssetClass::MOVABLE)->deadlineKey();

        return (int) setting("payments.final_payment_deadline_days.{$key}",
            config("mazayada.payments.final_payment_deadline_days.{$key}", 8));
    }

    /** Whether the inspection window (§4 step 4) is currently open. */
    public function isInspectionOpen(): bool
    {
        if (! $this->inspection_start || ! $this->inspection_end) {
            return false;
        }

        return now()->betweenIncluded($this->inspection_start, $this->inspection_end);
    }
}
