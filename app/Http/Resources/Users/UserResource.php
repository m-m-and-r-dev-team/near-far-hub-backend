<?php

declare(strict_types=1);

namespace App\Http\Resources\Users;

use App\Http\Resources\Roles\RoleResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @var User $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'name' => User::NAME,
        'email' => User::EMAIL,
        'emailVerifiedAt' => User::EMAIL_VERIFIED_AT,
        'createdAt' => User::CREATED_AT,
        'updatedAt' => User::UPDATED_AT,
    ];

    protected function getData(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'role' => RoleResource::make($this->resource->relatedRole()),
            'permissions' => [
                'canSell' => $this->resource->canSell(),
                'canModerate' => $this->resource->canModerate(),
                'canAccessAdmin' => $this->resource->canAccessAdmin(),
                'canUpgradeToSeller' => $this->resource->canUpgradeToSeller(),
            ],
        ];
    }
}