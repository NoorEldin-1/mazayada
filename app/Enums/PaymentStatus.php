<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case REFUNDED = 'REFUNDED';
    case FORFEITED = 'FORFEITED';
    case FAILED = 'FAILED';
}
