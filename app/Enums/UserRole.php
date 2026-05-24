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
        return match ($this) {
            self::CITIZEN => 'مواطن',
            self::PREMIUM_CITIZEN => 'مواطن مميز',
            self::SUPER_ADMIN => 'مشرف عام',
            self::ENTITY_HEAD => 'رئيس جهة',
            self::APPRAISER => 'خبير تقييم',
            self::HUISSIER => 'محضر قضائي',
            self::COMMITTEE_MEMBER => 'عضو لجنة',
            self::CONTENT_ADMIN => 'مشرف محتوى',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::ENTITY_HEAD, self::CONTENT_ADMIN]);
    }
}
