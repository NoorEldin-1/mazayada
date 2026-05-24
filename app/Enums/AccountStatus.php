<?php

namespace App\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case BANNED = 'BANNED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشط',
            self::SUSPENDED => 'معلّق',
            self::BANNED => 'محظور',
        };
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'chip-ok',
            self::SUSPENDED => 'chip-warn',
            self::BANNED => 'chip-danger',
        };
    }
}
