<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'auction_id', 'user_id', 'type', 'title', 'file_path', 'disk',
        'file_size', 'mime', 'qr_payload', 'signature', 'is_public', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'is_public' => 'boolean',
            'file_size' => 'integer',
            'meta' => 'array',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Storage disk this document lives on (defaults to the private documents disk). */
    public function diskName(): string
    {
        return $this->disk ?: config('mazayada.documents.disk', 'documents');
    }
}
