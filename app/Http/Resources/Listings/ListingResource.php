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

class ListingResource extends JsonResource
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
        'publishedAt' => Listing::PUBLISHED_AT,
        'expiresAt' => Listing::EXPIRES_AT,
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

            // Relationships
            'seller' => $this->whenLoaded(Listing::SELLER_PROFILE_RELATION, function () {
                return new SellerProfileResource($this->resource->relatedSellerProfile());
            }),
            'category' => $this->whenLoaded(Listing::CATEGORY_RELATION, function () {
                return new CategoryResource($this->resource->relatedCategory());
            }),
            'images' => $this->whenLoaded(Listing::IMAGES_RELATION, function () {
                return ImageResource::collection($this->resource->relatedImages());
            }),
            'primaryImage' => $this->when($this->resource->relationLoaded(Listing::IMAGES_RELATION), function () {
                $primaryImage = $this->resource->getPrimaryImage();
                return $primaryImage ? new ImageResource($primaryImage) : null;
            }),
        ]);
    }
}