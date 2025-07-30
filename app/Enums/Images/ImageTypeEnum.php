<?php

declare(strict_types=1);

namespace App\Enums\Images;

enum ImageTypeEnum: string
{
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case BANNER = 'banner';
    case PROFILE = 'profile';

    public static function getRegexPattern(): string
    {
        return implode('|', array_column(self::cases(), 'value'));
    }
}
