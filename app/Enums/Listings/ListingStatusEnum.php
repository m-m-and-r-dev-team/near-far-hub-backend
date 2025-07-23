<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingStatusEnum: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case SOLD = 'sold';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case PENDING_APPROVAL = 'pending_approval';
    case REJECTED = 'rejected';
}