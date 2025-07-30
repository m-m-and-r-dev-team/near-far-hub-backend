<?php

declare(strict_types=1);

namespace App\Services\Repositories\Listings;

use App\Http\DataTransferObjects\Listings\CreateListingData;
use App\Http\DataTransferObjects\Listings\UpdateListingData;
use App\Http\DataTransferObjects\Listings\SearchListingsData;
use App\Models\Listings\Listing;
use App\Models\Categories\Category;
use App\Services\Locations\HybridLocationService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

readonly class ListingRepository
{
    public function __construct(
        private Listing               $listing,
        private Category              $category,
        private HybridLocationService $locationService
    ) {
    }

    /**
     * Find listing by ID
     */
    public function findById(int $listingId): ?Listing
    {
        return $this->listing->find($listingId);
    }

    /**
     * Find listing by ID with full details
     */
    public function findByIdWithDetails(int $listingId): ?Listing
    {
        return $this->listing
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::CATEGORY_RELATION,
                Listing::IMAGES_RELATION
            ])
            ->find($listingId);
    }

    /**
     * Find listing by slug with details
     */
    public function findBySlugWithDetails(string $slug): ?Listing
    {
        return $this->listing
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.userRelation',
                Listing::CATEGORY_RELATION,
                Listing::IMAGES_RELATION
            ])
            ->where(Listing::SLUG, $slug)
            ->first();
    }

    /**
     * Create new listing
     * @throws Exception
     */
    public function create(int $sellerProfileId, CreateListingData $data): Listing
    {
        // Validate category exists and get its validation rules
        $category = $this->category->find($data->categoryId);
        if (!$category) {
            throw new Exception('Category not found');
        }

        // Validate category-specific attributes
        if (!empty($data->categoryAttributes)) {
            $errors = $category->validateListingData($data->categoryAttributes);
            if (!empty($errors)) {
                throw new Exception('Category attributes validation failed: ' . implode(', ', $errors));
            }
        }

        // Process location data
        $locationData = $this->processLocationData($data->location);

        $payload = [
            Listing::SELLER_PROFILE_ID => $sellerProfileId,
            Listing::CATEGORY_ID => $data->categoryId,
            Listing::TITLE => $data->title,
            Listing::DESCRIPTION => $data->description,
            Listing::PRICE => $data->price,
            Listing::ORIGINAL_PRICE => $data->originalPrice,
            Listing::CONDITION => $data->condition,
            Listing::BRAND => $data->brand,
            Listing::MODEL => $data->model,
            Listing::YEAR => $data->year,
            Listing::LOCATION_DATA => $locationData['data'] ?? null,
            Listing::LOCATION_DISPLAY => $locationData['display'] ?? null,
            Listing::LATITUDE => $locationData['latitude'] ?? null,
            Listing::LONGITUDE => $locationData['longitude'] ?? null,
            Listing::CAN_DELIVER_GLOBALLY => $data->canDeliverGlobally,
            Listing::DELIVERY_OPTIONS => $data->deliveryOptions,
            Listing::REQUIRES_APPOINTMENT => $data->requiresAppointment,
            Listing::CATEGORY_ATTRIBUTES => $data->categoryAttributes,
            Listing::TAGS => $data->tags,
            Listing::STATUS => $data->status,
            Listing::META_TITLE => $data->metaTitle,
            Listing::META_DESCRIPTION => $data->metaDescription,
        ];

        $listing = $this->listing->create($payload);

        return $listing->load([
            Listing::SELLER_PROFILE_RELATION . '.userRelation',
            Listing::CATEGORY_RELATION,
            Listing::IMAGES_RELATION
        ]);
    }

    /**
     * Update listing
     * @throws Exception
     */
    public function update(int $listingId, UpdateListingData $data): Listing
    {
        $listing = $this->listing->findOrFail($listingId);

        // Process location data if provided
        $locationData = null;
        if ($data->location !== null) {
            $locationData = $this->processLocationData($data->location);
        }

        // Build update payload with only provided fields
        $payload = array_filter([
            Listing::TITLE => $data->title,
            Listing::DESCRIPTION => $data->description,
            Listing::PRICE => $data->price,
            Listing::ORIGINAL_PRICE => $data->originalPrice,
            Listing::CONDITION => $data->condition,
            Listing::BRAND => $data->brand,
            Listing::MODEL => $data->model,
            Listing::YEAR => $data->year,
            Listing::LOCATION_DATA => $locationData['data'] ?? null,
            Listing::LOCATION_DISPLAY => $locationData['display'] ?? null,
            Listing::LATITUDE => $locationData['latitude'] ?? null,
            Listing::LONGITUDE => $locationData['longitude'] ?? null,
            Listing::CAN_DELIVER_GLOBALLY => $data->canDeliverGlobally,
            Listing::DELIVERY_OPTIONS => $data->deliveryOptions,
            Listing::REQUIRES_APPOINTMENT => $data->requiresAppointment,
            Listing::CATEGORY_ATTRIBUTES => $data->categoryAttributes,
            Listing::TAGS => $data->tags,
            Listing::STATUS => $data->status,
            Listing::META_TITLE => $data->metaTitle,
            Listing::META_DESCRIPTION => $data->metaDescription,
        ], fn($value) => $value !== null);

        $listing->update($payload);

        return $listing->load([
            Listing::SELLER_PROFILE_RELATION . '.userRelation',
            Listing::CATEGORY_RELATION,
            Listing::IMAGES_RELATION
        ]);
    }

    /**
     * Delete listing
     */
    public function delete(int $listingId): void
    {
        $listing = $this->listing->findOrFail($listingId);

        // Soft delete by setting status to deleted
        $listing->update([Listing::STATUS => Listing::STATUS_DELETED]);
    }

    /**
     * Update listing status
     */
    public function updateStatus(int $listingId, string $status): Listing
    {
        $listing = $this->listing->findOrFail($listingId);
        $listing->update([Listing::STATUS => $status]);

        return $listing->fresh();
    }

    /**
     * Get active listings with filters
     */
    public function getActiveListings(array $filters = [], string $sort = 'recent', int $limit = 20): LengthAwarePaginator
    {
        $query = $this->listing
            ->active()
            ->published()
            ->notExpired()
            ->withSellerAndCategory()
            ->withImages();

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply sorting
        $query = $this->applySorting($query, $sort, $filters);

        return $query->paginate($limit);
    }

    /**
     * Search listings with advanced filters
     */
    public function search(SearchListingsData $data): array
    {
        $query = $this->listing
            ->active()
            ->published()
            ->notExpired()
            ->withSellerAndCategory();

        // Text search
        if ($data->query) {
            $query->searchByText($data->query);
        }

        // Category filter
        if ($data->categoryId) {
            $query->inCategory($data->categoryId);
        }

        // Condition filter
        if ($data->condition) {
            $query->byCondition($data->condition);
        }

        // Price range filter
        if ($data->minPrice || $data->maxPrice) {
            $query->priceRange($data->minPrice, $data->maxPrice);
        }

        // Location-based search
        if ($data->latitude && $data->longitude) {
            $query->withLocation($data->latitude, $data->longitude, $data->radius);
        }

        // Images filter
        if ($data->withImagesOnly) {
            $query->whereHas(Listing::IMAGES_RELATION);
        }

        // Include images
        $query->withImages();

        // Featured first
        if ($data->featuredFirst) {
            $query->orderByRaw('featured_until IS NOT NULL AND featured_until > NOW() DESC');
        }

        // Apply sorting
        $this->applySortingFromSearch($query, $data->sort);

        $results = $query->paginate($data->perPage, ['*'], 'page', $data->page);

        return [
            'listings' => $results,
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'filters_applied' => $this->getAppliedFilters($data),
            'categories_found' => $this->getCategoriesInResults($results->items()),
            'price_range' => $this->getPriceRangeInResults($results->items()),
        ];
    }

    /**
     * Get seller's listings
     */
    public function getSellerListings(int $sellerProfileId, ?string $status = null, int $limit = 20): LengthAwarePaginator
    {
        $query = $this->listing
            ->where(Listing::SELLER_PROFILE_ID, $sellerProfileId)
            ->where(Listing::STATUS, '!=', Listing::STATUS_DELETED)
            ->withImages()
            ->with([Listing::CATEGORY_RELATION])
            ->orderByDesc(Listing::CREATED_AT);

        if ($status) {
            $query->where(Listing::STATUS, $status);
        }

        return $query->paginate($limit);
    }

    /**
     * Get featured listings
     */
    public function getFeaturedListings(int $limit = 10, ?int $categoryId = null): Collection
    {
        $cacheKey = "featured_listings_{$limit}_{$categoryId}";

        return Cache::remember($cacheKey, 300, function () use ($limit, $categoryId) {
            $query = $this->listing
                ->active()
                ->published()
                ->notExpired()
                ->featured()
                ->withSellerAndCategory()
                ->withImages()
                ->orderByDesc(Listing::FEATURED_UNTIL);

            if ($categoryId) {
                $query->inCategory($categoryId);
            }

            return $query->limit($limit)->get();
        });
    }

    /**
     * Get popular listings
     */
    public function getPopularListings(int $limit = 10, int $days = 7, ?int $categoryId = null): Collection
    {
        $cacheKey = "popular_listings_{$limit}_{$days}_{$categoryId}";

        return Cache::remember($cacheKey, 600, function () use ($limit, $days, $categoryId) {
            $query = $this->listing
                ->active()
                ->published()
                ->notExpired()
                ->popular($days)
                ->withSellerAndCategory()
                ->withImages();

            if ($categoryId) {
                $query->inCategory($categoryId);
            }

            return $query->limit($limit)->get();
        });
    }

    /**
     * Get recent listings
     */
    public function getRecentListings(int $limit = 10, ?int $categoryId = null): Collection
    {
        $cacheKey = "recent_listings_{$limit}_{$categoryId}";

        return Cache::remember($cacheKey, 300, function () use ($limit, $categoryId) {
            $query = $this->listing
                ->active()
                ->published()
                ->notExpired()
                ->recent()
                ->withSellerAndCategory()
                ->withImages();

            if ($categoryId) {
                $query->inCategory($categoryId);
            }

            return $query->limit($limit)->get();
        });
    }

    /**
     * Get similar listings
     */
    public function getSimilarListings(int $listingId, int $limit = 6): Collection
    {
        $listing = $this->listing->find($listingId);
        if (!$listing) {
            return new Collection();
        }

        $query = $this->listing
            ->active()
            ->published()
            ->notExpired()
            ->where(Listing::ID, '!=', $listingId)
            ->withSellerAndCategory()
            ->withImages();

        // Similar by category
        $query->where(function ($q) use ($listing) {
            $q->where(Listing::CATEGORY_ID, $listing->getCategoryId());

            // Similar by brand
            if ($listing->getBrand()) {
                $q->orWhere(Listing::BRAND, $listing->getBrand());
            }

            // Similar price range (±30%)
            $priceMin = $listing->getPrice() * 0.7;
            $priceMax = $listing->getPrice() * 1.3;
            $q->orWhereBetween(Listing::PRICE, [$priceMin, $priceMax]);
        });

        // Prioritize same category
        $query->orderByRaw("CASE WHEN category_id = ? THEN 0 ELSE 1 END", [$listing->getCategoryId()]);
        $query->orderByDesc(Listing::VIEWS_COUNT);

        return $query->limit($limit)->get();
    }

    /**
     * Get seller statistics
     */
    public function getSellerStats(int $sellerProfileId): array
    {
        $cacheKey = "seller_stats_{$sellerProfileId}";

        return Cache::remember($cacheKey, 900, function () use ($sellerProfileId) {
            $baseQuery = $this->listing->where(Listing::SELLER_PROFILE_ID, $sellerProfileId);

            return [
                'total_listings' => $baseQuery->clone()->count(),
                'active_listings' => $baseQuery->clone()->where(Listing::STATUS, Listing::STATUS_ACTIVE)->count(),
                'draft_listings' => $baseQuery->clone()->where(Listing::STATUS, Listing::STATUS_DRAFT)->count(),
                'sold_listings' => $baseQuery->clone()->where(Listing::STATUS, Listing::STATUS_SOLD)->count(),
                'expired_listings' => $baseQuery->clone()->where(Listing::STATUS, Listing::STATUS_EXPIRED)->count(),
                'total_views' => $baseQuery->clone()->sum(Listing::VIEWS_COUNT),
                'total_favorites' => $baseQuery->clone()->sum(Listing::FAVORITES_COUNT),
                'total_contacts' => $baseQuery->clone()->sum(Listing::CONTACT_COUNT),
                'featured_listings' => $baseQuery->clone()
                    ->whereNotNull(Listing::FEATURED_UNTIL)
                    ->where(Listing::FEATURED_UNTIL, '>', now())
                    ->count(),
                'avg_price' => $baseQuery->clone()
                    ->where(Listing::STATUS, Listing::STATUS_ACTIVE)
                    ->avg(Listing::PRICE),
                'this_month_listings' => $baseQuery->clone()
                    ->where(Listing::CREATED_AT, '>=', now()->startOfMonth())
                    ->count(),
                'this_week_views' => $baseQuery->clone()
                    ->where(Listing::UPDATED_AT, '>=', now()->startOfWeek())
                    ->sum(Listing::VIEWS_COUNT),
            ];
        });
    }

    // Private helper methods

    /**
     * Process location data
     */
    private function processLocationData(?array $locationData): array
    {
        if (!$locationData) {
            return [];
        }

        try {
            $enrichedLocation = $this->locationService->validateAndEnrichLocation($locationData);

            return [
                'data' => $enrichedLocation,
                'display' => $enrichedLocation['formatted_address'] ?? $enrichedLocation['description'] ?? null,
                'latitude' => $enrichedLocation['latitude'] ?? null,
                'longitude' => $enrichedLocation['longitude'] ?? null,
            ];
        } catch (Exception $e) {
            // If location processing fails, return original data
            return [
                'data' => $locationData,
                'display' => $locationData['description'] ?? null,
                'latitude' => $locationData['latitude'] ?? null,
                'longitude' => $locationData['longitude'] ?? null,
            ];
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['category_id'])) {
            $query->inCategory($filters['category_id']);
        }

        if (isset($filters['condition'])) {
            $query->byCondition($filters['condition']);
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->priceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null);
        }

        if (isset($filters['location']) && isset($filters['radius'])) {
            // This would need geocoding logic to convert location string to lat/lng
            // For now, we'll skip this complex implementation
        }

        if (isset($filters['with_images']) && $filters['with_images']) {
            $query->whereHas(Listing::IMAGES_RELATION);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    private function applySorting(Builder $query, string $sort, array $filters = []): Builder
    {
        if (isset($filters['featured_first']) && $filters['featured_first']) {
            $query->orderByRaw('featured_until IS NOT NULL AND featured_until > NOW() DESC');
        }

        return match ($sort) {
            'price_low' => $query->orderBy(Listing::PRICE, 'asc'),
            'price_high' => $query->orderBy(Listing::PRICE, 'desc'),
            'popular' => $query->orderByDesc(Listing::VIEWS_COUNT)->orderByDesc(Listing::FAVORITES_COUNT),
            'distance' => $query, // Distance sorting would be applied in location filter
            default => $query->orderByDesc(Listing::PUBLISHED_AT),
        };
    }

    /**
     * Apply sorting from search data
     */
    private function applySortingFromSearch(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_low' => $query->orderBy(Listing::PRICE, 'asc'),
            'price_high' => $query->orderBy(Listing::PRICE, 'desc'),
            'popular' => $query->orderByDesc(Listing::VIEWS_COUNT)->orderByDesc(Listing::FAVORITES_COUNT),
            'distance' => $query->orderBy('distance'), // Assumes distance was calculated
            'relevant' => $query->orderByDesc(DB::raw('MATCH(title, description) AGAINST (? IN NATURAL LANGUAGE MODE)', ['search_term'])),
            default => $query->orderByDesc(Listing::PUBLISHED_AT),
        };
    }

    /**
     * Get applied filters summary
     */
    private function getAppliedFilters(SearchListingsData $data): array
    {
        $filters = [];

        if ($data->query) $filters['search'] = $data->query;
        if ($data->categoryId) $filters['category_id'] = $data->categoryId;
        if ($data->condition) $filters['condition'] = $data->condition;
        if ($data->minPrice) $filters['min_price'] = $data->minPrice;
        if ($data->maxPrice) $filters['max_price'] = $data->maxPrice;
        if ($data->location) $filters['location'] = $data->location;
        if ($data->withImagesOnly) $filters['with_images'] = true;

        return $filters;
    }

    /**
     * Get categories found in results
     */
    private function getCategoriesInResults(array $listings): array
    {
        $categories = [];
        foreach ($listings as $listing) {
            $category = $listing->relatedCategory();
            if ($category && !isset($categories[$category->getId()])) {
                $categories[$category->getId()] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'count' => 1
                ];
            } elseif (isset($categories[$category->getId()])) {
                $categories[$category->getId()]['count']++;
            }
        }

        return array_values($categories);
    }

    /**
     * Get price range in results
     */
    private function getPriceRangeInResults(array $listings): ?array
    {
        if (empty($listings)) {
            return null;
        }

        $prices = array_map(fn($listing) => $listing->getPrice(), $listings);

        return [
            'min' => min($prices),
            'max' => max($prices),
            'avg' => round(array_sum($prices) / count($prices), 2)
        ];
    }
}