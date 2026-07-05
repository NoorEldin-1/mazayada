<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case REFUNDED = 'REFUNDED';
    case FORFEITED = 'FORFEITED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::CONFIRMED => 'chip-ok',
            self::REFUNDED => 'chip-info',
            self::FORFEITED => 'chip-danger',
            self::FAILED => 'chip-muted',
        };
    }
}
