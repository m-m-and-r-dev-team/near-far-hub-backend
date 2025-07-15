<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\BookAppointmentRequest;
use App\Http\Requests\Seller\RespondToAppointmentRequest;
use App\Http\Resources\Seller\AppointmentResource;
use App\Services\Repositories\Seller\AppointmentRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository
    ) {
    }

    /**
     * Book an appointment with a seller
     * @throws UnknownProperties
     * @throws Exception
     */
    public function bookAppointment(BookAppointmentRequest $request): AppointmentResource
    {
        $appointment = $this->appointmentRepository->create(
            auth()->id(),
            $request->dto()
        );

        return new AppointmentResource($appointment);
    }

    /**
     * Get seller's appointments
     */
    public function getSellerAppointments(Request $request): JsonResponse
    {
        $appointments = $this->appointmentRepository->getSellerAppointments(
            auth()->id(),
            $request->get('status'),
            $request->get('page', 1),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => AppointmentResource::collection($appointments->items()),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'total' => $appointments->total(),
                'per_page' => $appointments->perPage(),
                'last_page' => $appointments->lastPage(),
            ]
        ]);
    }

    /**
     * Get buyer's appointments
     */
    public function getBuyerAppointments(Request $request): JsonResponse
    {
        $appointments = $this->appointmentRepository->getBuyerAppointments(
            auth()->id(),
            $request->get('status'),
            $request->get('page', 1),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => AppointmentResource::collection($appointments->items()),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'total' => $appointments->total(),
                'per_page' => $appointments->perPage(),
                'last_page' => $appointments->lastPage(),
            ]
        ]);
    }

    /**
     * Respond to appointment (approve/reject)
     * @throws UnknownProperties
     * @throws Exception
     */
    public function respondToAppointment(
        int $appointmentId,
        RespondToAppointmentRequest $request
    ): AppointmentResource {
        $appointment = $this->appointmentRepository->respondToAppointment(
            $appointmentId,
            auth()->id(),
            $request->dto()
        );

        return new AppointmentResource($appointment);
    }

    /**
     * Cancel appointment
     * @throws Exception
     */
    public function cancelAppointment(int $appointmentId): JsonResponse
    {
        $this->appointmentRepository->cancelAppointment($appointmentId, auth()->id());

        return response()->json([
            'message' => 'Appointment cancelled successfully'
        ]);
    }

    /**
     * Get available slots for a seller
     */
    public function getAvailableSlots(int $sellerProfileId, Request $request): JsonResponse
    {
        $date = $request->get('date');
        $duration = $request->get('duration_minutes', 30);

        $slots = $this->appointmentRepository->getAvailableSlots(
            $sellerProfileId,
            $date,
            $duration
        );

        return response()->json([
            'data' => $slots
        ]);
    }
}