<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum SubscriptionPeriod: string
{
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';

    public function label(): string
    {
        return __('enums.subscription_period.'.$this->value);
    }

    /** End of one period starting at $from. */
    public function addTo(Carbon $from): Carbon
    {
        return match ($this) {
            self::MONTHLY => $from->copy()->addMonthNoOverflow(),
            self::YEARLY => $from->copy()->addYearNoOverflow(),
        };
    }
}
