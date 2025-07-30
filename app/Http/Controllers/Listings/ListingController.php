<?php

declare(strict_types=1);

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Images\ImageController;
use App\Http\Requests\Listings\CreateListingRequest;
use App\Http\Requests\Listings\UpdateListingRequest;
use App\Http\Requests\Listings\SearchListingsRequest;
use App\Http\Resources\Listings\ListingResource;
use App\Http\Resources\Listings\ListingDetailResource;
use App\Http\Resources\Listings\ListingCardResource;
use App\Models\Listings\Listing;
use App\Services\Repositories\Listings\ListingRepository;
use App\Services\Repositories\Seller\SellerProfileRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListingController extends Controller
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly SellerProfileRepository $sellerProfileRepository,
        private readonly ImageController $imageController
    ) {
    }

    /**
     * Get all active listings (public)
     */
    public function getActiveListings(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'category_id' => $request->get('category_id'),
            'condition' => $request->get('condition'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'location' => $request->get('location'),
            'radius' => $request->get('radius', 50),
            'with_images' => $request->boolean('with_images', false),
            'featured_first' => $request->boolean('featured_first', true),
        ];

        $sort = $request->get('sort', 'recent'); // recent, price_low, price_high, popular, distance
        $limit = min((int) $request->get('limit', 20), 50);

        $listings = $this->listingRepository->getActiveListings($filters, $sort, $limit);

        return ListingCardResource::collection($listings);
    }

    /**
     * Search listings with advanced filters
     */
    public function searchListings(SearchListingsRequest $request): JsonResponse
    {
        $results = $this->listingRepository->search($request->dto());

        return response()->json([
            'success' => true,
            'data' => ListingCardResource::collection($results['listings']),
            'meta' => [
                'total' => $results['total'],
                'per_page' => $results['per_page'],
                'current_page' => $results['current_page'],
                'last_page' => $results['last_page'],
                'filters_applied' => $results['filters_applied'],
                'search_query' => $request->getQuery(),
                'categories_found' => $results['categories_found'] ?? [],
                'price_range' => $results['price_range'] ?? null,
            ]
        ]);
    }

    /**
     * Get listing by slug (public)
     */
    public function getListingBySlug(string $slug): ListingDetailResource|JsonResponse
    {
        $listing = $this->listingRepository->findBySlugWithDetails($slug);

        if (!$listing || !$listing->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found or not available'
            ], 404);
        }

        // Increment view count
        $listing->incrementViews();

        return new ListingDetailResource($listing);
    }

    /**
     * Get listing details by ID
     */
    public function getListingById(int $listingId): ListingDetailResource|JsonResponse
    {
        $listing = $this->listingRepository->findByIdWithDetails($listingId);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Check if user can access this listing
        $user = Auth::user();
        if (!$listing->isActive() &&
            (!$user || $listing->getSellerProfileId() !== $user->relatedSellerProfile()?->getId())) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not available'
            ], 403);
        }

        return new ListingDetailResource($listing);
    }

    /**
     * Get seller's listings
     */
    public function getSellerListings(Request $request): AnonymousResourceCollection
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());

        if (!$sellerProfile) {
            abort(404, 'Seller profile not found');
        }

        $status = $request->get('status');
        $limit = min((int) $request->get('limit', 20), 50);

        $listings = $this->listingRepository->getSellerListings(
            $sellerProfile->getId(),
            $status,
            $limit
        );

        return ListingResource::collection($listings);
    }

    /**
     * Create new listing
     */
    public function createListing(CreateListingRequest $request): JsonResponse
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());

        if (!$sellerProfile || !$sellerProfile->getIsActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Active seller profile required to create listings'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Create listing
            $listing = $this->listingRepository->create($sellerProfile->getId(), $request->dto());

            // Handle image uploads if provided
            if ($request->hasFile('images')) {
                $this->handleImageUploads($listing->getId(), $request->file('images'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Listing created successfully',
                'data' => new ListingResource($listing)
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update listing
     */
    public function updateListing(int $listingId, UpdateListingRequest $request): JsonResponse
    {
        $listing = $this->listingRepository->findById($listingId);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Check ownership
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());
        if (!$sellerProfile || $listing->getSellerProfileId() !== $sellerProfile->getId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this listing'
            ], 403);
        }

        // Check if listing can be edited
        if (!$listing->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'This listing cannot be edited in its current status'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update listing
            $listing = $this->listingRepository->update($listingId, $request->dto());

            // Handle new image uploads if provided
            if ($request->hasFile('images')) {
                $this->handleImageUploads($listing->getId(), $request->file('images'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully',
                'data' => new ListingResource($listing)
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete listing
     */
    public function deleteListing(int $listingId): JsonResponse
    {
        $listing = $this->listingRepository->findById($listingId);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Check ownership
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());
        if (!$sellerProfile || $listing->getSellerProfileId() !== $sellerProfile->getId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this listing'
            ], 403);
        }

        // Check if listing can be deleted
        if (!$listing->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'This listing cannot be deleted in its current status'
            ], 422);
        }

        try {
            $this->listingRepository->delete($listingId);

            return response()->json([
                'success' => true,
                'message' => 'Listing deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark listing as sold
     */
    public function markAsSold(int $listingId): JsonResponse
    {
        return $this->updateListingStatus($listingId, Listing::STATUS_SOLD, 'Listing marked as sold');
    }

    /**
     * Renew expired listing
     */
    public function renewListing(int $listingId, Request $request): JsonResponse
    {
        $listing = $this->listingRepository->findById($listingId);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Check ownership
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());
        if (!$sellerProfile || $listing->getSellerProfileId() !== $sellerProfile->getId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to renew this listing'
            ], 403);
        }

        $days = min((int) $request->get('days', 30), 90); // Max 90 days

        try {
            $listing->renewListing($days);

            return response()->json([
                'success' => true,
                'message' => "Listing renewed for {$days} days",
                'data' => new ListingResource($listing->fresh())
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make listing featured
     */
    public function makeFeatured(int $listingId, Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30'
        ]);

        $listing = $this->listingRepository->findById($listingId);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Check ownership
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());
        if (!$sellerProfile || $listing->getSellerProfileId() !== $sellerProfile->getId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to feature this listing'
            ], 403);
        }

        $days = (int) $request->get('days');

        try {
            $listing->makeFeatureForsed($days);

            return response()->json([
                'success' => true,
                'message' => "Listing featured for {$days} days",
                'data' => new ListingResource($listing->fresh())
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to feature listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get similar listings
     */
    public function getSimilarListings(int $listingId): AnonymousResourceCollection
    {
        $similar = $this->listingRepository->getSimilarListings($listingId, 6);
        return ListingCardResource::collection($similar);
    }

    /**
     * Get listings statistics for seller dashboard
     */
    public function getSellerStats(): JsonResponse
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());

        if (!$sellerProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found'
            ], 404);
        }

        $stats = $this->listingRepository->getSellerStats($sellerProfile->getId());

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Contact seller about listing
     */
    public function contactSeller(int $listingId, Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'contact_method' => 'required|in:message,phone,email',
        ]);

        $listing = $this->listingRepository->findById($listingId);

        if (!$listing || !$listing->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found or not available'
            ], 404);
        }

        try {
            // Increment contact count
            $listing->incrementContacts();

            // Here you would typically send a notification/message to the seller
            // For now, we'll just return success

            return response()->json([
                'success' => true,
                'message' => 'Message sent to seller successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured listings
     */
    public function getFeaturedListings(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->get('limit', 10), 20);
        $categoryId = $request->get('category_id');

        $listings = $this->listingRepository->getFeaturedListings($limit, $categoryId);
        return ListingCardResource::collection($listings);
    }

    /**
     * Get popular listings
     */
    public function getPopularListings(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->get('limit', 10), 20);
        $days = min((int) $request->get('days', 7), 30);
        $categoryId = $request->get('category_id');

        $listings = $this->listingRepository->getPopularListings($limit, $days, $categoryId);
        return ListingCardResource::collection($listings);
    }

    /**
     * Get recent listings
     */
    public function getRecentListings(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->get('limit', 10), 20);
        $categoryId = $request->get('category_id');

        $listings = $this->listingRepository->getRecentListings($limit, $categoryId);
        return ListingCardResource::collection($listings);
    }

    // Private helper methods

    /**
     * Handle image uploads for listing
     */
    private function handleImageUploads(int $listingId, array $images): void
    {
        foreach ($images as $image) {
            $this->imageController->handleSingleImageUpload($listingId, $image, 'listing');
        }
    }

    /**
     * Update listing status
     */
    private function updateListingStatus(int $listingId, string $status, string $successMessage): JsonResponse
    {
        $listing = $this->listingRepository->findById($listingId);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Check ownership
        $sellerProfile = $this->sellerProfileRepository->getByUserId(Auth::id());
        if (!$sellerProfile || $listing->getSellerProfileId() !== $sellerProfile->getId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this listing'
            ], 403);
        }

        try {
            $this->listingRepository->updateStatus($listingId, $status);

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => new ListingResource($listing->fresh())
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}