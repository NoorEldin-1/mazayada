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
}
