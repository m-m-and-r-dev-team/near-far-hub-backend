<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Enums\Listings\ListingCategoryEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Http\DataTransferObjects\Listings\ListingSearchData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class SearchListingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryValues = array_column(ListingCategoryEnum::cases(), 'value');
        $conditionValues = array_column(ListingConditionEnum::cases(), 'value');

        return [
            'query' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            'category' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                Rule::in($categoryValues)
            ],
            'condition' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                Rule::in($conditionValues)
            ],
            'min_price' => [
                ValidationRuleHelper::SOMETIMES,
                'numeric',
                'min:0'
            ],
            'max_price' => [
                ValidationRuleHelper::SOMETIMES,
                'numeric',
                'min:0',
                'gte:min_price'
            ],
            'location' => [
                ValidationRuleHelper::SOMETIMES,
                'array'
            ],
            'radius' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:1',
                'max:500'
            ],
            'can_deliver_globally' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            'requires_appointment' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            'sort_by' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                Rule::in(['relevance', 'price', 'date', 'views', 'favorites'])
            ],
            'sort_direction' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                Rule::in(['asc', 'desc'])
            ],
            'page' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:1'
            ],
            'per_page' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:1',
                'max:100'
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): ListingSearchData
    {
        return new ListingSearchData([
            ListingSearchData::QUERY => $this->input('query'),
            ListingSearchData::CATEGORY => $this->input('category'),
            ListingSearchData::CONDITION => $this->input('condition'),
            ListingSearchData::MIN_PRICE => $this->input('min_price') ? (float) $this->input('min_price') : null,
            ListingSearchData::MAX_PRICE => $this->input('max_price') ? (float) $this->input('max_price') : null,
            ListingSearchData::LOCATION => $this->input('location'),
            ListingSearchData::RADIUS => $this->input('radius') ? (int) $this->input('radius') : null,
            ListingSearchData::CAN_DELIVER_GLOBALLY => $this->input('can_deliver_globally') ? $this->boolean('can_deliver_globally') : null,
            ListingSearchData::REQUIRES_APPOINTMENT => $this->input('requires_appointment') ? $this->boolean('requires_appointment') : null,
            ListingSearchData::SORT_BY => $this->input('sort_by', 'relevance'),
            ListingSearchData::SORT_DIRECTION => $this->input('sort_direction', 'desc'),
            ListingSearchData::PAGE => (int) $this->input('page', 1),
            ListingSearchData::PER_PAGE => (int) $this->input('per_page', 20),
        ]);
    }
}