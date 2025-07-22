<?php

declare(strict_types=1);

namespace App\Services\Repositories\Users;

use App\Http\DataTransferObjects\Users\UpdateProfileRequestData;
use App\Models\User;
use App\Services\Locations\HybridLocationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserLogicRepository
{
    public function __construct(
        private readonly UserDbRepository $userDbRepository,
        private readonly HybridLocationService $hybridLocationService
    )
    {
    }

    /**
     * Update user profile with intelligent location handling
     */
    public function updateProfileAccount(int $userId, UpdateProfileRequestData $data): User
    {
        $user = $this->userDbRepository->findOrFail($userId);

        // Prepare basic profile data
        $updateData = [
            User::NAME => $data->name,
            User::PHONE => $data->phone,
            User::BIO => $data->bio,
        ];

        // Handle location data if provided
        if (!empty($data->location)) {
            $locationResult = $this->hybridLocationService->saveUserLocation($userId, $data->location);

            if ($locationResult['success']) {
                // The location service already updated the user, so we'll refresh
                $user = $user->fresh(['country', 'state', 'city', 'roleRelation']);
                return $user;
            }
        }

        // Update basic data only if no location update
        return $this->userDbRepository->update($user, array_filter($updateData, fn($value) => $value !== null));
    }

    /**
     * Get user profile with location data
     */
    public function getUserProfile(int $userId): User
    {
        return $this->userDbRepository->findWithLocation($userId);
    }
}