<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commune extends Model
{
    public $timestamps = false;

    protected $fillable = ['wilaya_id', 'code', 'name_ar', 'name_fr', 'postal_code'];

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }
}
