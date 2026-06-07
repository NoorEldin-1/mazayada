<?php

namespace App\Enums;

/**
 * Lifecycle of the physical hand-over of a won asset (spec §4 step 9).
 */
enum DeliveryStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case IN_TRANSIT = 'IN_TRANSIT';
    case DELIVERED = 'DELIVERED';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return __('enums.delivery_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::SCHEDULED => 'chip-info',
            self::IN_TRANSIT => 'chip-warn',
            self::DELIVERED => 'chip-ok',
            self::FAILED => 'chip-danger',
            self::CANCELLED => 'chip-violet',
        };
    }
}
