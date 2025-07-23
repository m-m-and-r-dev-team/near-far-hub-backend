<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class PublishListingData extends DataTransferObject
{
    public const PUBLISHED_AT = 'publishedAt';
    public const EXPIRES_AT = 'expiresAt';

    public ?string $publishedAt;
    public ?string $expiresAt;
}