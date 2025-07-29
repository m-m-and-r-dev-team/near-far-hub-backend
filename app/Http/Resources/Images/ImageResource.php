<?php

declare(strict_types=1);

namespace App\Http\Resources\Images;

use App\Models\Images\Image;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ImageResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var Image $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'isPrimary' => Image::IS_PRIMARY,
        'createdAt' => Image::CREATED_AT,
    ];

    public function toArray($request): array
    {
        return [
            'id' => $this->resource->getId(),
            'relatedId' => $this->resource->getRelatedId(),
            'imageUrl' => Storage::disk('s3')->url($this->resource->getType() . '/' . $this->resource->getImageLink()),
        ];
    }
}