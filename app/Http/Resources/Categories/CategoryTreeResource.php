<?php

declare(strict_types=1);

namespace App\Http\Resources\Categories;

use App\Models\Categories\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * @var Category $resource
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'name' => $this->resource->getName(),
            'slug' => $this->resource->getSlug(),
            'description' => $this->resource->getDescription(),
            'icon' => $this->resource->getIcon(),
            'color' => $this->resource->getColor(),
            'parentId' => $this->resource->getParentId(),
            'sortOrder' => $this->resource->getSortOrder(),
            'isActive' => $this->resource->getIsActive(),
            'isFeatured' => $this->resource->getIsFeatured(),
            'isParent' => $this->resource->isParent(),
            'depth' => $this->resource->getDepth(),
            'children' => $this->when($this->resource->isParent(), function () {
                return CategoryTreeResource::collection($this->resource->relatedChildren());
            }),
            'listingsCount' => $this->when($this->resource->relationLoaded('listingsRelation'), function () {
                return $this->resource->relatedListings()->where('status', 'active')->count();
            }),
        ];
    }
}