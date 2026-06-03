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
        return __('enums.payment_type.'.$this->value);
    }
}
