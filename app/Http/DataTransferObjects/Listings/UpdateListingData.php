<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateListingData extends DataTransferObject
{
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const PRICE = 'price';
    public const CATEGORY = 'category';
    public const CONDITION = 'condition';
    public const LOCATION = 'location';
    public const CAN_DELIVER_GLOBALLY = 'canDeliverGlobally';
    public const REQUIRES_APPOINTMENT = 'requiresAppointment';
    public const TAGS = 'tags';
    public const DELIVERY_OPTIONS = 'deliveryOptions';
    public const DIMENSIONS = 'dimensions';
    public const WEIGHT = 'weight';
    public const BRAND = 'brand';
    public const MODEL = 'model';
    public const YEAR = 'year';
    public const COLOR = 'color';
    public const MATERIAL = 'material';
    public const EXPIRES_AT = 'expiresAt';

    public ?string $title;
    public ?string $description;
    public ?float $price;
    public ?string $category;
    public ?string $condition;
    public ?array $location;
    public ?bool $canDeliverGlobally;
    public ?bool $requiresAppointment;
    public ?array $tags;
    public ?array $deliveryOptions;
    public ?array $dimensions;
    public ?float $weight;
    public ?string $brand;
    public ?string $model;
    public ?int $year;
    public ?string $color;
    public ?string $material;
    public ?string $expiresAt;
}