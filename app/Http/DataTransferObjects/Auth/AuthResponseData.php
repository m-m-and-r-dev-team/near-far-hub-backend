<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Auth;

use App\Models\User;
use Spatie\DataTransferObject\DataTransferObject;

class AuthResponseData extends DataTransferObject
{
    public const USER = 'user';
    public const TOKEN = 'token';
    public const TOKEN_TYPE = 'tokenType';

    public User $user;
    public string $token;
    public string $tokenType;
}