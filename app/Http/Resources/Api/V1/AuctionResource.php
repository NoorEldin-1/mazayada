<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\DocumentType;
use App\Models\Auction;
use App\Services\BidderAliasService;
use App\Support\Api\FormatsMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full auction detail. Money fields are { amount, formatted } in dinars; the
 * winner is shown only by alias and only once the auction is closed.
 *
 * @mixin Auction
 */
class AuctionResource extends JsonResource
{
    use FormatsMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isClosed = $this->status === AuctionStatus::CLOSED;

        return [
            'id' => $this->id,
            'title' => $this->localizedTitle(),
            'titles' => [
                'ar' => $this->title_ar,
                'fr' => $this->title_fr,
                'en' => $this->title_en,
            ],
            'description' => $this->description_ar ?: $this->description_fr,
            'descriptions' => [
                'ar' => $this->description_ar,
                'fr' => $this->description_fr,
            ],
            // Admin-authored asset specifications: localized title/body for the
            // active locale, plus the per-language maps for client-side switching.
            'specifications' => collect($this->specifications ?? [])->map(fn (array $spec) => [
                'title' => ($spec['title_'.app()->getLocale()] ?? null) ?: ($spec['title_ar'] ?? ''),
                'body' => ($spec['body_'.app()->getLocale()] ?? null) ?: ($spec['body_ar'] ?? ''),
                'titles' => ['ar' => $spec['title_ar'] ?? null, 'fr' => $spec['title_fr'] ?? null],
                'bodies' => ['ar' => $spec['body_ar'] ?? null, 'fr' => $spec['body_fr'] ?? null],
            ])->values()->all(),
            'status' => $this->status?->value,
            'auction_type' => $this->auction_type?->value,
            'asset_class' => $this->asset_class?->value,
            'condition' => $this->condition?->value,
            'unit_count' => $this->unit_count,

            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'wilaya' => $this->whenLoaded('wilaya', fn () => [
                'id' => $this->wilaya->id,
                'code' => $this->wilaya->code,
                'name' => $this->wilaya->name,
            ]),
            'commune' => $this->whenLoaded('commune', fn () => $this->commune ? [
                'id' => $this->commune->id,
                'name' => $this->commune->name,
            ] : null),
            'entity' => $this->whenLoaded('entity', fn () => $this->entity ? [
                'id' => $this->entity->id,
                'name' => $this->entity->name,
            ] : null),

            'asset_location' => $this->asset_location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'mayor_name' => $this->mayor_name,

            'photos' => $this->photoUrls(),
            'cover_photo_url' => $this->coverPhotoUrl(),
            'video_url' => $this->videoUrl(),

            'opening_price' => $this->money($this->opening_price),
            'current_price' => $this->money($this->currentPrice()),
            // Participation deposit = a % of the opening price (refundable to
            // losers, credited to the winner). The legacy entry fee is removed.
            'deposit_amount' => $this->money($this->deposit_amount),
            'deposit_percent' => (float) $this->deposit_percent,
            'book_price' => $this->money($this->book_price),
            // Whether the current user may download the condition book (free or paid).
            'has_book_access' => $request->user() ? $this->hasBookAccess($request->user()) : false,

            'bid_count' => $this->bidCount(),
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'seconds_remaining' => $this->secondsRemaining(),
            'is_live' => $this->isLive(),
            'is_biddable' => $this->isBiddable(),
            'has_ended' => $this->hasEnded(),

            'extension_count' => $this->extension_count,
            'max_extensions' => $this->max_extensions,

            'inspection' => [
                'start' => $this->inspection_start?->toIso8601String(),
                'end' => $this->inspection_end?->toIso8601String(),
                'location' => $this->inspection_location,
                'is_open' => $this->isInspectionOpen(),
            ],

            'lease' => $this->when($this->auction_type === AuctionType::LEASE, fn () => [
                'duration_years' => $this->lease_duration_years,
                'renewals' => $this->lease_renewals,
            ]),

            'requires_commerce_register' => (bool) $this->requires_commerce_register,
            'requires_newspaper_announcement' => (bool) $this->requires_newspaper_announcement,

            // Outcome — only meaningful once closed.
            'winner_alias' => $isClosed && $this->winner_user_id
                ? app(BidderAliasService::class)->aliasFor($this->winner_user_id, $this->id)
                : null,
            'final_price' => $this->when($isClosed && $this->final_price !== null, fn () => $this->money($this->final_price)),

            // Document references (the binary is fetched via the download endpoint).
            'condition_book' => $this->conditionBookRef(),
            'award_document' => $this->awardDocumentRef($request),
        ];
    }

    protected function secondsRemaining(): int
    {
        if ($this->end_time === null || ! $this->end_time->isFuture()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->end_time);
    }

    /**
     * Condition-book reference (id + download URL). The binary itself is gated:
     * downloading requires the book to be free or purchased (see has_book_access
     * and DocumentPolicy). Returning the reference just tells the client it exists.
     */
    protected function conditionBookRef(): ?array
    {
        $doc = $this->documents()
            ->where('type', DocumentType::CONDITION_BOOK)
            ->latest()
            ->first();

        return $doc ? (new DocumentResource($doc))->resolve() : null;
    }

    /** Award report — surfaced only to the winner. */
    protected function awardDocumentRef(Request $request): ?array
    {
        if (! $this->winner_user_id || $request->user()?->id !== $this->winner_user_id) {
            return null;
        }

        $doc = $this->documents()
            ->where('type', DocumentType::AWARD)
            ->latest()
            ->first();

        return $doc ? (new DocumentResource($doc))->resolve() : null;
    }
}
