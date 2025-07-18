<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Seller;

use Spatie\DataTransferObject\DataTransferObject;

class SellerAvailabilityData extends DataTransferObject
{
    public const DAY_OF_WEEK = 'dayOfWeek';
    public const START_TIME = 'startTime';
    public const END_TIME = 'endTime';
    public const IS_ACTIVE = 'isActive';

    public string $dayOfWeek;
    public string $startTime;
    public string $endTime;
    public bool $isActive;
}