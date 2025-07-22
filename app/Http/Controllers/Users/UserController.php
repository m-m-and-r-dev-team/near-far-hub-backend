<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Resources\Users\UserResource;
use App\Services\Repositories\Users\UserLogicRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UserController extends Controller
{
    public function __construct(
        private readonly UserLogicRepository $userLogicRepository
    )
    {
    }

    /**
     * Get current user profile
     */
    public function getProfile(): UserResource
    {
        $user = $this->userLogicRepository->getUserProfile(auth()->id());
        return new UserResource($user);
    }

    /**
     * Update the user profile with location
     */
    public function updateProfileAccount(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->userLogicRepository->updateProfileAccount(
                auth()->id(),
                $request->dto()
            );

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => new UserResource($user)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}