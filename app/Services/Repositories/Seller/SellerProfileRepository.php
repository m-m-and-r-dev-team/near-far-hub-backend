<?php

declare(strict_types=1);

namespace App\Services\Repositories\Seller;

use App\Models\SellerAvailabilities\SellerAvailability;
use App\Models\SellerProfiles\SellerProfile;
use App\Http\DataTransferObjects\Seller\CreateSellerProfileData;
use App\Http\DataTransferObjects\Seller\UpdateSellerProfileData;
use App\Http\DataTransferObjects\Seller\SetAvailabilityData;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SellerProfileRepository
{
    public function __construct(
        private readonly SellerProfile $sellerProfile,
        private readonly SellerAvailability $sellerAvailability,
    ) {
    }

    public function getByUserId(int $userId): ?SellerProfile
    {
        return $this->sellerProfile
            ->with([SellerProfile::USER_RELATION, SellerProfile::AVAILABILITY_RELATION])
            ->where(SellerProfile::USER_ID, $userId)
            ->first();
    }

    /**
     * @throws Exception
     */
    public function create(int $userId, CreateSellerProfileData $data): SellerProfile
    {
        if ($this->getByUserId($userId)) {
            throw new Exception('User already has a seller profile');
        }

        $payload = [
            SellerProfile::USER_ID => $userId,
            SellerProfile::BUSINESS_NAME => $data->businessName,
            SellerProfile::BUSINESS_DESCRIPTION => $data->businessDescription,
            SellerProfile::BUSINESS_TYPE => $data->businessType,
            SellerProfile::PHONE => $data->phone,
            SellerProfile::ADDRESS => $data->address,
            SellerProfile::CITY => $data->city,
            SellerProfile::POSTAL_CODE => $data->postalCode,
            SellerProfile::COUNTRY => $data->country,
            SellerProfile::IS_ACTIVE => true,
            SellerProfile::IS_VERIFIED => false,
        ];

        $sellerProfile = $this->sellerProfile->create($payload);

        return $sellerProfile->load([SellerProfile::USER_RELATION, SellerProfile::AVAILABILITY_RELATION]);
    }

    public function update(int $userId, UpdateSellerProfileData $data): SellerProfile
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $updateData = array_filter([
            SellerProfile::BUSINESS_NAME => $data->businessName,
            SellerProfile::BUSINESS_DESCRIPTION => $data->businessDescription,
            SellerProfile::BUSINESS_TYPE => $data->businessType,
            SellerProfile::PHONE => $data->phone,
            SellerProfile::ADDRESS => $data->address,
            SellerProfile::CITY => $data->city,
            SellerProfile::POSTAL_CODE => $data->postalCode,
            SellerProfile::COUNTRY => $data->country,
        ], fn($value) => $value !== null);

        $sellerProfile->update($updateData);

        return $sellerProfile->load([SellerProfile::USER_RELATION, SellerProfile::AVAILABILITY_RELATION]);
    }

    public function setAvailability(int $userId, SetAvailabilityData $data): void
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $this->sellerAvailability
            ->where(SellerAvailability::SELLER_PROFILE_ID, $sellerProfile->getId())
            ->delete();

        foreach ($data->availability as $slot) {
            $this->sellerAvailability->create([
                SellerAvailability::SELLER_PROFILE_ID => $sellerProfile->id,
                SellerAvailability::DAY_OF_WEEK => $slot->dayOfWeek,
                SellerAvailability::START_TIME => $slot->startTime,
                SellerAvailability::END_TIME => $slot->endTime,
                SellerAvailability::IS_ACTIVE => $slot->isActive,
            ]);
        }
    }

    public function getAvailability(int $userId): Collection
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        return $this->sellerAvailability
            ->where(SellerAvailability::SELLER_PROFILE_ID, $sellerProfile->getId())
            ->orderBy(SellerAvailability::DAY_OF_WEEK)
            ->get();
    }

    public function getDashboardStats(int $userId): array
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        return [
            'totalAppointments' => $sellerProfile->relatedAppointments()->count(),
            'pendingAppointments' => $sellerProfile->relatedAppointments()->where('status', 'pending')->count(),
            'approvedAppointments' => $sellerProfile->relatedAppointments()->where('status', 'approved')->count(),
            'completedAppointments' => $sellerProfile->relatedAppointments()->where('status', 'completed')->count(),
            'totalListings' => 0, // TODO: Implement when listings are created
            'activeListings' => 0, // TODO: Implement when listings are created
            'totalEarnings' => 0, // TODO: Implement when payment system is ready
            'listingFeeBalance' => $sellerProfile->getListingFeeBalance(),
        ];
    }

    public function deactivateAccount(int $userId): void
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $sellerProfile->update([SellerProfile::IS_ACTIVE => false]);
    }

    public function activateAccount(int $userId): void
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $sellerProfile->update([SellerProfile::IS_ACTIVE => true]);
    }

    public function verifyAccount(int $userId): void
    {
        $sellerProfile = $this->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $sellerProfile->update([
            SellerProfile::IS_VERIFIED => true,
            SellerProfile::VERIFIED_AT => now(),
        ]);
    }

    public function findById(int $id): ?SellerProfile
    {
        return $this->sellerProfile
            ->with([SellerProfile::USER_RELATION, SellerProfile::AVAILABILITY_RELATION])
            ->find($id);
    }

    /**
     * @throws Exception
     */
    public function deductListingFee(int $sellerProfileId, float $amount): void
    {
        /** @var SellerProfile $sellerProfile */
        $sellerProfile = $this->sellerProfile->findOrFail($sellerProfileId);

        if ($sellerProfile->getListingFeeBalance() < $amount) {
            throw new Exception('Insufficient listing fee balance');
        }

        $sellerProfile->decrement(SellerProfile::LISTING_FEE_BALANCE, $amount);
    }

    public function addListingFeeBalance(int $sellerProfileId, float $amount): void
    {
        $sellerProfile = $this->sellerProfile->findOrFail($sellerProfileId);
        $sellerProfile->increment(SellerProfile::LISTING_FEE_BALANCE, $amount);
    }
}