<?php

namespace App\Enums;

/**
 * Lifecycle of a user's Commercial Register (السجل التجاري) submission. A record
 * only exists once the user submits, so there is no "not started" state — the
 * three cases below mirror the requirement exactly:
 *   PENDING  — submitted, awaiting an admin decision (the queue badge counts these)
 *   APPROVED — accepted; unlocks bidding on auctions that require a register
 *   REJECTED — refused with a reason; the user may fix and resubmit
 *
 * Deliberately parallels App\Enums\KycStatus (same helper surface) so the shared
 * chip / badge CSS is reused verbatim.
 */
enum CommercialRegisterStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return __('enums.commercial_register_status.'.$this->value);
    }

    /** Chip class for the status pill (matches the KYC chip palette). */
    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::APPROVED => 'chip-ok',
            self::REJECTED => 'chip-danger',
        };
    }

    /** Tone token for the <x-ui.badge> component (ok / warn / danger). */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::PENDING => 'warn',
            self::APPROVED => 'ok',
            self::REJECTED => 'danger',
        };
    }

    /** Tailwind text-colour utility for the coloured status label. */
    public function textClass(): string
    {
        return match ($this) {
            self::PENDING => 'text-warn',
            self::APPROVED => 'text-ok',
            self::REJECTED => 'text-danger',
        };
    }
}
