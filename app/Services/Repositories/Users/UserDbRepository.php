<?php

declare(strict_types=1);

namespace App\Services\Repositories\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

readonly class UserDbRepository
{
    public function __construct(
        private User $user
    )
    {
    }

    /**
     * Find a user by ID or fail
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $userId): User
    {
        return $this->user->with([User::ROLE_RELATION])->findOrFail($userId);
    }

    /**
     * Update user with given data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh([User::ROLE_RELATION]);
    }

    /**
     * Find the user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->user->where(User::EMAIL, $email)->first();
    }

    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        return $this->user->create($data);
    }

    /**
     * Find user by ID with location relations
     * @throws ModelNotFoundException
     */
    public function findWithLocation(int $userId): User
    {
        return $this->user
            ->with([
                User::ROLE_RELATION,
                'country',
                'state',
                'city'
            ])
            ->findOrFail($userId);
    }
}