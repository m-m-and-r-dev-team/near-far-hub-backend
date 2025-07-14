<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Auth;

use Spatie\DataTransferObject\DataTransferObject;

class RegisterRequestData extends DataTransferObject
{
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const PASSWORD = 'password';
    public const PASSWORD_CONFIRMATION = 'passwordConfirmation';

    public string $name;
    public string $email;
    public string $password;
    public string $passwordConfirmation;
}