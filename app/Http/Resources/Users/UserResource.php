<?php

declare(strict_types=1);

namespace App\Http\Resources\Users;

use App\Http\Resources\Roles\RoleResource;
use App\Models\User;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var User $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'name' => User::NAME,
        'email' => User::EMAIL,
        'phone' => User::PHONE,
        'bio' => User::BIO,
        'emailVerifiedAt' => User::EMAIL_VERIFIED_AT,
        'createdAt' => User::CREATED_AT,
        'updatedAt' => User::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'isSeller' => $this->resource->isSeller(),
            'isVerifiedSeller' => $this->resource->isVerifiedSeller(),
            'hasActiveSellerAccount' => $this->resource->hasActiveSellerAccount(),
            'role' => RoleResource::make($this->resource->relatedRole()),

            'location' => [
                'display' => $this->resource->location_display,
                'fullLocation' => $this->resource->full_location,
                'hasLocationData' => $this->resource->hasLocationData(),
                'coordinates' => $this->resource->getCoordinates(),
                'data' => $this->resource->location_data,
                'country' => $this->when($this->resource->country, [
                    'id' => $this->resource->country?->id,
                    'name' => $this->resource->country?->name,
                    'code' => $this->resource->country?->code,
                ]),
                'state' => $this->when($this->resource->state, [
                    'id' => $this->resource->state?->id,
                    'name' => $this->resource->state?->name,
                    'code' => $this->resource->state?->code,
                ]),
                'city' => $this->when($this->resource->city, [
                    'id' => $this->resource->city?->id,
                    'name' => $this->resource->city?->name,
                    'fullName' => $this->resource->city?->full_name,
                ]),
                'addressLine' => $this->resource->address_line,
                'postalCode' => $this->resource->postal_code,
                'googlePlaceId' => $this->resource->google_place_id,
            ],

            'permissions' => [
                'canSell' => $this->resource->canSell(),
                'canModerate' => $this->resource->canModerate(),
                'canAccessAdmin' => $this->resource->canAccessAdmin(),
                'canUpgradeToSeller' => $this->resource->canUpgradeToSeller(),
            ],
        ];
    }
}