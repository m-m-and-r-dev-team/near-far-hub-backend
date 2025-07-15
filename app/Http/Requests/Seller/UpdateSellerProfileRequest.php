<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Http\DataTransferObjects\Seller\UpdateSellerProfileData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateSellerProfileRequest extends FormRequest
{
    private const BUSINESS_NAME = 'business_name';
    private const BUSINESS_DESCRIPTION = 'business_description';
    private const BUSINESS_TYPE = 'business_type';
    private const PHONE = 'phone';
    private const ADDRESS = 'address';
    private const CITY = 'city';
    private const POSTAL_CODE = 'postal_code';
    private const COUNTRY = 'country';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::BUSINESS_NAME => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(2),
                ValidationRuleHelper::max(100)
            ],
            self::BUSINESS_DESCRIPTION => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(1000)
            ],
            self::BUSINESS_TYPE => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                'in:individual,business,company'
            ],
            self::PHONE => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(10),
                ValidationRuleHelper::max(20)
            ],
            self::ADDRESS => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::CITY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::POSTAL_CODE => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(20)
            ],
            self::COUNTRY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UpdateSellerProfileData
    {
        return new UpdateSellerProfileData([
            UpdateSellerProfileData::BUSINESS_NAME => $this->input(self::BUSINESS_NAME),
            UpdateSellerProfileData::BUSINESS_DESCRIPTION => $this->input(self::BUSINESS_DESCRIPTION),
            UpdateSellerProfileData::BUSINESS_TYPE => $this->input(self::BUSINESS_TYPE),
            UpdateSellerProfileData::PHONE => $this->input(self::PHONE),
            UpdateSellerProfileData::ADDRESS => $this->input(self::ADDRESS),
            UpdateSellerProfileData::CITY => $this->input(self::CITY),
            UpdateSellerProfileData::POSTAL_CODE => $this->input(self::POSTAL_CODE),
            UpdateSellerProfileData::COUNTRY => $this->input(self::COUNTRY),
        ]);
    }
}