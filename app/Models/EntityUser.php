<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityUser extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id', 'user_id', 'username', 'password',
        'full_name', 'role', 'is_active',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
