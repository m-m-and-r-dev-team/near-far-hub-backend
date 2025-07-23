<?php

declare(strict_types=1);

namespace App\Http\Resources\Images;

use App\Models\Images\Image;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var Image $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'filename' => Image::FILENAME,
        'originalName' => Image::ORIGINAL_NAME,
        'path' => Image::PATH,
        'url' => Image::URL,
        'size' => Image::SIZE,
        'mimeType' => Image::MIME_TYPE,
        'width' => Image::WIDTH,
        'height' => Image::HEIGHT,
        'altText' => Image::ALT_TEXT,
        'sortOrder' => Image::SORT_ORDER,
        'isPrimary' => Image::IS_PRIMARY,
        'isActive' => Image::IS_ACTIVE,
        'createdAt' => Image::CREATED_AT,
        'updatedAt' => Image::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        $conditionalData = $this->getConditionalData($request);

        return array_merge($conditionalData, [
            'id' => $this->resource->getId(),
            'type' => $this->resource->getType()->value,
            'formattedSize' => $this->resource->getFormattedSize(),
            'dimensions' => $this->resource->getDimensions(),
            'isImage' => $this->resource->isImage(),

            // Thumbnails from metadata
            'thumbnails' => $this->when(
                !empty($this->resource->getMetadata()['thumbnails']),
                $this->resource->getMetadata()['thumbnails'] ?? []
            ),

            // Additional metadata
            'metadata' => $this->when(
                !empty($this->resource->getMetadata()),
                $this->resource->getMetadata()
            ),
        ]);
    }
}