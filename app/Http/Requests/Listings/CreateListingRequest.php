<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Enums\Listings\ListingCategoryEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Http\DataTransferObjects\Listings\CreateListingData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class CreateListingRequest extends FormRequest
{
    private const TITLE = 'title';
    private const DESCRIPTION = 'description';
    private const PRICE = 'price';
    private const CATEGORY = 'category';
    private const CONDITION = 'condition';
    private const LOCATION = 'location';
    private const CAN_DELIVER_GLOBALLY = 'can_deliver_globally';
    private const REQUIRES_APPOINTMENT = 'requires_appointment';
    private const TAGS = 'tags';
    private const DELIVERY_OPTIONS = 'delivery_options';
    private const DIMENSIONS = 'dimensions';
    private const WEIGHT = 'weight';
    private const BRAND = 'brand';
    private const MODEL = 'model';
    private const YEAR = 'year';
    private const COLOR = 'color';
    private const MATERIAL = 'material';
    private const EXPIRES_AT = 'expires_at';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryValues = array_column(ListingCategoryEnum::cases(), 'value');
        $conditionValues = array_column(ListingConditionEnum::cases(), 'value');

        return [
            self::TITLE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(3),
                ValidationRuleHelper::max(255)
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
            self::CATEGORY => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                Rule::in($categoryValues)
            ],
            self::CONDITION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                Rule::in($conditionValues)
            ],
            self::LOCATION => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::LOCATION . '.description' => [
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
            self::REQUIRES_APPOINTMENT => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::TAGS => [
                ValidationRuleHelper::NULLABLE,
                'array',
                'max:10'
            ],
            self::TAGS . '.*' => [
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::DELIVERY_OPTIONS => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::DIMENSIONS => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::DIMENSIONS . '.length' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0'
            ],
            self::DIMENSIONS . '.width' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0'
            ],
            self::DIMENSIONS . '.height' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0'
            ],
            self::DIMENSIONS . '.unit' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                Rule::in(['cm', 'in', 'm', 'ft'])
            ],
            self::WEIGHT => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0'
            ],
            self::BRAND => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::MODEL => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::YEAR => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'min:1900',
                'max:' . (date('Y') + 1)
            ],
            self::COLOR => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::MATERIAL => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::EXPIRES_AT => [
                ValidationRuleHelper::NULLABLE,
                'date',
                'after:now'
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): CreateListingData
    {
        return new CreateListingData([
            CreateListingData::TITLE => $this->input(self::TITLE),
            CreateListingData::DESCRIPTION => $this->input(self::DESCRIPTION),
            CreateListingData::PRICE => (float) $this->input(self::PRICE),
            CreateListingData::CATEGORY => $this->input(self::CATEGORY),
            CreateListingData::CONDITION => $this->input(self::CONDITION),
            CreateListingData::LOCATION => $this->input(self::LOCATION),
            CreateListingData::CAN_DELIVER_GLOBALLY => $this->boolean(self::CAN_DELIVER_GLOBALLY),
            CreateListingData::REQUIRES_APPOINTMENT => $this->boolean(self::REQUIRES_APPOINTMENT),
            CreateListingData::TAGS => $this->input(self::TAGS),
            CreateListingData::DELIVERY_OPTIONS => $this->input(self::DELIVERY_OPTIONS),
            CreateListingData::DIMENSIONS => $this->input(self::DIMENSIONS),
            CreateListingData::WEIGHT => $this->input(self::WEIGHT) ? (float) $this->input(self::WEIGHT) : null,
            CreateListingData::BRAND => $this->input(self::BRAND),
            CreateListingData::MODEL => $this->input(self::MODEL),
            CreateListingData::YEAR => $this->input(self::YEAR) ? (int) $this->input(self::YEAR) : null,
            CreateListingData::COLOR => $this->input(self::COLOR),
            CreateListingData::MATERIAL => $this->input(self::MATERIAL),
            CreateListingData::EXPIRES_AT => $this->input(self::EXPIRES_AT),
        ]);
    }
}