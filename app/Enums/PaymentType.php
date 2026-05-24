<?php

namespace App\Enums;

enum PaymentType: string
{
    case DEPOSIT = 'DEPOSIT';
    case ENTRY_FEE = 'ENTRY_FEE';
    case BOOK_PURCHASE = 'BOOK_PURCHASE';
    case FINAL_PAYMENT = 'FINAL_PAYMENT';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'كفالة',
            self::ENTRY_FEE => 'رسوم دخول',
            self::BOOK_PURCHASE => 'كراسة شروط',
            self::FINAL_PAYMENT => 'دفع نهائي',
        };
    }
}
