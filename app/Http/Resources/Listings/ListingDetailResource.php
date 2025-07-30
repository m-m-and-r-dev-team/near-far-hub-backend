<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use App\Http\Resources\Categories\CategoryResource;
use App\Http\Resources\Images\ImageResource;
use App\Http\Resources\Seller\SellerProfileResource;
use App\Models\Listings\Listing;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingDetailResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var Listing $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'title' => Listing::TITLE,
        'slug' => Listing::SLUG,
        'description' => Listing::DESCRIPTION,
        'price' => Listing::PRICE,
        'originalPrice' => Listing::ORIGINAL_PRICE,
        'condition' => Listing::CONDITION,
        'brand' => Listing::BRAND,
        'model' => Listing::MODEL,
        'year' => Listing::YEAR,
        'locationDisplay' => Listing::LOCATION_DISPLAY,
        'canDeliverGlobally' => Listing::CAN_DELIVER_GLOBALLY,
        'requiresAppointment' => Listing::REQUIRES_APPOINTMENT,
        'status' => Listing::STATUS,
        'viewsCount' => Listing::VIEWS_COUNT,
        'favoritesCount' => Listing::FAVORITES_COUNT,
        'contactCount' => Listing::CONTACT_COUNT,
        'featuredUntil' => Listing::FEATURED_UNTIL,
        'publishedAt' => Listing::PUBLISHED_AT,
        'expiresAt' => Listing::EXPIRES_AT,
        'metaTitle' => Listing::META_TITLE,
        'metaDescription' => Listing::META_DESCRIPTION,
        'createdAt' => Listing::CREATED_AT,
        'updatedAt' => Listing::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        $conditionalData = $this->getConditionalData($request);

        return array_merge($conditionalData, [
            'id' => $this->resource->getId(),
            'isFeatured' => $this->resource->isFeatured(),
            'isActive' => $this->resource->isActive(),
            'canEdit' => $this->resource->canEdit(),
            'canDelete' => $this->resource->canDelete(),
            'hasDiscount' => $this->resource->hasDiscount(),
            'discountPercentage' => $this->resource->getDiscountPercentage(),
            'coordinates' => $this->resource->getCoordinates(),
            'deliveryOptions' => $this->resource->getDeliveryOptions(),
            'categoryAttributes' => $this->resource->getCategoryAttributes(),
            'tags' => $this->resource->getTags(),
            'locationData' => $this->resource->getLocationData(),
            'seller' => new SellerProfileResource($this->whenLoaded(Listing::SELLER_PROFILE_RELATION)),
            'category' => new CategoryResource($this->whenLoaded(Listing::CATEGORY_RELATION)),
            'images' => ImageResource::collection($this->whenLoaded(Listing::IMAGES_RELATION)),
            'imageUrls' => $this->resource->getImageUrls(),
            'primaryImageUrl' => $this->resource->getPrimaryImageUrl(),
            'categoryPath' => $this->when($this->resource->relationLoaded(Listing::CATEGORY_RELATION), function () {
                $category = $this->resource->relatedCategory();
                return $category ? $category->getBreadcrumb() : null;
            }),
            'daysActive' => $this->resource->getPublishedAt()
                ? $this->resource->getPublishedAt()->diffInDays(now())
                : 0,
            'isExpiringSoon' => $this->resource->getExpiresAt() && $this->resource->getExpiresAt()->diffInDays(now()) <= 7,
        ]);
    }
}