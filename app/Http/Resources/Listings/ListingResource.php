<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

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
        'description' => Listing::DESCRIPTION,
        'price' => Listing::PRICE,
        'category' => Listing::CATEGORY,
        'condition' => Listing::CONDITION,
        'status' => Listing::STATUS,
        'slug' => Listing::SLUG,
        'canDeliverGlobally' => Listing::CAN_DELIVER_GLOBALLY,
        'requiresAppointment' => Listing::REQUIRES_APPOINTMENT,
        'publishedAt' => Listing::PUBLISHED_AT,
        'expiresAt' => Listing::EXPIRES_AT,
        'viewsCount' => Listing::VIEWS_COUNT,
        'favoritesCount' => Listing::FAVORITES_COUNT,
        'createdAt' => Listing::CREATED_AT,
        'updatedAt' => Listing::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        $conditionalData = $this->getConditionalData($request);

        return array_merge($conditionalData, [
            'id' => $this->resource->getId(),
            'formattedPrice' => $this->resource->getFormattedPrice(),
            'categoryDisplayName' => $this->resource->getCategoryDisplayName(),
            'conditionDisplayName' => $this->resource->getConditionDisplayName(),
            'location' => $this->resource->getLocation(),
            'tags' => $this->resource->getTags(),
            'deliveryOptions' => $this->resource->getAttribute('delivery_options'),
            'dimensions' => $this->resource->getAttribute('dimensions'),
            'weight' => $this->resource->getAttribute('weight'),
            'brand' => $this->resource->getAttribute('brand'),
            'model' => $this->resource->getAttribute('model'),
            'year' => $this->resource->getAttribute('year'),
            'color' => $this->resource->getAttribute('color'),
            'material' => $this->resource->getAttribute('material'),

            'isActive' => $this->resource->isActive(),
            'isDraft' => $this->resource->isDraft(),
            'isSold' => $this->resource->isSold(),
            'isExpired' => $this->resource->isExpired(),
            'canBeViewed' => $this->resource->canBeViewed(),
            'canBeEdited' => $this->resource->canBeEdited(),

            'seller' => new SellerProfileResource($this->whenLoaded(Listing::SELLER_PROFILE_RELATION)),
            'images' => ImageResource::collection($this->whenLoaded(Listing::IMAGES_RELATION)),
            'primaryImage' => $this->when(
                $this->resource->relationLoaded(Listing::IMAGES_RELATION),
                fn() => new ImageResource($this->resource->getPrimaryImage())
            ),

            'urls' => [
                'public' => url("/listings/{$this->resource->getSlug()}"),
                'edit' => url("/seller/listings/{$this->resource->getId()}/edit"),
                'api' => url("/api/listings/{$this->resource->getId()}"),
            ],
        ]);
    }
}