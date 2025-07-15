<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Http\Resources\Users\UserResource;
use App\Models\SellerAppointments\SellerAppointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * @var SellerAppointment $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'appointmentDatetime' => SellerAppointment::APPOINTMENT_DATETIME,
        'durationMinutes' => SellerAppointment::DURATION_MINUTES,
        'status' => SellerAppointment::STATUS,
        'buyerMessage' => SellerAppointment::BUYER_MESSAGE,
        'sellerResponse' => SellerAppointment::SELLER_RESPONSE,
        'meetingLocation' => SellerAppointment::MEETING_LOCATION,
        'meetingNotes' => SellerAppointment::MEETING_NOTES,
        'createdAt' => SellerAppointment::CREATED_AT,
        'updatedAt' => SellerAppointment::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'endTime' => $this->resource->getEndTimeAttribute()->toISOString(),
            'seller' => SellerProfileResource::make($this->resource->relatedSellerProfile()),
            'buyer' => UserResource::make($this->resource->relatedBuyer()),
//            'listing' => $this->when($this->resource->listing, [
//                'id' => $this->resource->listing?->id,
//                'title' => $this->resource->listing?->title,
//                'price' => $this->resource->listing?->price,
//                'images' => $this->resource->listing?->images,
//            ]),
        ];
    }
}