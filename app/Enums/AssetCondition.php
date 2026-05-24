<?php

namespace App\Enums;

enum AssetCondition: string
{
    case NEW = 'NEW';
    case GOOD = 'GOOD';
    case FAIR = 'FAIR';
    case POOR = 'POOR';
    case SCRAP = 'SCRAP';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'جديد',
            self::GOOD => 'جيد',
            self::FAIR => 'مقبول',
            self::POOR => 'ضعيف',
            self::SCRAP => 'خردة',
        };
    }
}
