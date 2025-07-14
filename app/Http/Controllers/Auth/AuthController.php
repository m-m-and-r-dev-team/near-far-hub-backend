<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\Users\UserResource;
use App\Services\Repositories\Auth\AuthLogicRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthLogicRepository $authLogicRepository
    ) {
    }

    /**
     * @throws UnknownProperties
     */
    public function register(RegisterRequest $request): AuthResource
    {
        return $request->responseResource(
            $this->authLogicRepository->register($request->dto())
        );
    }

    /**
     * @throws UnknownProperties
     * @throws ValidationException
     */
    public function login(LoginRequest $request): AuthResource
    {
        return $request->responseResource(
            $this->authLogicRepository->login($request->dto())
        );
    }

    /**
     * @throws AuthenticationException
     */
    public function logout(): JsonResponse
    {
        $this->authLogicRepository->logout();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public function user(): UserResource
    {
        return UserResource::make(
            $this->authLogicRepository->getCurrentUser()
        );
    }

    public function forgotPassword(): JsonResponse
    {
        // TODO: Implement forgot password logic
        return response()->json([
            'message' => 'Password reset link sent to your email'
        ]);
    }

    public function resetPassword(): JsonResponse
    {
        // TODO: Implement reset password logic
        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }
}