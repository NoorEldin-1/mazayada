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
        return __('enums.asset_condition.'.$this->value);
    }
}
