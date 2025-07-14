<?php

declare(strict_types=1);

namespace App\Services\Repositories\Auth;

use App\Http\DataTransferObjects\Auth\AuthResponseData;
use App\Http\DataTransferObjects\Auth\LoginRequestData;
use App\Http\DataTransferObjects\Auth\RegisterRequestData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class AuthLogicRepository
{
    private const TOKEN_NAME = 'auth_token';
    private const TOKEN_TYPE = 'Bearer';

    public function __construct(
        private readonly AuthDbRepository $authDbRepository
    ) {
    }

    /**
     * @throws UnknownProperties
     */
    public function register(RegisterRequestData $data): AuthResponseData
    {
        $user = $this->authDbRepository->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return new AuthResponseData([
            AuthResponseData::USER => $user,
            AuthResponseData::TOKEN => $token,
            AuthResponseData::TOKEN_TYPE => self::TOKEN_TYPE,
        ]);
    }

    /**
     * @throws UnknownProperties
     * @throws ValidationException
     */
    public function login(LoginRequestData $data): AuthResponseData
    {
        $user = $this->authDbRepository->findByEmail($data->email);

        if (!$user || !Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$data->remember) {
            $user->tokens()->delete();
        }

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return new AuthResponseData([
            AuthResponseData::USER => $user,
            AuthResponseData::TOKEN => $token,
            AuthResponseData::TOKEN_TYPE => self::TOKEN_TYPE,
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public function logout(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException('User not authenticated');
        }

        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->delete();
        }
    }

    /**
     * @throws AuthenticationException
     */
    public function getCurrentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException('User not authenticated');
        }

        return $user;
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}