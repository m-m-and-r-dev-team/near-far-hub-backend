<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Auth;

use Spatie\DataTransferObject\DataTransferObject;

class LoginRequestData extends DataTransferObject
{
    public const EMAIL = 'email';
    public const PASSWORD = 'password';
    public const REMEMBER = 'remember';

    public string $email;
    public string $password;
    public bool $remember;
}