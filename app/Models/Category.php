<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasLocalizedName;

    public $timestamps = false;

    protected $fillable = ['name_ar', 'name_fr', 'name_en', 'icon', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }
}
