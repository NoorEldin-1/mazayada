<?php

namespace App\Enums;

/**
 * Publication level bought by the organising entity (client edit 15). PRIORITY
 * costs a surcharge on top of the package price and is listed first in browse.
 */
enum PublicationPriority: string
{
    case NORMAL = 'NORMAL';
    case PRIORITY = 'PRIORITY';

    public function label(): string
    {
        return __('enums.publication_priority.'.$this->value);
    }
}
