<?php

declare(strict_types=1);

namespace App\Services\Repositories\Seller;

use App\Enums\Seller\SellerAppointmentStatusEnum;
use App\Http\DataTransferObjects\Seller\BookAppointmentData;
use App\Http\DataTransferObjects\Seller\RespondToAppointmentData;
use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerProfiles\SellerProfile;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class AppointmentRepository
{
    public function __construct(
        private readonly SellerAppointment $appointment,
        private readonly SellerProfile $sellerProfile,
        private readonly SellerProfileRepository $sellerProfileRepository
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(int $buyerId, BookAppointmentData $data): SellerAppointment
    {
        $sellerProfile = $this->sellerProfileRepository->findById($data->sellerProfileId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        if (!$sellerProfile->getIsActive()) {
            throw new Exception('Seller account is not active');
        }

        $appointmentDatetime = Carbon::parse($data->appointmentDatetime);

        // Check if seller is available on this day and time
        if (!$this->isSellerAvailable($sellerProfile, $appointmentDatetime)) {
            throw new Exception('Seller is not available at this time');
        }

        // Check for conflicts
        if ($this->hasConflictingAppointment($sellerProfile->id, $appointmentDatetime, $data->durationMinutes)) {
            throw new Exception('This time slot is already booked');
        }

        $payload = [
            SellerAppointment::SELLER_PROFILE_ID => $data->sellerProfileId,
            SellerAppointment::BUYER_ID => $buyerId,
            SellerAppointment::LISTING_ID => $data->listingId,
            SellerAppointment::APPOINTMENT_DATETIME => $appointmentDatetime,
            SellerAppointment::DURATION_MINUTES => $data->durationMinutes,
            SellerAppointment::BUYER_MESSAGE => $data->buyerMessage,
            SellerAppointment::MEETING_LOCATION => $data->meetingLocation,
            SellerAppointment::STATUS => SellerAppointmentStatusEnum::STATUS_PENDING->value,
        ];

        $appointment = $this->appointment->create($payload);

        return $appointment->load(['sellerProfile.userRelation', SellerAppointment::BUYER_RELATION, 'listing']);
    }

    public function getSellerAppointments(int $userId, ?string $status = null, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $query = $this->appointment
            ->with(['sellerProfile.userRelation', SellerAppointment::BUYER_RELATION, 'listing'])
            ->where(SellerAppointment::SELLER_PROFILE_ID, $sellerProfile->getId())
            ->orderBy(SellerAppointment::APPOINTMENT_DATETIME, 'desc');

        if ($status) {
            $query->where(SellerAppointment::STATUS, $status);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getBuyerAppointments(int $buyerId, ?string $status = null, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->appointment
            ->with(['sellerProfile.userRelation', SellerAppointment::BUYER_RELATION, 'listing'])
            ->where(SellerAppointment::BUYER_ID, $buyerId)
            ->orderBy(SellerAppointment::APPOINTMENT_DATETIME, 'desc');

        if ($status) {
            $query->where(SellerAppointment::STATUS, $status);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @throws Exception
     */
    public function respondToAppointment(int $appointmentId, int $sellerId, RespondToAppointmentData $data): SellerAppointment
    {
        /** @var SellerAppointment $appointment */
        $appointment = $this->appointment->findOrFail($appointmentId);

        // Verify the seller owns this appointment
        $sellerProfile = $this->sellerProfileRepository->getByUserId($sellerId);
        if (!$sellerProfile || $appointment->getSellerProfileId() !== $sellerProfile->getId()) {
            throw new Exception('Unauthorized to respond to this appointment');
        }

        if ($appointment->getStatus() !== SellerAppointmentStatusEnum::STATUS_PENDING->value) {
            throw new Exception('Appointment has already been responded to');
        }

        $payload = [
            SellerAppointment::STATUS => $data->status,
            SellerAppointment::SELLER_RESPONSE => $data->sellerResponse,
            SellerAppointment::MEETING_LOCATION => $data->meetingLocation,
            SellerAppointment::MEETING_NOTES => $data->meetingNotes,
        ];

        $appointment->update($payload);

        return $appointment->load(['sellerProfile.userRelation', SellerAppointment::BUYER_RELATION, 'listing']);
    }

    /**
     * @throws Exception
     */
    public function cancelAppointment(int $appointmentId, int $userId): void
    {
        /** @var SellerAppointment $appointment */
        $appointment = $this->appointment->findOrFail($appointmentId);

        // Check if user is either the buyer or seller
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);
        $isSeller = $sellerProfile && $appointment->getSellerProfileId() === $sellerProfile->getId();
        $isBuyer = $appointment->getBuyerId() === $userId;

        if (!$isSeller && !$isBuyer) {
            throw new Exception('Unauthorized to cancel this appointment');
        }

        if (!in_array($appointment->getStatus(), [SellerAppointmentStatusEnum::STATUS_PENDING->value, 'approved'])) {
            throw new Exception('Cannot cancel appointment in current status');
        }

        $payload = [
            SellerAppointment::STATUS => SellerAppointmentStatusEnum::STATUS_CANCELED->value,
        ];

        $appointment->update($payload);
    }

    public function getAvailableSlots(int $sellerProfileId, string $date, int $durationMinutes = 30): array
    {
        $sellerProfile = $this->sellerProfileRepository->findById($sellerProfileId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $requestedDate = Carbon::parse($date);
        $dayOfWeek = strtolower($requestedDate->format('l'));

        $availability = $sellerProfile->getAvailabilityFor($dayOfWeek);

        if (!$availability) {
            return [];
        }

        $slots = [];
        $startTime = Carbon::parse($requestedDate->format('Y-m-d') . ' ' . $availability->getStartTime());
        $endTime = Carbon::parse($requestedDate->format('Y-m-d') . ' ' . $availability->getEndTime());

        while ($startTime->copy()->addMinutes($durationMinutes)->lte($endTime)) {
            $slotEnd = $startTime->copy()->addMinutes($durationMinutes);

            $isAvailable = !$this->hasConflictingAppointment(
                $sellerProfileId,
                $startTime,
                $durationMinutes
            );

            $slots[] = [
                'datetime' => $startTime->toISOString(),
                'start_time' => $startTime->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'available' => $isAvailable,
            ];

            $startTime->addMinutes($durationMinutes);
        }

        return $slots;
    }

    private function isSellerAvailable(SellerProfile $sellerProfile, Carbon $datetime): bool
    {
        $dayOfWeek = strtolower($datetime->format('l'));
        $time = $datetime->format('H:i');

        $availability = $sellerProfile->getAvailabilityFor($dayOfWeek);

        if (!$availability) {
            return false;
        }

        return $time >= $availability->getStartTime()->format('H:i') &&
            $time <= $availability->getEndTime()->format('H:i');
    }

    private function hasConflictingAppointment(int $sellerProfileId, Carbon $datetime, int $durationMinutes): bool
    {
        $endTime = $datetime->copy()->addMinutes($durationMinutes);

        return $this->appointment
            ->where(SellerAppointment::SELLER_PROFILE_ID, $sellerProfileId)
            ->whereIn(SellerAppointment::STATUS, [SellerAppointmentStatusEnum::STATUS_PENDING->value, SellerAppointmentStatusEnum::STATUS_APPROVED->value])
            ->where(function($query) use ($datetime, $endTime) {
                $query->whereBetween(SellerAppointment::APPOINTMENT_DATETIME, [$datetime, $endTime])
                    ->orWhere(function($q) use ($datetime, $endTime) {
                        $q->where(SellerAppointment::APPOINTMENT_DATETIME, '<', $datetime)
                            ->whereRaw('DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE) > ?', [$datetime]);
                    });
            })
            ->exists();
    }
}