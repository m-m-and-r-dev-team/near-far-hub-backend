<?php

declare(strict_types=1);

namespace App\Http\Resources\Categories;

use App\Models\Categories\Category;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var Category $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'name' => Category::NAME,
        'slug' => Category::SLUG,
        'description' => Category::DESCRIPTION,
        'icon' => Category::ICON,
        'color' => Category::COLOR,
        'sortOrder' => Category::SORT_ORDER,
        'isActive' => Category::IS_ACTIVE,
        'isFeatured' => Category::IS_FEATURED,
        'metaTitle' => Category::META_TITLE,
        'metaDescription' => Category::META_DESCRIPTION,
        'createdAt' => Category::CREATED_AT,
        'updatedAt' => Category::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        $conditionalData = $this->getConditionalData($request);

        return array_merge($conditionalData, [
            'id' => $this->resource->getId(),
            'parentId' => $this->resource->getParentId(),
            'isParent' => $this->resource->isParent(),
            'isChild' => $this->resource->isChild(),
            'isRoot' => $this->resource->isRoot(),
            'depth' => $this->resource->getDepth(),
            'breadcrumb' => $this->resource->getBreadcrumb(),
            'pathNames' => $this->resource->getPathNames(),
            'attributes' => $this->resource->getAttributes(),
            'validationRules' => $this->resource->getValidationRules(),
            'requiredAttributes' => $this->resource->getRequiredAttributes(),
            'parent' => $this->whenLoaded(Category::PARENT_RELATION, function () {
                return new CategoryResource($this->resource->relatedParent());
            }),
            'children' => $this->whenLoaded(Category::CHILDREN_RELATION, function () {
                return CategoryResource::collection($this->resource->relatedChildren());
            }),
            'listingsCount' => $this->whenLoaded(Category::LISTINGS_RELATION, function () {
                return $this->resource->relatedListings()->count();
            }),
        ]);
    }
}