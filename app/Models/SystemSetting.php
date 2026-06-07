<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single runtime-editable platform parameter (spec §8.2). Read through the
 * setting() helper, which caches the whole table and falls back to
 * config/mazayada.php when a key is absent.
 */
class SystemSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'group', 'updated_by'];

    /** The stored string cast to its declared type. */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'float' => (float) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            default => $this->value,
        };
    }
}
