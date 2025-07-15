<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Models\SellerAvailabilities\SellerAvailability;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerAvailabilityResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var SellerAvailability $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'dayOfWeek' => SellerAvailability::DAY_OF_WEEK,
        'startTime' => SellerAvailability::START_TIME,
        'endTime' => SellerAvailability::END_TIME,
        'isActive' => SellerAvailability::IS_ACTIVE,
        'createdAt' => SellerAvailability::CREATED_AT,
        'updatedAt' => SellerAvailability::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
        ];
    }
}