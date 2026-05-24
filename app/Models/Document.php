<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'auction_id', 'type', 'title', 'file_path',
        'file_size', 'qr_payload', 'is_public',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'file_size' => 'integer'];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }
}
