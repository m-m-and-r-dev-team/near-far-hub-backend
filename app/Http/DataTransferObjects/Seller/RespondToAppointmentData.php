<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Seller;

use Spatie\DataTransferObject\DataTransferObject;

class RespondToAppointmentData extends DataTransferObject
{
    public const STATUS = 'status';
    public const SELLER_RESPONSE = 'sellerResponse';
    public const MEETING_LOCATION = 'meetingLocation';
    public const MEETING_NOTES = 'meetingNotes';

    public string $status;
    public ?string $sellerResponse;
    public ?string $meetingLocation;
    public ?string $meetingNotes;
}