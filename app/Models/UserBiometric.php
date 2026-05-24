<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBiometric extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'photo_biometric_path', 'selfie_with_id_path',
        'id_front_path', 'id_back_path', 'kyc_verified_by',
        'kyc_verified_at', 'liveness_score', 'match_score',
    ];

    protected function casts(): array
    {
        return [
            'kyc_verified_at' => 'datetime',
            'liveness_score' => 'float',
            'match_score' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
