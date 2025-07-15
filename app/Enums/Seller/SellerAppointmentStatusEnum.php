<?php

declare(strict_types=1);

namespace App\Enums\Seller;

enum SellerAppointmentStatusEnum : string
{
    case STATUS_PENDING = 'pending';
    case STATUS_APPROVED = 'approved';
    case STATUS_CANCELED = 'cancelled';
}
