<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Seller;

use Spatie\DataTransferObject\DataTransferObject;

class BookAppointmentData extends DataTransferObject
{
    public const SELLER_PROFILE_ID = 'sellerProfileId';
    public const LISTING_ID = 'listingId';
    public const APPOINTMENT_DATETIME = 'appointmentDatetime';
    public const DURATION_MINUTES = 'durationMinutes';
    public const BUYER_MESSAGE = 'buyerMessage';
    public const MEETING_LOCATION = 'meetingLocation';

    public int $sellerProfileId;
    public ?int $listingId;
    public string $appointmentDatetime;
    public int $durationMinutes;
    public ?string $buyerMessage;
    public ?string $meetingLocation;
}