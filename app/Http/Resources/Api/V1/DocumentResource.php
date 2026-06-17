<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A generated document reference (condition book / award / receipt). The binary
 * is fetched separately via the document download endpoint.
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
            'title' => $this->title,
            'is_public' => (bool) $this->is_public,
            'download_url' => route('api.v1.documents.download', $this->id),
        ];
    }
}
