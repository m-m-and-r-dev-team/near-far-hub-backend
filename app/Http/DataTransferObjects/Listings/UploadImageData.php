<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class UploadImageData extends DataTransferObject
{
    public const TYPE = 'type';
    public const ALT_TEXT = 'altText';
    public const IS_PRIMARY = 'isPrimary';
    public const SORT_ORDER = 'sortOrder';
    public const QUALITY = 'quality';
    public const MAX_WIDTH = 'maxWidth';
    public const MAX_HEIGHT = 'maxHeight';
    public const GENERATE_THUMBNAILS = 'generateThumbnails';

    public string $type;
    public ?string $altText;
    public bool $isPrimary;
    public int $sortOrder;
    public int $quality;
    public ?int $maxWidth;
    public ?int $maxHeight;
    public bool $generateThumbnails;
}