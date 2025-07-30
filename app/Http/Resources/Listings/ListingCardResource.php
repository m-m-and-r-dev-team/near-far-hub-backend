<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use App\Http\Resources\Categories\CategoryResource;
use App\Models\Listings\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingCardResource extends JsonResource
{
    /**
     * @var Listing $resource
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'title' => $this->resource->getTitle(),
            'slug' => $this->resource->getSlug(),
            'price' => $this->resource->getPrice(),
            'originalPrice' => $this->resource->getOriginalPrice(),
            'condition' => $this->resource->getCondition(),
            'brand' => $this->resource->getBrand(),
            'locationDisplay' => $this->resource->getLocationDisplay(),
            'viewsCount' => $this->resource->getViewsCount(),
            'favoritesCount' => $this->resource->getFavoritesCount(),
            'isFeatured' => $this->resource->isFeatured(),
            'hasDiscount' => $this->resource->hasDiscount(),
            'discountPercentage' => $this->resource->getDiscountPercentage(),
            'publishedAt' => $this->resource->getPublishedAt()?->toISOString(),
            'primaryImageUrl' => $this->resource->getPrimaryImageUrl(),
            'seller' => $this->whenLoaded(Listing::SELLER_PROFILE_RELATION, function () {
                $seller = $this->resource->relatedSellerProfile();
                return [
                    'id' => $seller->getId(),
                    'businessName' => $seller->getAttribute('business_name'),
                    'isVerified' => $seller->getIsVerified(),
                ];
            }),
            'category' => $this->whenLoaded(Listing::CATEGORY_RELATION, function () {
                $category = $this->resource->relatedCategory();
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                ];
            }),
        ];
    }
}