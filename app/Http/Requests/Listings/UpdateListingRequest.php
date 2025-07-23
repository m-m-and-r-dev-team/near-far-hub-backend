<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Enums\Listings\ListingCategoryEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Http\DataTransferObjects\Listings\UpdateListingData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateListingRequest extends FormRequest
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
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(3),
                ValidationRuleHelper::max(255)
            ],
            self::DESCRIPTION => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(10),
                ValidationRuleHelper::max(5000)
            ],
            self::PRICE => [
                ValidationRuleHelper::SOMETIMES,
                'numeric',
                'min:0.01',
                'max:999999.99'
            ],
            self::CATEGORY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                Rule::in($categoryValues)
            ],
            self::CONDITION => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                Rule::in($conditionValues)
            ],
            self::LOCATION => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'array'
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
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'array',
                'max:10'
            ],
            self::TAGS . '.*' => [
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::DELIVERY_OPTIONS => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::DIMENSIONS => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::WEIGHT => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0'
            ],
            self::BRAND => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::MODEL => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::YEAR => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'min:1900',
                'max:' . (date('Y') + 1)
            ],
            self::COLOR => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::MATERIAL => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::EXPIRES_AT => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'date',
                'after:now'
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UpdateListingData
    {
        return new UpdateListingData([
            UpdateListingData::TITLE => $this->input(self::TITLE),
            UpdateListingData::DESCRIPTION => $this->input(self::DESCRIPTION),
            UpdateListingData::PRICE => $this->input(self::PRICE) ? (float) $this->input(self::PRICE) : null,
            UpdateListingData::CATEGORY => $this->input(self::CATEGORY),
            UpdateListingData::CONDITION => $this->input(self::CONDITION),
            UpdateListingData::LOCATION => $this->input(self::LOCATION),
            UpdateListingData::CAN_DELIVER_GLOBALLY => $this->has(self::CAN_DELIVER_GLOBALLY) ? $this->boolean(self::CAN_DELIVER_GLOBALLY) : null,
            UpdateListingData::REQUIRES_APPOINTMENT => $this->has(self::REQUIRES_APPOINTMENT) ? $this->boolean(self::REQUIRES_APPOINTMENT) : null,
            UpdateListingData::TAGS => $this->input(self::TAGS),
            UpdateListingData::DELIVERY_OPTIONS => $this->input(self::DELIVERY_OPTIONS),
            UpdateListingData::DIMENSIONS => $this->input(self::DIMENSIONS),
            UpdateListingData::WEIGHT => $this->input(self::WEIGHT) ? (float) $this->input(self::WEIGHT) : null,
            UpdateListingData::BRAND => $this->input(self::BRAND),
            UpdateListingData::MODEL => $this->input(self::MODEL),
            UpdateListingData::YEAR => $this->input(self::YEAR) ? (int) $this->input(self::YEAR) : null,
            UpdateListingData::COLOR => $this->input(self::COLOR),
            UpdateListingData::MATERIAL => $this->input(self::MATERIAL),
            UpdateListingData::EXPIRES_AT => $this->input(self::EXPIRES_AT),
        ]);
    }
}