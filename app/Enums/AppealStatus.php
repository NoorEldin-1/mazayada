<?php

namespace App\Enums;

enum AppealStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case RESOLVED = 'RESOLVED';
    case REJECTED = 'REJECTED';
    case ESCALATED = 'ESCALATED';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'مقدّم',
            self::UNDER_REVIEW => 'قيد المراجعة',
            self::RESOLVED => 'تمت التسوية',
            self::REJECTED => 'مرفوض',
            self::ESCALATED => 'مُصعَّد',
        };
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::SUBMITTED => 'chip-info',
            self::UNDER_REVIEW => 'chip-warn',
            self::RESOLVED => 'chip-ok',
            self::REJECTED => 'chip-danger',
            self::ESCALATED => 'chip-violet',
        };
    }
}
