<?php

declare(strict_types=1);

namespace App\Services\Repositories\Auth;

use App\Enums\Roles\RoleEnum;
use App\Http\DataTransferObjects\Auth\AuthResponseData;
use App\Http\DataTransferObjects\Auth\LoginRequestData;
use App\Http\DataTransferObjects\Auth\RegisterRequestData;
use App\Models\Roles\Role;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class AuthLogicRepository
{
    private const TOKEN_NAME = 'auth_token';
    private const TOKEN_TYPE = 'Bearer';

    public function __construct(
        private readonly AuthDbRepository $authDbRepository
    )
    {
    }

    /**
     * @throws UnknownProperties
     */
    public function register(RegisterRequestData $data): AuthResponseData
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();

        if (!$buyerRole) {
            throw new RuntimeException('Buyer role not found. Please run database seeders.');
        }

        $payload = [
            User::NAME => $data->name,
            User::EMAIL => $data->email,
            User::PASSWORD => Hash::make($data->password),
            User::ROLE_ID => $buyerRole->getId(),
        ];

        $user = $this->authDbRepository->create($payload);

        $user->load(User::ROLE_RELATION);

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

        if (!$user || !Hash::check($data->password, $user->getPassword())) {
            throw ValidationException::withMessages([
                'error' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->load(User::ROLE_RELATION);

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

        if ($currentToken instanceof PersonalAccessToken) {
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

        $user->load(User::ROLE_RELATION);

        return $user;
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function upgradeToSeller(User $user): bool
    {
        if (!$user->canUpgradeToSeller()) {
            return false;
        }

        $sellerRole = Role::where(Role::NAME, RoleEnum::SELLER->value)->first();

        if (!$sellerRole) {
            throw new RuntimeException('Seller role not found.');
        }

        $user->update([User::ROLE_ID => $sellerRole->getId()]);
        $user->load(User::ROLE_RELATION);

        return true;
    }
}