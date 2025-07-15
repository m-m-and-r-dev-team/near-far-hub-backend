<?php

declare(strict_types=1);

namespace App\Http\Resources\Users;

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
        ];
    }
}