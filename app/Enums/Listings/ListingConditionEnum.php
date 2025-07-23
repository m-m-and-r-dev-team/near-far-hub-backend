<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingConditionEnum: string
{
    case NEW = 'new';
    case LIKE_NEW = 'like_new';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
    case REFURBISHED = 'refurbished';
    case FOR_PARTS = 'for_parts';
}