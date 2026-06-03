<?php

namespace App\Enums;

enum AuctionType: string
{
    case SALE = 'SALE';
    case LEASE = 'LEASE';

    public function label(): string
    {
        return __('enums.auction_type.'.$this->value);
    }
}
