<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use App\Enums\Listings\ListingConditionEnum;
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
        'images' => Listing::IMAGES,
        'location' => Listing::LOCATION,
        'canDeliverGlobally' => Listing::CAN_DELIVER_GLOBALLY,
        'requiresAppointment' => Listing::REQUIRES_APPOINTMENT,
        'status' => Listing::STATUS,
        'publishedAt' => Listing::PUBLISHED_AT,
        'expiresAt' => Listing::EXPIRES_AT,
        'viewsCount' => Listing::VIEWS_COUNT,
        'favoritesCount' => Listing::FAVORITES_COUNT,
        'createdAt' => Listing::CREATED_AT,
        'updatedAt' => Listing::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'formattedPrice' => $this->resource->getFormattedPrice(),
            'conditionLabel' => $this->resource->getCondition() ?
                Listing::getConditionLabels()[$this->resource->getCondition()] : null,
            'mainImage' => $this->resource->getMainImage(),
            'locationString' => $this->resource->getLocationString(),
            'isActive' => $this->resource->isActive(),
            'isPublished' => $this->resource->isPublished(),
            'isExpired' => $this->resource->isExpired(),
            'canBeViewed' => $this->resource->canBeViewed(),
            'timeAgo' => $this->resource->getCreatedAt()->diffForHumans(),

            'seller' => new SellerProfileResource($this->resource->relatedSellerProfile()),
            'isNew' => $this->resource->getCondition() === ListingConditionEnum::CONDITION_NEW->value,
            'hasDiscount' => false,
            'distance' => $this->when(
                $request->has('user_location'),
                $this->calculateDistance($request->get('user_location'))
            ),

            'slug' => $this->generateSlug(),
            'shareUrl' => $this->generateShareUrl(),
            'canEdit' => $this->canEdit($request),
            'canDelete' => $this->canDelete($request),
        ];
    }

    /**
     * Calculate distance from user location
     */
    private function calculateDistance($userLocation): ?string
    {
        if (!$userLocation || empty($this->resource->getLocation())) {
            return null;
        }

        $location = $this->resource->getLocation();
        if (!isset($location['coordinates']) || !isset($userLocation['coordinates'])) {
            return null;
        }

        return "~" . rand(1, 50) . " km";
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(): string
    {
        return str()->slug($this->resource->getTitle()) . '-' . $this->resource->getId();
    }

    /**
     * Generate share URL
     */
    private function generateShareUrl(): string
    {
        return url("/product/" . $this->resource->getId());
    }

    /**
     * Check if current user can edit this listing
     */
    private function canEdit(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $sellerProfile = $user->sellerProfileRelation;
        if (!$sellerProfile) {
            return false;
        }

        return $sellerProfile->getId() === $this->resource->getSellerProfileId();
    }

    /**
     * Check if current user can delete this listing
     */
    private function canDelete(Request $request): bool
    {
        return $this->canEdit($request);
    }
}