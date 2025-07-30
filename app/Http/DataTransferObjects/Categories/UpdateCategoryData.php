<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Categories;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateCategoryData extends DataTransferObject
{
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const PARENT_ID = 'parentId';
    public const ICON = 'icon';
    public const COLOR = 'color';
    public const SORT_ORDER = 'sortOrder';
    public const IS_ACTIVE = 'isActive';
    public const IS_FEATURED = 'isFeatured';
    public const META_TITLE = 'metaTitle';
    public const META_DESCRIPTION = 'metaDescription';
    public const ATTRIBUTES = 'attributes';
    public const VALIDATION_RULES = 'validationRules';

    public ?string $name;
    public ?string $description;
    public ?int $parentId;
    public ?string $icon;
    public ?string $color;
    public ?int $sortOrder;
    public ?bool $isActive;
    public ?bool $isFeatured;
    public ?string $metaTitle;
    public ?string $metaDescription;
    public ?array $attributes;
    public ?array $validationRules;
}