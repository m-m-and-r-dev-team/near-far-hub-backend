<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use App\Http\Resources\Images\ImageResource;
use App\Http\Resources\Seller\SellerProfileResource;
use App\Models\Listings\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingSearchResource extends JsonResource
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
            'canDeliverGlobally' => $this->resource->getCanDeliverGlobally(),
            'requiresAppointment' => $this->resource->getRequiresAppointment(),
            'viewsCount' => $this->resource->getViewsCount(),
            'favoritesCount' => $this->resource->getFavoritesCount(),
            'publishedAt' => $this->resource->getPublishedAt()?->toISOString(),
            'createdAt' => $this->resource->getCreatedAt()->toISOString(),

            // Minimal seller info
            'seller' => $this->when(
                $this->resource->relationLoaded(Listing::SELLER_PROFILE_RELATION),
                fn() => [
                    'id' => $this->resource->relatedSellerProfile()->getId(),
                    'businessName' => $this->resource->relatedSellerProfile()->getAttribute('business_name'),
                    'isVerified' => $this->resource->relatedSellerProfile()->getIsVerified(),
                    'user' => [
                        'name' => $this->resource->relatedSellerProfile()->relatedUser()->getName(),
                        'location' => $this->resource->relatedSellerProfile()->relatedUser()->getAttribute('location_display'),
                    ]
                ]
            ),

            // Primary image only for search results
            'primaryImage' => $this->when(
                $this->resource->relationLoaded(Listing::IMAGES_RELATION),
                fn() => new ImageResource($this->resource->getPrimaryImage())
            ),

            // URLs
            'urls' => [
                'public' => url("/listings/{$this->resource->getSlug()}"),
                'api' => url("/api/listings/{$this->resource->getId()}"),
            ],
        ];
    }
}