<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Users;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateProfileRequestData extends DataTransferObject
{
    public const NAME = 'name';
    public const PHONE = 'phone';
    public const BIO = 'bio';
    public const LOCATION = 'location'; // This will be the location object

    public string $name;
    public ?string $phone;
    public ?string $bio;
    public ?array $location; // Complete location data from frontend
}
