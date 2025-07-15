<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Seller;

use Spatie\DataTransferObject\DataTransferObject;

class SetAvailabilityData extends DataTransferObject
{
    public const AVAILABILITY = 'availability';

    /** @var SellerAvailabilityData[] */
    public array $availability;
}