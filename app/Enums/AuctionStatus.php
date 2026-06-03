<?php

namespace App\Enums;

enum AuctionStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ACTIVE = 'ACTIVE';
    case EXTENDED = 'EXTENDED';
    case CLOSED = 'CLOSED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return __('enums.auction_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::DRAFT => 'chip-muted',
            self::PUBLISHED => 'chip-info',
            self::ACTIVE => 'chip-ok',
            self::EXTENDED => 'chip-warn',
            self::CLOSED => 'chip-muted',
            self::CANCELLED => 'chip-danger',
        };
    }
}
