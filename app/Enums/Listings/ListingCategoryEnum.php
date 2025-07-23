<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingCategoryEnum: string
{
    case ELECTRONICS = 'electronics';
    case FASHION = 'fashion';
    case HOME_GARDEN = 'home_garden';
    case AUTOMOTIVE = 'automotive';
    case SPORTS_OUTDOORS = 'sports_outdoors';
    case BOOKS_MEDIA = 'books_media';
    case TOYS_GAMES = 'toys_games';
    case HEALTH_BEAUTY = 'health_beauty';
    case BUSINESS_INDUSTRIAL = 'business_industrial';
    case COLLECTIBLES = 'collectibles';
    case REAL_ESTATE = 'real_estate';
    case SERVICES = 'services';
    case OTHER = 'other';
}