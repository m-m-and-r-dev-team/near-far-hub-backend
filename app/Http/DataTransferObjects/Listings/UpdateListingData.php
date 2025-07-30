<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateListingData extends DataTransferObject
{
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const PRICE = 'price';
    public const ORIGINAL_PRICE = 'originalPrice';
    public const CONDITION = 'condition';
    public const BRAND = 'brand';
    public const MODEL = 'model';
    public const YEAR = 'year';
    public const LOCATION = 'location';
    public const CAN_DELIVER_GLOBALLY = 'canDeliverGlobally';
    public const DELIVERY_OPTIONS = 'deliveryOptions';
    public const REQUIRES_APPOINTMENT = 'requiresAppointment';
    public const CATEGORY_ATTRIBUTES = 'categoryAttributes';
    public const TAGS = 'tags';
    public const STATUS = 'status';
    public const META_TITLE = 'metaTitle';
    public const META_DESCRIPTION = 'metaDescription';

    public ?string $title;
    public ?string $description;
    public ?float $price;
    public ?float $originalPrice;
    public ?string $condition;
    public ?string $brand;
    public ?string $model;
    public ?int $year;
    public ?array $location;
    public ?bool $canDeliverGlobally;
    public ?array $deliveryOptions;
    public ?bool $requiresAppointment;
    public ?array $categoryAttributes;
    public ?array $tags;
    public ?string $status;
    public ?string $metaTitle;
    public ?string $metaDescription;
}