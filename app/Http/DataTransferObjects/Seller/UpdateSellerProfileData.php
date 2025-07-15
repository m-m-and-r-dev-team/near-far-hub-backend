<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Seller;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateSellerProfileData extends DataTransferObject
{
    public const BUSINESS_NAME = 'businessName';
    public const BUSINESS_DESCRIPTION = 'businessDescription';
    public const BUSINESS_TYPE = 'businessType';
    public const PHONE = 'phone';
    public const ADDRESS = 'address';
    public const CITY = 'city';
    public const POSTAL_CODE = 'postalCode';
    public const COUNTRY = 'country';

    public ?string $businessName;
    public ?string $businessDescription;
    public ?string $businessType;
    public ?string $phone;
    public ?string $address;
    public ?string $city;
    public ?string $postalCode;
    public ?string $country;
}