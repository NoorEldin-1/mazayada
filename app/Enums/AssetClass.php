<?php

namespace App\Enums;

/**
 * Legal classification of the auctioned asset. Drives:
 *  - the final-payment deadline (movable 8 days / real estate 15 days — CPC Art. 373),
 *  - the customs 20%-immediate rule (spec §2.3),
 *  - whether the movables hammer-fee tiers apply (Decree 97-33; real estate uses
 *    proportional rights only).
 */
enum AssetClass: string
{
    case MOVABLE = 'MOVABLE';
    case REAL_ESTATE = 'REAL_ESTATE';
    case CUSTOMS = 'CUSTOMS';

    public function label(): string
    {
        return __('enums.asset_class.'.$this->value);
    }

    /** Final-payment deadline key used in config('mazayada.payments.final_payment_deadline_days'). */
    public function deadlineKey(): string
    {
        return $this === self::REAL_ESTATE ? 'real_estate' : 'movable';
    }
}
