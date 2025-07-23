<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingDeliveryEnum: string
{
    case PICKUP_ONLY = 'pickup_only';
    case LOCAL_DELIVERY = 'local_delivery';
    case NATIONAL_SHIPPING = 'national_shipping';
    case INTERNATIONAL_SHIPPING = 'international_shipping';
    case DIGITAL_DELIVERY = 'digital_delivery';
}