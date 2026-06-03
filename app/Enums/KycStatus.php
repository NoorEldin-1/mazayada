<?php

namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETE = 'COMPLETE';
    case SUSPENDED = 'SUSPENDED';

    public function label(): string
    {
        return __('enums.kyc_status.'.$this->value);
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
