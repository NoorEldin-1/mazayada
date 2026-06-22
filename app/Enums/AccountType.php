<?php

namespace App\Enums;

/**
 * Distinguishes a real person (citizens + individual entity staff) from an
 * institutional login that represents a government entity itself. Institutional
 * accounts have no NIN / phone / birth date and are always read-only
 * (UserRole::ENTITY_VIEWER), scoped to their entity by EntityScope.
 */
enum AccountType: string
{
    case PERSON = 'PERSON';
    case INSTITUTION = 'INSTITUTION';

    public function label(): string
    {
        return __('enums.account_type.'.$this->value);
    }
}
