<?php

declare(strict_types=1);

namespace App\Http\Resources\Roles;

use App\Models\Roles\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @var Role $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'name' => Role::NAME,
        'displayName' => Role::DISPLAY_NAME,
        'description' => Role::DESCRIPTION,
        'permissions' => Role::PERMISSIONS,
        'isActive' => Role::IS_ACTIVE,
        'createdAt' => Role::CREATED_AT,
        'updatedAt' => Role::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
        ];
    }
}