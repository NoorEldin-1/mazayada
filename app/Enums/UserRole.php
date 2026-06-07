<?php

namespace App\Enums;

enum UserRole: string
{
    case CITIZEN = 'CITIZEN';
    case PREMIUM_CITIZEN = 'PREMIUM_CITIZEN';
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ENTITY_HEAD = 'ENTITY_HEAD';
    case APPRAISER = 'APPRAISER';
    case HUISSIER = 'HUISSIER';
    case COMMITTEE_MEMBER = 'COMMITTEE_MEMBER';
    case CONTENT_ADMIN = 'CONTENT_ADMIN';

    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::ENTITY_HEAD, self::CONTENT_ADMIN]);
    }

    /**
     * Any non-citizen role — i.e. a government/platform employee who belongs in
     * the admin dashboard. Used for the admin area gate and login redirect.
     */
    public function isStaff(): bool
    {
        return ! in_array($this, [self::CITIZEN, self::PREMIUM_CITIZEN]);
    }

    /** Role values that grant access to the admin dashboard. */
    public static function staffValues(): array
    {
        return array_values(array_map(
            fn (self $r) => $r->value,
            array_filter(self::cases(), fn (self $r) => $r->isStaff()),
        ));
    }
}
