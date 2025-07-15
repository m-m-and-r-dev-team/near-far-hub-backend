<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\CreateSellerProfileRequest;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Http\Requests\Seller\SetAvailabilityRequest;
use App\Http\Resources\Seller\SellerProfileResource;
use App\Http\Resources\Seller\SellerAvailabilityResource;
use App\Services\Repositories\Seller\SellerProfileRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class SellerController extends Controller
{
    public function __construct(
        private readonly SellerProfileRepository $sellerProfileRepository
    )
    {
    }

    /**
     * Get current user's seller profile
     */
    public function getProfile(): SellerProfileResource|JsonResponse
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId(auth()->id());

        if (!$sellerProfile) {
            return response()->json([
                'message' => 'Seller profile not found'
            ], 404);
        }

        return new SellerProfileResource($sellerProfile);
    }

    /**
     * Create seller profile
     * @throws UnknownProperties
     * @throws Exception
     */
    public function createProfile(CreateSellerProfileRequest $request): SellerProfileResource
    {
        $sellerProfile = $this->sellerProfileRepository->create(
            auth()->id(),
            $request->dto()
        );

        return new SellerProfileResource($sellerProfile);
    }

    /**
     * Update seller profile
     * @throws UnknownProperties
     */
    public function updateProfile(UpdateSellerProfileRequest $request): SellerProfileResource
    {
        $sellerProfile = $this->sellerProfileRepository->update(
            auth()->id(),
            $request->dto()
        );

        return new SellerProfileResource($sellerProfile);
    }

    /**
     * Set seller availability
     * @throws UnknownProperties
     */
    public function setAvailability(SetAvailabilityRequest $request): JsonResponse
    {
        $this->sellerProfileRepository->setAvailability(
            auth()->id(),
            $request->dto()
        );

        return response()->json([
            'message' => 'Availability updated successfully'
        ]);
    }

    /**
     * Get seller availability
     */
    public function getAvailability(): JsonResponse
    {
        $availability = $this->sellerProfileRepository->getAvailability(auth()->id());

        return response()->json([
            'data' => SellerAvailabilityResource::collection($availability)
        ]);
    }

    /**
     * Get seller dashboard stats
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = $this->sellerProfileRepository->getDashboardStats(auth()->id());

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Deactivate seller account
     */
    public function deactivateAccount(): JsonResponse
    {
        $this->sellerProfileRepository->deactivateAccount(auth()->id());

        return response()->json([
            'message' => 'Seller account deactivated successfully'
        ]);
    }
}