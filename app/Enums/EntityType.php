<?php

namespace App\Enums;

enum EntityType: string
{
    case CUSTOMS = 'CUSTOMS';
    case STATE_PROPERTIES = 'STATE_PROPERTIES';
    case MUNICIPALITY = 'MUNICIPALITY';
    case JUDICIAL = 'JUDICIAL';
    case TAX = 'TAX';

    public function label(): string
    {
        return __('enums.entity_type.'.$this->value);
    }

    public function code(): string
    {
        return match ($this) {
            self::CUSTOMS => 'DGD',
            self::STATE_PROPERTIES => 'DGDPE',
            self::MUNICIPALITY => 'APC',
            self::JUDICIAL => 'HUI',
            self::TAX => 'DGI',
        };
    }
}
