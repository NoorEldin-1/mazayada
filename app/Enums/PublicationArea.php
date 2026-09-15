<?php

namespace App\Enums;

/**
 * The display space a publication package buys on the platform (client edit 14).
 */
enum PublicationArea: string
{
    case LISTING = 'LISTING';           // the general auctions listing
    case CATEGORY_TOP = 'CATEGORY_TOP'; // top of its sector/category
    case HOMEPAGE = 'HOMEPAGE';         // featured on the home page

    public function label(): string
    {
        return __('enums.publication_area.'.$this->value);
    }
}
