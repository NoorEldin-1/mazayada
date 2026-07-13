<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A generated document reference (condition book / award / receipt / delivery
 * report). The binary is fetched separately via the document download endpoint.
 *
 * The auction / entity block is only included when the `auction` relation is
 * loaded, so the lean references embedded in AuctionResource stay unchanged while
 * the document-library listing carries the richer context the client renders.
 *
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'title' => $this->title,
            'is_public' => (bool) $this->is_public,
            'file_size' => (int) $this->file_size,
            'file_size_human' => human_filesize($this->file_size),
            'issued_at' => $this->created_at?->toIso8601String(),
            'download_url' => route('api.v1.documents.download', $this->id),
            // Public QR verification page (same signed link printed on the PDF).
            'verify_url' => $this->signature
                ? route('documents.verify', ['doc' => $this->id, 'sig' => $this->signature])
                : null,
            'auction' => $this->whenLoaded('auction', fn () => $this->auction ? [
                'id' => $this->auction->id,
                'title' => $this->auction->localizedTitle(),
                'entity_name' => $this->auction->entity?->name,
                'wilaya_name' => $this->auction->wilaya?->name,
                'category_name' => $this->auction->category?->name,
            ] : null),
        ];
    }
}
