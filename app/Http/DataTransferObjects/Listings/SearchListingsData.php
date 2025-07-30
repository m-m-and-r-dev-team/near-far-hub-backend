<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class SearchListingsData extends DataTransferObject
{
    public const QUERY = 'query';
    public const CATEGORY_ID = 'categoryId';
    public const CONDITION = 'condition';
    public const MIN_PRICE = 'minPrice';
    public const MAX_PRICE = 'maxPrice';
    public const LOCATION = 'location';
    public const LATITUDE = 'latitude';
    public const LONGITUDE = 'longitude';
    public const RADIUS = 'radius';
    public const SORT = 'sort';
    public const PAGE = 'page';
    public const PER_PAGE = 'perPage';
    public const WITH_IMAGES_ONLY = 'withImagesOnly';
    public const FEATURED_FIRST = 'featuredFirst';

    public ?string $query;
    public ?int $categoryId;
    public ?string $condition;
    public ?float $minPrice;
    public ?float $maxPrice;
    public ?string $location;
    public ?float $latitude;
    public ?float $longitude;
    public int $radius;
    public string $sort;
    public int $page;
    public int $perPage;
    public bool $withImagesOnly;
    public bool $featuredFirst;
}