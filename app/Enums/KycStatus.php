<?php

namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'PENDING';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case COMPLETE = 'COMPLETE';
    case REJECTED = 'REJECTED';
    case SUSPENDED = 'SUSPENDED';

    public function label(): string
    {
        return __('enums.kyc_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::UNDER_REVIEW => 'chip-info',
            self::COMPLETE => 'chip-ok',
            self::REJECTED, self::SUSPENDED => 'chip-danger',
        };
    }
}
