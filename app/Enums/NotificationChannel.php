<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case PUSH = 'PUSH';
    case SMS = 'SMS';
    case EMAIL = 'EMAIL';
    case IN_APP = 'IN_APP';

    public function label(): string
    {
        return __('enums.notification_channel.'.$this->value);
    }
}
