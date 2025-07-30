<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Http\DataTransferObjects\Listings\CreateListingData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use App\Models\Listings\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class CreateListingRequest extends FormRequest
{
    private const CATEGORY_ID = 'category_id';
    private const TITLE = 'title';
    private const DESCRIPTION = 'description';
    private const PRICE = 'price';
    private const ORIGINAL_PRICE = 'original_price';
    private const CONDITION = 'condition';
    private const BRAND = 'brand';
    private const MODEL = 'model';
    private const YEAR = 'year';
    private const LOCATION = 'location';
    private const CAN_DELIVER_GLOBALLY = 'can_deliver_globally';
    private const DELIVERY_OPTIONS = 'delivery_options';
    private const REQUIRES_APPOINTMENT = 'requires_appointment';
    private const CATEGORY_ATTRIBUTES = 'category_attributes';
    private const TAGS = 'tags';
    private const STATUS = 'status';
    private const META_TITLE = 'meta_title';
    private const META_DESCRIPTION = 'meta_description';
    private const IMAGES = 'images';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::CATEGORY_ID => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::INTEGER,
                'exists:categories,id'
            ],
            self::TITLE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(3),
                ValidationRuleHelper::max(100)
            ],
            self::DESCRIPTION => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(10),
                ValidationRuleHelper::max(5000)
            ],
            self::PRICE => [
                ValidationRuleHelper::REQUIRED,
                'numeric',
                'min:0.01',
                'max:999999.99'
            ],
            self::ORIGINAL_PRICE => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0.01',
                'max:999999.99',
                'gt:price'
            ],
            self::CONDITION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'in:' . implode(',', array_keys(Listing::getConditions()))
            ],
            self::BRAND => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::MODEL => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::YEAR => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'min:1900',
                'max:' . (date('Y') + 1)
            ],
            self::LOCATION => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::LOCATION . '.display' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::LOCATION . '.latitude' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'between:-90,90'
            ],
            self::LOCATION . '.longitude' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'between:-180,180'
            ],
            self::CAN_DELIVER_GLOBALLY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::DELIVERY_OPTIONS => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::DELIVERY_OPTIONS . '.*' => [
                ValidationRuleHelper::STRING,
                'in:pickup,local_delivery,national_shipping,international_shipping'
            ],
            self::REQUIRES_APPOINTMENT => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::CATEGORY_ATTRIBUTES => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::TAGS => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::TAGS . '.*' => [
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::STATUS => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                'in:' . Listing::STATUS_DRAFT . ',' . Listing::STATUS_ACTIVE
            ],
            self::META_TITLE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(60)
            ],
            self::META_DESCRIPTION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(160)
            ],
            self::IMAGES => [
                ValidationRuleHelper::NULLABLE,
                'array',
                'max:10'
            ],
            self::IMAGES . '.*' => [
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120' // 5MB
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): CreateListingData
    {
        return new CreateListingData([
            CreateListingData::CATEGORY_ID => $this->input(self::CATEGORY_ID),
            CreateListingData::TITLE => $this->input(self::TITLE),
            CreateListingData::DESCRIPTION => $this->input(self::DESCRIPTION),
            CreateListingData::PRICE => (float) $this->input(self::PRICE),
            CreateListingData::ORIGINAL_PRICE => $this->input(self::ORIGINAL_PRICE) ? (float) $this->input(self::ORIGINAL_PRICE) : null,
            CreateListingData::CONDITION => $this->input(self::CONDITION),
            CreateListingData::BRAND => $this->input(self::BRAND),
            CreateListingData::MODEL => $this->input(self::MODEL),
            CreateListingData::YEAR => $this->input(self::YEAR) ? (int) $this->input(self::YEAR) : null,
            CreateListingData::LOCATION => $this->input(self::LOCATION),
            CreateListingData::CAN_DELIVER_GLOBALLY => $this->boolean(self::CAN_DELIVER_GLOBALLY),
            CreateListingData::DELIVERY_OPTIONS => $this->input(self::DELIVERY_OPTIONS, []),
            CreateListingData::REQUIRES_APPOINTMENT => $this->boolean(self::REQUIRES_APPOINTMENT),
            CreateListingData::CATEGORY_ATTRIBUTES => $this->input(self::CATEGORY_ATTRIBUTES, []),
            CreateListingData::TAGS => $this->input(self::TAGS, []),
            CreateListingData::STATUS => $this->input(self::STATUS, Listing::STATUS_DRAFT),
            CreateListingData::META_TITLE => $this->input(self::META_TITLE),
            CreateListingData::META_DESCRIPTION => $this->input(self::META_DESCRIPTION),
        ]);
    }
}