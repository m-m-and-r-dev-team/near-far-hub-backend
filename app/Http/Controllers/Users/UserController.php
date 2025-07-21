<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Resources\Users\UserResource;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UserController extends Controller
{
    public function __construct(
        private readonly UserLogicRepository $userLogicRepository
    )
    {
    }

    /**
     * @throws UnknownProperties
     */
    public function updateProfileAccount(UpdateProfileRequest $request): UserResource
    {
        return $request->responseResource(
            $this->userLogicRepository->updateProfileAccount(
                auth()->id(),
                $request->dto()
            )
        );
    }
}