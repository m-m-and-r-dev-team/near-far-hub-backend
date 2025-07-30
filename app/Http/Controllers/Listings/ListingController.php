<?php

declare(strict_types=1);

namespace App\Http\Controllers\Listings;

use App\Enums\Images\ImageTypeEnum;
use App\Enums\Listings\ListingStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Listings\CreateListingRequest;
use App\Http\Requests\Listings\SearchListingsRequest;
use App\Http\Requests\Listings\UpdateListingRequest;
use App\Http\Requests\Listings\UploadImageRequest;
use App\Http\Resources\Images\ImageResource;
use App\Http\Resources\Listings\ListingCardResource;
use App\Http\Resources\Listings\ListingResource;
use App\Http\Resources\Listings\ListingSearchResource;
use App\Services\Images\ImageUploadService;
use App\Services\Repositories\Listings\ListingRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class ListingController extends Controller
{
    public function __construct(
        private readonly ListingRepository  $listingRepository,
        private readonly ImageUploadService $imageUploadService
    )
    {
    }

    /**
     * Get public listings with search and filters
     */
    public function index(SearchListingsRequest $request): AnonymousResourceCollection
    {
        $searchData = $request->dto();
        $listings = $this->listingRepository->search($searchData);

        return ListingSearchResource::collection($listings);
    }

    /**
     * Get seller's own listings
     */
    public function getSellerListings(Request $request): AnonymousResourceCollection
    {
        $status = $request->get('status') ? ListingStatusEnum::from($request->get('status')) : null;
        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 20);

        $listings = $this->listingRepository->getBySellerUserId(
            auth()->id(),
            $status,
            $page,
            $perPage
        );

        return ListingResource::collection($listings);
    }

    /**
     * Create a new listing
     * @throws UnknownProperties
     * @throws Exception
     */
    public function store(CreateListingRequest $request): ListingResource
    {
        $listing = $this->listingRepository->create(
            auth()->id(),
            $request->dto()
        );

        return new ListingResource($listing);
    }

    /**
     * Get a specific listing (public view)
     */
    public function show(int $listingId): ListingResource
    {
        $listing = $this->listingRepository->findByIdForPublicView($listingId);
        return new ListingResource($listing);
    }

    /**
     * Get a listing by slug (public view)
     */
    public function showBySlug(string $slug): ListingResource
    {
        $listing = $this->listingRepository->findBySlugForPublicView($slug);
        return new ListingResource($listing);
    }

    /**
     * Get listing for editing (seller only)
     */
    public function edit(int $listingId): ListingResource
    {
        $listing = $this->listingRepository->findByIdAndUserId($listingId, auth()->id());
        return new ListingResource($listing);
    }

    /**
     * Update a listing
     * @throws UnknownProperties
     * @throws Exception
     */
    public function update(int $listingId, UpdateListingRequest $request): ListingResource
    {
        $listing = $this->listingRepository->update(
            $listingId,
            auth()->id(),
            $request->dto()
        );

        return new ListingResource($listing);
    }

    /**
     * Publish a listing
     * @throws Exception
     */
    public function publish(int $listingId): JsonResponse
    {
        $listing = $this->listingRepository->publish($listingId, auth()->id());

        return response()->json([
            'message' => 'Listing published successfully',
            'listing' => new ListingResource($listing)
        ]);
    }

    /**
     * Mark listing as sold
     * @throws Exception
     */
    public function markAsSold(int $listingId): JsonResponse
    {
        $listing = $this->listingRepository->markAsSold($listingId, auth()->id());

        return response()->json([
            'message' => 'Listing marked as sold',
            'listing' => new ListingResource($listing)
        ]);
    }

    /**
     * Delete a listing
     * @throws Exception
     */
    public function destroy(int $listingId): JsonResponse
    {
        $this->listingRepository->delete($listingId, auth()->id());

        return response()->json([
            'message' => 'Listing deleted successfully'
        ]);
    }

    /**
     * Upload images for a listing
     * @throws Exception
     */
    public function uploadImages(int $listingId, UploadImageRequest $request): JsonResponse
    {
        $listing = $this->listingRepository->findByIdAndUserId($listingId, auth()->id());

        $files = $request->file('images');
        if (!$files || empty($files)) {
            return response()->json([
                'message' => 'No images provided'
            ], 400);
        }

        $options = [
            'alt_text' => $request->input('alt_text'),
            'quality' => (int) $request->input('quality', 85),
            'max_width' => (int) $request->input('max_width', 1200),
            'max_height' => (int) $request->input('max_height', 900),
            'generate_thumbnails' => true,
            'auto_set_primary' => !$listing->hasPrimaryImage(),
        ];

        $uploadedImages = collect();
        $errors = [];
        $successCount = 0;

        foreach ($files as $index => $file) {
            try {
                $uploadedImage = $this->imageUploadService->uploadSingleForModel(
                    $file,
                    $listing,
                    ImageTypeEnum::LISTING,
                    array_merge($options, [
                        'sort_order' => $listing->getImageCount() + $index,
                        'is_primary' => $options['auto_set_primary'] && $successCount === 0,
                    ])
                );

                $uploadedImages->push($uploadedImage);
                $successCount++;
            } catch (Exception $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        $responseData = [
            'uploaded_count' => $successCount,
            'total_attempted' => count($files),
            'total_images' => $listing->fresh()->getImageCount(),
        ];

        if ($uploadedImages->isNotEmpty()) {
            $responseData['images'] = ImageResource::collection($uploadedImages);
        }

        if (!empty($errors)) {
            $responseData['errors'] = $errors;
        }

        $statusCode = 200;
        if ($successCount === 0) {
            $responseData['message'] = 'No images were uploaded successfully';
            $statusCode = 400;
        } elseif (!empty($errors)) {
            $responseData['message'] = "Successfully uploaded {$successCount} of " . count($files) . " images";
            $statusCode = 207;
        } else {
            $responseData['message'] = 'All images uploaded successfully!';
        }

        return response()->json($responseData, $statusCode);
    }

    /**
     * Update image details
     * @throws Exception
     */
    public function updateImage(int $listingId, int $imageId, Request $request): JsonResponse
    {
        $listing = $this->listingRepository->findByIdAndUserId($listingId, auth()->id());

        $image = $listing->imagesRelation()->findOrFail($imageId);

        $request->validate([
            'alt_text' => 'sometimes|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
            'is_primary' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $updatedImage = $this->imageUploadService->updateImage($image, [
                'alt_text' => $request->input('alt_text'),
                'sort_order' => $request->input('sort_order'),
                'is_primary' => $request->boolean('is_primary'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'message' => 'Image updated successfully',
                'image' => new ImageResource($updatedImage)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an image
     * @throws Exception
     */
    public function deleteImage(int $listingId, int $imageId): JsonResponse
    {
        $listing = $this->listingRepository->findByIdAndUserId($listingId, auth()->id());

        $image = $listing->imagesRelation()->findOrFail($imageId);

        try {
            $deleted = $this->imageUploadService->deleteImage($image);

            if ($deleted) {
                return response()->json([
                    'message' => 'Image deleted successfully',
                    'remaining_images' => $listing->fresh()->getImageCount()
                ]);
            }

            return response()->json([
                'message' => 'Failed to delete image from storage'
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder listing images
     * @throws Exception
     */
    public function reorderImages(int $listingId, Request $request): JsonResponse
    {
        $listing = $this->listingRepository->findByIdAndUserId($listingId, auth()->id());

        $request->validate([
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'integer|exists:images,id'
        ]);

        $imageIds = $request->input('image_ids');

        $listingImageIds = $listing->imagesRelation()->pluck('id')->toArray();
        $invalidIds = array_diff($imageIds, $listingImageIds);

        if (!empty($invalidIds)) {
            return response()->json([
                'message' => 'Some images do not belong to this listing',
                'invalid_ids' => $invalidIds
            ], 400);
        }

        try {
            $this->imageUploadService->reorderImages($listing, $imageIds);

            return response()->json([
                'message' => 'Images reordered successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to reorder images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get similar listings
     */
    public function getSimilarListings(int $listingId): AnonymousResourceCollection
    {
        $similarListings = $this->listingRepository->getSimilarListings($listingId);
        return ListingCardResource::collection($similarListings);
    }

    /**
     * Get popular listings
     */
    public function getPopularListings(Request $request): AnonymousResourceCollection
    {
        $limit = min((int)$request->get('limit', 10), 50);
        $popularListings = $this->listingRepository->getPopularListings($limit);
        return ListingCardResource::collection($popularListings);
    }

    /**
     * Get recent listings
     */
    public function getRecentListings(Request $request): AnonymousResourceCollection
    {
        $limit = min((int)$request->get('limit', 10), 50);
        $recentListings = $this->listingRepository->getRecentListings($limit);
        return ListingCardResource::collection($recentListings);
    }

    /**
     * Get seller dashboard stats
     */
    public function getSellerStats(): JsonResponse
    {
        $stats = $this->listingRepository->getSellerDashboardStats(auth()->id());

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get listing categories
     */
    public function getCategories(): JsonResponse
    {
        $categories = [];

        foreach (\App\Enums\Listings\ListingCategoryEnum::cases() as $category) {
            $categories[] = [
                'value' => $category->value,
                'label' => match ($category) {
                    \App\Enums\Listings\ListingCategoryEnum::ELECTRONICS => 'Electronics',
                    \App\Enums\Listings\ListingCategoryEnum::FASHION => 'Fashion',
                    \App\Enums\Listings\ListingCategoryEnum::HOME_GARDEN => 'Home & Garden',
                    \App\Enums\Listings\ListingCategoryEnum::AUTOMOTIVE => 'Automotive',
                    \App\Enums\Listings\ListingCategoryEnum::SPORTS_OUTDOORS => 'Sports & Outdoors',
                    \App\Enums\Listings\ListingCategoryEnum::BOOKS_MEDIA => 'Books & Media',
                    \App\Enums\Listings\ListingCategoryEnum::TOYS_GAMES => 'Toys & Games',
                    \App\Enums\Listings\ListingCategoryEnum::HEALTH_BEAUTY => 'Health & Beauty',
                    \App\Enums\Listings\ListingCategoryEnum::BUSINESS_INDUSTRIAL => 'Business & Industrial',
                    \App\Enums\Listings\ListingCategoryEnum::COLLECTIBLES => 'Collectibles',
                    \App\Enums\Listings\ListingCategoryEnum::REAL_ESTATE => 'Real Estate',
                    \App\Enums\Listings\ListingCategoryEnum::SERVICES => 'Services',
                    \App\Enums\Listings\ListingCategoryEnum::OTHER => 'Other',
                }
            ];
        }

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Get listing conditions
     */
    public function getConditions(): JsonResponse
    {
        $conditions = [];

        foreach (\App\Enums\Listings\ListingConditionEnum::cases() as $condition) {
            $conditions[] = [
                'value' => $condition->value,
                'label' => match ($condition) {
                    \App\Enums\Listings\ListingConditionEnum::NEW => 'New',
                    \App\Enums\Listings\ListingConditionEnum::LIKE_NEW => 'Like New',
                    \App\Enums\Listings\ListingConditionEnum::GOOD => 'Good',
                    \App\Enums\Listings\ListingConditionEnum::FAIR => 'Fair',
                    \App\Enums\Listings\ListingConditionEnum::POOR => 'Poor',
                    \App\Enums\Listings\ListingConditionEnum::REFURBISHED => 'Refurbished',
                    \App\Enums\Listings\ListingConditionEnum::FOR_PARTS => 'For Parts',
                }
            ];
        }

        return response()->json([
            'data' => $conditions
        ]);
    }

    /**
     * Add listing to favorites (placeholder for future implementation)
     */
    public function addToFavorites(int $listingId): JsonResponse
    {
        // TODO: Implement favorites functionality
        return response()->json([
            'message' => 'Favorites functionality not yet implemented'
        ], 501);
    }

    /**
     * Remove listing from favorites (placeholder for future implementation)
     */
    public function removeFromFavorites(int $listingId): JsonResponse
    {
        // TODO: Implement favorites functionality
        return response()->json([
            'message' => 'Favorites functionality not yet implemented'
        ], 501);
    }
}