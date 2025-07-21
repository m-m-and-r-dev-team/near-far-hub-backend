<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Users;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateProfileRequestData extends DataTransferObject
{
    public const NAME = 'name';
    public const PHONE = 'phone';
    public const LOCATION = 'location';
    public const BIO = 'bio';

    public string $name;
    public ?string $phone;
    public ?string $location;
    public ?string $bio;
}