<?php

declare(strict_types=1);

namespace App\Http\Resources\Images;

use App\Models\Images\Image;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * @var Image $resource
     */
    public $resource;

    public function toArray($request): array
    {
        return [
            'id' => $this->resource->getId(),
            'type' => $this->resource->getType(),
            'altText' => $this->resource->getAltText(),
            'sortOrder' => $this->resource->getSortOrder(),
            'isPrimary' => $this->resource->getIsPrimary(),
            'isActive' => $this->resource->getIsActive(),
            'width' => $this->resource->getWidth(),
            'height' => $this->resource->getHeight(),
            'fileSize' => $this->resource->getFileSize(),
            'formattedFileSize' => $this->resource->getFormattedFileSize(),
            'url' => $this->resource->getUrl(),
            'thumbnails' => $this->when(
                !empty($this->resource->getMetadata()['thumbnails']),
                $this->resource->getMetadata()['thumbnails'] ?? []
            ),
            'metadata' => $this->resource->getMetadata(),
            'createdAt' => $this->resource->getCreatedAt()->toISOString(),
        ];
    }
}