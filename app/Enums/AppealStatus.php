<?php

namespace App\Enums;

/**
 * The appeal (طعن) workflow state machine.
 *
 * Internally an appeal moves through six states as it is handed between the
 * citizen, the platform admin and the organising entity. The CITIZEN, however,
 * only ever sees three buckets — see {@see self::publicStatus()}:
 *
 *   PENDING ─────────────┐
 *   FORWARDED_TO_ENTITY ─┤→ PENDING  (🟡 قيد المراجعة)
 *   ENTITY_APPROVED ─────┤
 *   ENTITY_REJECTED ─────┘
 *   APPROVED ────────────→ APPROVED  (🟢 موافقة)
 *   REJECTED ────────────→ REJECTED  (🔴 رفض)
 */
enum AppealStatus: string
{
    case PENDING = 'PENDING';                         // filed, awaiting the admin
    case FORWARDED_TO_ENTITY = 'FORWARDED_TO_ENTITY';  // admin forwarded to the entity
    case ENTITY_APPROVED = 'ENTITY_APPROVED';          // entity approved, awaiting admin confirmation
    case ENTITY_REJECTED = 'ENTITY_REJECTED';          // entity rejected, awaiting admin confirmation
    case APPROVED = 'APPROVED';                        // admin confirmed acceptance (terminal)
    case REJECTED = 'REJECTED';                        // admin confirmed rejection (terminal)

    /**
     * The three citizen-facing buckets. Everything still in flight collapses to
     * PENDING; only the admin's final confirmation reveals APPROVED / REJECTED.
     */
    public function publicStatus(): self
    {
        return match ($this) {
            self::APPROVED => self::APPROVED,
            self::REJECTED => self::REJECTED,
            default => self::PENDING,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], true);
    }

    /** Full, internal label for the admin / entity dashboards (6 states). */
    public function label(): string
    {
        return __('enums.appeal_status.'.$this->value);
    }

    /** Collapsed label for the citizen (3 states). */
    public function publicLabel(): string
    {
        return $this->publicStatus()->label();
    }

    /** Chip colour for the admin dashboard (6 states). */
    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-info',
            self::FORWARDED_TO_ENTITY => 'chip-violet',
            self::ENTITY_APPROVED, self::ENTITY_REJECTED => 'chip-warn',
            self::APPROVED => 'chip-ok',
            self::REJECTED => 'chip-danger',
        };
    }

    /** Chip colour for the citizen (3 states). */
    public function publicChipClass(): string
    {
        return match ($this->publicStatus()) {
            self::APPROVED => 'chip-ok',
            self::REJECTED => 'chip-danger',
            default => 'chip-warn',
        };
    }
}
