<?php

declare(strict_types=1);

namespace App\Services\Repositories\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthDbRepository
{
    public function __construct(private readonly User $user)
    {
    }

    public function create(array $data): User
    {
        return $this->user->create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->user->where(User::EMAIL, $email)->first();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $userId): User
    {
        return $this->user->findOrFail($userId);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->refresh();
    }

    public function emailExists(string $email): bool
    {
        return $this->user->where('email', $email)->exists();
    }
}