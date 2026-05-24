<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id', 'category_id', 'title_ar', 'title_fr', 'title_en',
        'description_ar', 'description_fr', 'condition', 'unit_count',
        'asset_location', 'latitude', 'longitude',
        'opening_price', 'deposit_amount', 'entry_fee', 'book_price',
        'start_time', 'end_time', 'extension_trigger_seconds', 'extension_duration_minutes',
        'status', 'winner_user_id', 'final_price',
        'auction_type', 'lease_duration_years', 'lease_renewals',
        'requires_commerce_register', 'created_by', 'appraiser_id',
        'wilaya_id', 'photos',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuctionStatus::class,
            'auction_type' => AuctionType::class,
            'condition' => AssetCondition::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'opening_price' => 'integer',
            'deposit_amount' => 'integer',
            'entry_fee' => 'integer',
            'book_price' => 'integer',
            'final_price' => 'integer',
            'requires_commerce_register' => 'boolean',
        ];
    }

    // Relationships
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(EntityUser::class, 'created_by');
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
        return $this->bids()->where('is_valid', true)->max('amount') ?? $this->opening_price;
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

    public function formatPrice(int $centimes): string
    {
        return number_format($centimes / 100, 0, ',', ' ') . ' دج';
    }
}
