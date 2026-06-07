<?php

namespace App\Enums;

/**
 * The kind of identity document a citizen registers during KYC (spec §3.2).
 * Maps to the matching column on the users table.
 */
enum IdDocumentType: string
{
    case ID_CARD = 'ID_CARD';
    case PASSPORT = 'PASSPORT';
    case LICENSE = 'LICENSE';

    public function label(): string
    {
        return __('enums.id_document_type.'.$this->value);
    }

    /** The users-table column that stores this document's number. */
    public function column(): string
    {
        return match ($this) {
            self::ID_CARD => 'id_card_number',
            self::PASSPORT => 'passport_number',
            self::LICENSE => 'license_number',
        };
    }
}
