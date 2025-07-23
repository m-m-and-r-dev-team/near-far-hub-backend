<?php

declare(strict_types=1);

namespace App\Services\Repositories\Listings;

use App\Enums\Listings\ListingStatusEnum;
use App\Http\DataTransferObjects\Listings\CreateListingData;
use App\Http\DataTransferObjects\Listings\ListingSearchData;
use App\Http\DataTransferObjects\Listings\UpdateListingData;
use App\Models\Listings\Listing;
use App\Services\Repositories\Seller\SellerProfileRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListingRepository
{
    public function __construct(
        private readonly Listing $listing,
        private readonly SellerProfileRepository $sellerProfileRepository
    ) {
    }

    /**
     * Create a new listing
     * @throws Exception
     */
    public function create(int $userId, CreateListingData $data): Listing
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        if (!$sellerProfile->getIsActive()) {
            throw new Exception('Seller account is not active');
        }

        $slug = $this->generateUniqueSlug($data->title);

        $payload = [
            Listing::SELLER_PROFILE_ID => $sellerProfile->getId(),
            Listing::TITLE => $data->title,
            Listing::DESCRIPTION => $data->description,
            Listing::PRICE => $data->price,
            Listing::CATEGORY => $data->category,
            Listing::CONDITION => $data->condition,
            Listing::LOCATION => $data->location,
            Listing::CAN_DELIVER_GLOBALLY => $data->canDeliverGlobally,
            Listing::REQUIRES_APPOINTMENT => $data->requiresAppointment,
            Listing::STATUS => ListingStatusEnum::DRAFT,
            Listing::SLUG => $slug,
            Listing::TAGS => $data->tags,
            Listing::DELIVERY_OPTIONS => $data->deliveryOptions,
            Listing::DIMENSIONS => $data->dimensions,
            Listing::WEIGHT => $data->weight,
            Listing::BRAND => $data->brand,
            Listing::MODEL => $data->model,
            Listing::YEAR => $data->year,
            Listing::COLOR => $data->color,
            Listing::MATERIAL => $data->material,
            Listing::EXPIRES_AT => $data->expiresAt ? Carbon::parse($data->expiresAt) : null,
        ];

        $listing = $this->listing->create($payload);

        return $listing->load([
            Listing::SELLER_PROFILE_RELATION . '.userRelation',
            Listing::IMAGES_RELATION
        ]);
    }

    /**
     * Update an existing listing
     * @throws Exception
     */
    public function update(int $listingId, int $userId, UpdateListingData $data): Listing
    {
        $listing = $this->findByIdAndUserId($listingId, $userId);

        if (!$listing->canBeEdited()) {
            throw new Exception('Listing cannot be edited in its current status');
        }

        $updateData = array_filter([
            Listing::TITLE => $data->title,
            Listing::DESCRIPTION => $data->description,
            Listing::PRICE => $data->price,
            Listing::CATEGORY => $data->category,
            Listing::CONDITION => $data->condition,
            Listing::LOCATION => $data->location,
            Listing::CAN_DELIVER_GLOBALLY => $data->canDeliverGlobally,
            Listing::REQUIRES_APPOINTMENT => $data->requiresAppointment,
            Listing::TAGS => $data->tags,
            Listing::DELIVERY_OPTIONS => $data->deliveryOptions,
            Listing::DIMENSIONS => $data->dimensions,
            Listing::WEIGHT => $data->weight,
            Listing::BRAND => $data->brand,
            Listing::MODEL => $data->model,
            Listing::YEAR => $data->year,
            Listing::COLOR => $data->color,
            Listing::MATERIAL => $data->material,
            Listing::EXPIRES_AT => $data->expiresAt ? Carbon::parse($data->expiresAt) : null,
        ], fn($value) => $value !== null);

        // Update slug if title changed
        if (isset($updateData[Listing::TITLE]) && $updateData[Listing::TITLE] !== $listing->getTitle()) {
            $updateData[Listing::SLUG] = $this->generateUniqueSlug($updateData[Listing::TITLE], $listing->getId());
        }

        $listing->update($updateData);

        return $listing->fresh([
            Listing::SELLER_PROFILE_RELATION . '.userRelation',
            Listing::IMAGES_RELATION
        ]);
    }

    /**
     * Publish a listing
     * @throws Exception
     */
    public function publish(int $listingId, int $userId, ?Carbon $publishedAt = null, ?Carbon $expiresAt = null): Listing
    {
        $listing = $this->findByIdAndUserId($listingId, $userId);

        if (!$listing->isDraft() && !$listing->getStatus() === ListingStatusEnum::PENDING_APPROVAL) {
            throw new Exception('Only draft or pending approval listings can be published');
        }

        // Validate listing has required content
        if (!$this->isListingReadyForPublication($listing)) {
            throw new Exception('Listing is not ready for publication. Please ensure it has a title, description, price, category, and at least one image.');
        }

        $listing->update([
            Listing::STATUS => ListingStatusEnum::ACTIVE,
            Listing::PUBLISHED_AT => $publishedAt ?? now(),
            Listing::EXPIRES_AT => $expiresAt,
        ]);

        return $listing->fresh([
            Listing::SELLER_PROFILE_RELATION . '.userRelation',
            Listing::IMAGES_RELATION
        ]);
    }

    /**
     * Mark listing as sold
     * @throws Exception
     */
    public function markAsSold(int $listingId, int $userId): Listing
    {
        $listing = $this->findByIdAndUserId($listingId, $userId);

        if (!$listing->isActive()) {
            throw new Exception('Only active listings can be marked as sold');
        }

        $listing->update([Listing::STATUS => ListingStatusEnum::SOLD]);

        return $listing->fresh([
            Listing::SELLER_PROFILE_RELATION . '.userRelation',
            Listing::IMAGES_RELATION
        ]);
    }

    /**
     * Delete a listing
     * @throws Exception
     */
    public function delete(int $listingId, int $userId): void
    {
        $listing = $this->findByIdAndUserId($listingId, $userId);

        if ($listing->isActive() || $listing->isSold()) {
            throw new Exception('Active or sold listings cannot be deleted. Please mark as draft first.');
        }

        // Soft delete would be better for data integrity
        $listing->update([Listing::STATUS => ListingStatusEnum::EXPIRED]);
    }

    /**
     * Search listings with filters
     */
    public function search(ListingSearchData $searchData): LengthAwarePaginator
    {
        $query = $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION => function($q) {
                    $q->where('is_primary', true)->orWhere('sort_order', 0);
                }
            ])
            ->viewable();

        // Apply search filters
        $this->applySearchFilters($query, $searchData);

        // Apply sorting
        $this->applySorting($query, $searchData);

        return $query->paginate(
            $searchData->perPage,
            ['*'],
            'page',
            $searchData->page
        );
    }

    /**
     * Get listings by seller
     */
    public function getBySellerUserId(int $userId, ?ListingStatusEnum $status = null, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $query = $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION
            ])
            ->bySellerProfile($sellerProfile->getId())
            ->orderByDate();

        if ($status) {
            $query->where(Listing::STATUS, $status);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get listing by ID (public view)
     */
    public function findByIdForPublicView(int $listingId): Listing
    {
        $listing = $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION
            ])
            ->viewable()
            ->findOrFail($listingId);

        // Increment view count
        $listing->incrementViewsCount();

        return $listing;
    }

    /**
     * Get listing by slug (public view)
     */
    public function findBySlugForPublicView(string $slug): Listing
    {
        $listing = $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION
            ])
            ->viewable()
            ->where(Listing::SLUG, $slug)
            ->firstOrFail();

        // Increment view count
        $listing->incrementViewsCount();

        return $listing;
    }

    /**
     * Get listing by ID and user ID (for editing)
     */
    public function findByIdAndUserId(int $listingId, int $userId): Listing
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        return $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION
            ])
            ->bySellerProfile($sellerProfile->getId())
            ->findOrFail($listingId);
    }

    /**
     * Get similar listings
     */
    public function getSimilarListings(int $listingId, int $limit = 5): Collection
    {
        $listing = $this->listing->findOrFail($listingId);

        return $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION => function($q) {
                    $q->where('is_primary', true)->orWhere('sort_order', 0);
                }
            ])
            ->viewable()
            ->where('id', '!=', $listingId)
            ->where(function($query) use ($listing) {
                $query->byCategory($listing->getCategory())
                    ->orWhere(Listing::PRICE, '>=', $listing->getPrice() * 0.7)
                    ->where(Listing::PRICE, '<=', $listing->getPrice() * 1.3);
            })
            ->orderByViews()
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular listings
     */
    public function getPopularListings(int $limit = 10): Collection
    {
        return $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION => function($q) {
                    $q->where('is_primary', true)->orWhere('sort_order', 0);
                }
            ])
            ->viewable()
            ->orderByViews()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent listings
     */
    public function getRecentListings(int $limit = 10): Collection
    {
        return $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::IMAGES_RELATION => function($q) {
                    $q->where('is_primary', true)->orWhere('sort_order', 0);
                }
            ])
            ->viewable()
            ->orderByDate()
            ->limit($limit)
            ->get();
    }

    /**
     * Get seller dashboard stats
     */
    public function getSellerDashboardStats(int $userId): array
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $stats = DB::table('listings')
            ->where('seller_profile_id', $sellerProfile->getId())
            ->selectRaw('
                COUNT(*) as total_listings,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_listings,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft_listings,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sold_listings,
                SUM(views_count) as total_views,
                SUM(favorites_count) as total_favorites,
                AVG(views_count) as avg_views_per_listing
            ', [
                ListingStatusEnum::ACTIVE->value,
                ListingStatusEnum::DRAFT->value,
                ListingStatusEnum::SOLD->value
            ])
            ->first();

        return [
            'total_listings' => (int) $stats->total_listings,
            'active_listings' => (int) $stats->active_listings,
            'draft_listings' => (int) $stats->draft_listings,
            'sold_listings' => (int) $stats->sold_listings,
            'total_views' => (int) $stats->total_views,
            'total_favorites' => (int) $stats->total_favorites,
            'avg_views_per_listing' => round((float) $stats->avg_views_per_listing, 2),
        ];
    }

    // Private helper methods

    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->listing->where(Listing::SLUG, $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function isListingReadyForPublication(Listing $listing): bool
    {
        return !empty($listing->getTitle()) &&
            !empty($listing->getDescription()) &&
            $listing->getPrice() > 0 &&
            !empty($listing->getCategory()) &&
            $listing->relatedImages()->isNotEmpty();
    }

    private function applySearchFilters(Builder $query, ListingSearchData $searchData): void
    {
        if ($searchData->query) {
            $query->search($searchData->query);
        }

        if ($searchData->category) {
            $query->byCategory(\App\Enums\Listings\ListingCategoryEnum::from($searchData->category));
        }

        if ($searchData->condition) {
            $query->byCondition(\App\Enums\Listings\ListingConditionEnum::from($searchData->condition));
        }

        if ($searchData->minPrice !== null || $searchData->maxPrice !== null) {
            $query->priceRange($searchData->minPrice, $searchData->maxPrice);
        }

        if ($searchData->canDeliverGlobally !== null) {
            $query->canDeliverGlobally($searchData->canDeliverGlobally);
        }

        if ($searchData->requiresAppointment !== null) {
            $query->requiresAppointment($searchData->requiresAppointment);
        }

        // Location-based filtering would require more complex implementation
        // if ($searchData->location && $searchData->radius) {
        //     $this->applyLocationFilter($query, $searchData->location, $searchData->radius);
        // }
    }

    private function applySorting(Builder $query, ListingSearchData $searchData): void
    {
        switch ($searchData->sortBy) {
            case 'price':
                $query->orderByPrice($searchData->sortDirection);
                break;
            case 'date':
                $query->orderByDate($searchData->sortDirection);
                break;
            case 'views':
                $query->orderByViews($searchData->sortDirection);
                break;
            case 'favorites':
                $query->orderBy(Listing::FAVORITES_COUNT, $searchData->sortDirection);
                break;
            case 'relevance':
            default:
                $query->orderByRelevance($searchData->query);
                break;
        }
    }
}