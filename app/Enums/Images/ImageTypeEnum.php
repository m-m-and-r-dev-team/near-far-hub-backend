<?php

declare(strict_types=1);

namespace App\Enums\Images;

enum ImageTypeEnum: string
{
    case PROFILE = 'profile';
    case LISTING = 'listing';
    case SELLER_VERIFICATION = 'seller_verification';
    case USER_AVATAR = 'user_avatar';
    case LISTING_GALLERY = 'listing_gallery';
    case LISTING_THUMBNAIL = 'listing_thumbnail';
}