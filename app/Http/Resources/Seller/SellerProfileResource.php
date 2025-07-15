<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Http\Resources\Users\UserResource;
use App\Models\SellerProfiles\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProfileResource extends JsonResource
{
    /**
     * @var SellerProfile $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'businessName' => SellerProfile::BUSINESS_NAME,
        'businessDescription' => SellerProfile::BUSINESS_DESCRIPTION,
        'businessType' => SellerProfile::BUSINESS_TYPE,
        'phone' => SellerProfile::PHONE,
        'address' => SellerProfile::ADDRESS,
        'city' => SellerProfile::CITY,
        'postalCode' => SellerProfile::POSTAL_CODE,
        'country' => SellerProfile::COUNTRY,
        'listingFeeBalance' => SellerProfile::LISTING_FEE_BALANCE,
        'isActive' => SellerProfile::IS_ACTIVE,
        'isVerified' => SellerProfile::IS_VERIFIED,
        'verifiedAt' => SellerProfile::VERIFIED_AT,
        'createdAt' => SellerProfile::CREATED_AT,
        'updatedAt' => SellerProfile::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'fullAddress' => $this->resource->getFullAddressAttribute(),
            'user' => UserResource::make($this->resource->relatedUser()),
            'availability' => SellerAvailabilityResource::collection($this->whenLoaded(SellerProfile::AVAILABILITY_RELATION)),
            'stats' => $this->when(isset($this->resource->stats), $this->resource->stats),
        ];
    }
}