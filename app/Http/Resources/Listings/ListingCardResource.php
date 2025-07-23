<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use App\Http\Resources\Images\ImageResource;
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
            'price' => $this->resource->getPrice(),
            'formattedPrice' => $this->resource->getFormattedPrice(),
            'category' => $this->resource->getCategory()->value,
            'categoryDisplayName' => $this->resource->getCategoryDisplayName(),
            'condition' => $this->resource->getCondition()?->value,
            'conditionDisplayName' => $this->resource->getConditionDisplayName(),
            'slug' => $this->resource->getSlug(),
            'location' => $this->resource->getLocation(),
            'viewsCount' => $this->resource->getViewsCount(),
            'favoritesCount' => $this->resource->getFavoritesCount(),
            'publishedAt' => $this->resource->getPublishedAt()?->toISOString(),

            // Basic seller info
            'sellerName' => $this->when(
                $this->resource->relationLoaded(Listing::SELLER_PROFILE_RELATION),
                fn() => $this->resource->relatedSellerProfile()->relatedUser()->getName()
            ),

            'sellerVerified' => $this->when(
                $this->resource->relationLoaded(Listing::SELLER_PROFILE_RELATION),
                fn() => $this->resource->relatedSellerProfile()->getIsVerified()
            ),

            // Primary image thumbnail
            'thumbnail' => $this->when(
                $this->resource->relationLoaded(Listing::IMAGES_RELATION),
                function() {
                    $primaryImage = $this->resource->getPrimaryImage();
                    if ($primaryImage) {
                        $thumbnails = $primaryImage->getMetadata()['thumbnails'] ?? [];
                        return $thumbnails['medium'] ?? [
                            'url' => $primaryImage->getUrl(),
                            'width' => $primaryImage->getWidth(),
                            'height' => $primaryImage->getHeight()
                        ];
                    }
                    return null;
                }
            ),

            // URLs
            'url' => url("/listings/{$this->resource->getSlug()}"),
        ];
    }
}