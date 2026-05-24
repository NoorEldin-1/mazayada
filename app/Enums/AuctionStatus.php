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
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::PUBLISHED => 'منشورة',
            self::ACTIVE => 'نشطة',
            self::EXTENDED => 'ممددة',
            self::CLOSED => 'مغلقة',
            self::CANCELLED => 'ملغاة',
        };
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
