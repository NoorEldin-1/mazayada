<?php

namespace App\Enums;

/**
 * State of a bidder's written question during the inspection window
 * (spec §4 step 4 — Art. 7 condition book + Customs Art. 373).
 */
enum InspectionQuestionStatus: string
{
    case PENDING = 'PENDING';
    case ANSWERED = 'ANSWERED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return __('enums.inspection_question_status.'.$this->value);
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::PENDING => 'chip-warn',
            self::ANSWERED => 'chip-ok',
            self::REJECTED => 'chip-danger',
        };
    }
}
