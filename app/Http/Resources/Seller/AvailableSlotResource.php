<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'datetime' => $this->resource['datetime'],
            'startTime' => $this->resource['start_time'],
            'endTime' => $this->resource['end_time'],
            'available' => $this->resource['available'],
        ];
    }
}