<?php

namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETE = 'COMPLETE';
    case SUSPENDED = 'SUSPENDED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'في الانتظار',
            self::COMPLETE => 'موثّق',
            self::SUSPENDED => 'معلّق',
        };
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::COMPLETE => 'chip-ok',
            self::SUSPENDED => 'chip-danger',
        };
    }
}
