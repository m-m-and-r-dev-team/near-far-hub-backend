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

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'name' => $this->resource->getName(),
            'email' => $this->resource->getEmail(),
            'role' => RoleResource::make($this->resource->relatedRole()),
            'emailVerifiedAt' => $this->resource->getEmailVerifiedAt(),
            'createdAt' => $this->resource->getCreatedAt(),
            'updatedAt' => $this->resource->getUpdatedAt(),
            'permissions' => [
                'canSell' => $this->resource->canSell(),
                'canModerate' => $this->resource->canModerate(),
                'canAccessAdmin' => $this->resource->canAccessAdmin(),
                'canUpgradeToSeller' => $this->resource->canUpgradeToSeller(),
            ],
        ];
    }
}