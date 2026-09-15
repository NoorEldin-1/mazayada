<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case PENDING = 'PENDING';     // checkout started, payment not confirmed yet
    case ACTIVE = 'ACTIVE';       // paid and within its period
    case EXPIRED = 'EXPIRED';     // period ended while auto-renew was on
    case CANCELLED = 'CANCELLED'; // payment failed, or ended after auto-renew was switched off

    public function label(): string
    {
        return __('enums.subscription_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::ACTIVE => 'chip-ok',
            self::EXPIRED => 'chip-muted',
            self::CANCELLED => 'chip-danger',
        };
    }
}
