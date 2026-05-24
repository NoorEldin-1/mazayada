<?php

namespace App\Enums;

enum AuctionType: string
{
    case SALE = 'SALE';
    case LEASE = 'LEASE';

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'بيع',
            self::LEASE => 'إيجار',
        };
    }
}
