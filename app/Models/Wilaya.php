<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilaya extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'name_ar', 'name_fr', 'name_en'];

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }
}
