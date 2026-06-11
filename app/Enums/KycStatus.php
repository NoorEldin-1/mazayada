<?php

namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'PENDING';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case COMPLETE = 'COMPLETE';
    case REJECTED = 'REJECTED';
    case SUSPENDED = 'SUSPENDED';

    public function label(): string
    {
        return __('enums.kyc_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::UNDER_REVIEW => 'chip-info',
            self::COMPLETE => 'chip-ok',
            self::REJECTED, self::SUSPENDED => 'chip-danger',
        };
    }

    /**
     * Tone token for the <x-ui.badge> component (ok / warn / info / danger).
     * Used by the compact KYC status badge shown next to the user's name.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::PENDING => 'warn',
            self::UNDER_REVIEW => 'info',
            self::COMPLETE => 'ok',
            self::REJECTED, self::SUSPENDED => 'danger',
        };
    }

    /**
     * Tailwind text-colour utility for the coloured status label rendered
     * under the user's name in the citizen sidebar.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::PENDING => 'text-warn',
            self::UNDER_REVIEW => 'text-info',
            self::COMPLETE => 'text-ok',
            self::REJECTED, self::SUSPENDED => 'text-danger',
        };
    }
}
