<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Http\DataTransferObjects\Listings\SearchListingsData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class SearchListingsRequest extends FormRequest
{
    private const QUERY = 'q';
    private const CATEGORY_ID = 'category_id';
    private const CONDITION = 'condition';
    private const MIN_PRICE = 'min_price';
    private const MAX_PRICE = 'max_price';
    private const LOCATION = 'location';
    private const LATITUDE = 'latitude';
    private const LONGITUDE = 'longitude';
    private const RADIUS = 'radius';
    private const SORT = 'sort';
    private const PAGE = 'page';
    private const PER_PAGE = 'per_page';
    private const WITH_IMAGES_ONLY = 'with_images_only';
    private const FEATURED_FIRST = 'featured_first';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::QUERY => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(2),
                ValidationRuleHelper::max(200)
            ],
            self::CATEGORY_ID => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'exists:categories,id'
            ],
            self::CONDITION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'in:new,like_new,excellent,good,fair,poor'
            ],
            self::MIN_PRICE => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0'
            ],
            self::MAX_PRICE => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'min:0',
                'gte:min_price'
            ],
            self::LOCATION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::LATITUDE => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'between:-90,90'
            ],
            self::LONGITUDE => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'between:-180,180'
            ],
            self::RADIUS => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'min:1',
                'max:500'
            ],
            self::SORT => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'in:recent,price_low,price_high,popular,distance,relevant'
            ],
            self::PAGE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'min:1'
            ],
            self::PER_PAGE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'min:1',
                'max:50'
            ],
            self::WITH_IMAGES_ONLY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::FEATURED_FIRST => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): SearchListingsData
    {
        return new SearchListingsData([
            SearchListingsData::QUERY => $this->input(self::QUERY),
            SearchListingsData::CATEGORY_ID => $this->input(self::CATEGORY_ID) ? (int) $this->input(self::CATEGORY_ID) : null,
            SearchListingsData::CONDITION => $this->input(self::CONDITION),
            SearchListingsData::MIN_PRICE => $this->input(self::MIN_PRICE) ? (float) $this->input(self::MIN_PRICE) : null,
            SearchListingsData::MAX_PRICE => $this->input(self::MAX_PRICE) ? (float) $this->input(self::MAX_PRICE) : null,
            SearchListingsData::LOCATION => $this->input(self::LOCATION),
            SearchListingsData::LATITUDE => $this->input(self::LATITUDE) ? (float) $this->input(self::LATITUDE) : null,
            SearchListingsData::LONGITUDE => $this->input(self::LONGITUDE) ? (float) $this->input(self::LONGITUDE) : null,
            SearchListingsData::RADIUS => $this->input(self::RADIUS, 50),
            SearchListingsData::SORT => $this->input(self::SORT, 'recent'),
            SearchListingsData::PAGE => $this->input(self::PAGE, 1),
            SearchListingsData::PER_PAGE => $this->input(self::PER_PAGE, 20),
            SearchListingsData::WITH_IMAGES_ONLY => $this->boolean(self::WITH_IMAGES_ONLY),
            SearchListingsData::FEATURED_FIRST => $this->boolean(self::FEATURED_FIRST, true),
        ]);
    }

    public function getQuery(): ?string
    {
        return $this->input(self::QUERY);
    }
}