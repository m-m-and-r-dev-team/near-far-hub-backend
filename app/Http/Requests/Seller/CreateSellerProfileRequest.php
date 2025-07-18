<?php

// app/Http/Requests/Seller/CreateSellerProfileRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Http\DataTransferObjects\Seller\CreateSellerProfileData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class CreateSellerProfileRequest extends FormRequest
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
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(2),
                ValidationRuleHelper::max(100)
            ],
            self::BUSINESS_DESCRIPTION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(1000)
            ],
            self::BUSINESS_TYPE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                'in:individual,business,company'
            ],
            self::PHONE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(10),
                ValidationRuleHelper::max(20)
            ],
            self::ADDRESS => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::CITY => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::POSTAL_CODE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(20)
            ],
            self::COUNTRY => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): CreateSellerProfileData
    {
        return new CreateSellerProfileData([
            CreateSellerProfileData::BUSINESS_NAME => $this->input(self::BUSINESS_NAME),
            CreateSellerProfileData::BUSINESS_DESCRIPTION => $this->input(self::BUSINESS_DESCRIPTION),
            CreateSellerProfileData::BUSINESS_TYPE => $this->input(self::BUSINESS_TYPE),
            CreateSellerProfileData::PHONE => $this->input(self::PHONE),
            CreateSellerProfileData::ADDRESS => $this->input(self::ADDRESS),
            CreateSellerProfileData::CITY => $this->input(self::CITY),
            CreateSellerProfileData::POSTAL_CODE => $this->input(self::POSTAL_CODE),
            CreateSellerProfileData::COUNTRY => $this->input(self::COUNTRY),
        ]);
    }
}