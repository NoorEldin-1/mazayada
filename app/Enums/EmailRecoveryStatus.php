<?php

namespace App\Enums;

/**
 * Lifecycle of a "lost my email" recovery request:
 *   PENDING      — submitted by the citizen, waiting in the admin queue
 *   UNDER_REVIEW — a reviewer has picked it up
 *   APPROVED     — identity confirmed; the account email was replaced
 *   REJECTED     — refused with a reason; the citizen may submit a new request
 *
 * Mirrors CommercialRegisterStatus (same helper surface) so the shared chip /
 * badge palette is reused verbatim.
 */
enum EmailRecoveryStatus: string
{
    case PENDING = 'PENDING';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return __('email_recovery.status.'.$this->value);
    }

    /** Still awaiting a decision — at most one such request per account. */
    public function isOpen(): bool
    {
        return in_array($this, [self::PENDING, self::UNDER_REVIEW], true);
    }

    /** Chip class for the status pill (matches the KYC chip palette). */
    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::UNDER_REVIEW => 'chip-info',
            self::APPROVED => 'chip-ok',
            self::REJECTED => 'chip-danger',
        };
    }

    /** @return array<int, self> */
    public static function openCases(): array
    {
        return [self::PENDING, self::UNDER_REVIEW];
    }
}
