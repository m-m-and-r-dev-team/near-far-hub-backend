<?php

declare(strict_types=1);

namespace App\Enums\Roles;

enum RoleEnum : string
{
    case BUYER = 'buyer';
    case SELLER = 'seller';
    case MODERATOR = 'moderator';
    case ADMIN = 'admin';
}
