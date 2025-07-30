<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Http\DataTransferObjects\Listings\UpdateListingData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use App\Models\Listings\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateListingRequest extends FormRequest
{
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
            self::TITLE => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(3),
                ValidationRuleHelper::max(100)
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
            self::ORIGINAL_PRICE => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0.01',
                'max:999999.99'
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
            self::CAN_DELIVER_GLOBALLY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::DELIVERY_OPTIONS => [
                ValidationRuleHelper::NULLABLE,
                'array'
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
                'in:' . implode(',', array_keys(Listing::getStatuses()))
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
                'max:5120'
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
            UpdateListingData::ORIGINAL_PRICE => $this->input(self::ORIGINAL_PRICE) ? (float) $this->input(self::ORIGINAL_PRICE) : null,
            UpdateListingData::CONDITION => $this->input(self::CONDITION),
            UpdateListingData::BRAND => $this->input(self::BRAND),
            UpdateListingData::MODEL => $this->input(self::MODEL),
            UpdateListingData::YEAR => $this->input(self::YEAR) ? (int) $this->input(self::YEAR) : null,
            UpdateListingData::LOCATION => $this->input(self::LOCATION),
            UpdateListingData::CAN_DELIVER_GLOBALLY => $this->input(self::CAN_DELIVER_GLOBALLY) !== null ? $this->boolean(self::CAN_DELIVER_GLOBALLY) : null,
            UpdateListingData::DELIVERY_OPTIONS => $this->input(self::DELIVERY_OPTIONS),
            UpdateListingData::REQUIRES_APPOINTMENT => $this->input(self::REQUIRES_APPOINTMENT) !== null ? $this->boolean(self::REQUIRES_APPOINTMENT) : null,
            UpdateListingData::CATEGORY_ATTRIBUTES => $this->input(self::CATEGORY_ATTRIBUTES),
            UpdateListingData::TAGS => $this->input(self::TAGS),
            UpdateListingData::STATUS => $this->input(self::STATUS),
            UpdateListingData::META_TITLE => $this->input(self::META_TITLE),
            UpdateListingData::META_DESCRIPTION => $this->input(self::META_DESCRIPTION),
        ]);
    }
}