<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class ListingSearchData extends DataTransferObject
{
    public const QUERY = 'query';
    public const CATEGORY = 'category';
    public const CONDITION = 'condition';
    public const MIN_PRICE = 'minPrice';
    public const MAX_PRICE = 'maxPrice';
    public const LOCATION = 'location';
    public const RADIUS = 'radius';
    public const CAN_DELIVER_GLOBALLY = 'canDeliverGlobally';
    public const REQUIRES_APPOINTMENT = 'requiresAppointment';
    public const SORT_BY = 'sortBy';
    public const SORT_DIRECTION = 'sortDirection';
    public const PAGE = 'page';
    public const PER_PAGE = 'perPage';

    public ?string $query;
    public ?string $category;
    public ?string $condition;
    public ?float $minPrice;
    public ?float $maxPrice;
    public ?array $location;
    public ?int $radius;
    public ?bool $canDeliverGlobally;
    public ?bool $requiresAppointment;
    public string $sortBy;
    public string $sortDirection;
    public int $page;
    public int $perPage;
}