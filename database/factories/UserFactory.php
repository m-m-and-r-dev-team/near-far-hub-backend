<?php

namespace Database\Factories;

use App\Enums\Roles\RoleEnum;
use App\Models\Roles\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();
        $defaultRoleId = $buyerRole ? $buyerRole->getId() : 1;

        return [
            User::NAME => fake()->name(),
            User::EMAIL => fake()->unique()->safeEmail(),
            User::EMAIL_VERIFIED_AT => now(),
            User::PASSWORD => static::$password ??= Hash::make('password'),
            User::REMEMBER_TOKEN => Str::random(10),
            User::ROLE_ID => $defaultRoleId,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            User::EMAIL_VERIFIED_AT => null,
        ]);
    }

    /**
     * Create a buyer user.
     */
    public function buyer(): static
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();

        return $this->state(fn (array $attributes) => [
            User::ROLE_ID => $buyerRole ? $buyerRole->getId() : 1,
        ]);
    }

    /**
     * Create a seller user.
     */
    public function seller(): static
    {
        $sellerRole = Role::where(Role::NAME, RoleEnum::SELLER->value)->first();

        return $this->state(fn (array $attributes) => [
            User::ROLE_ID => $sellerRole ? $sellerRole->getId() : 2,
        ]);
    }

    /**
     * Create a moderator user.
     */
    public function moderator(): static
    {
        $moderatorRole = Role::where(Role::NAME, RoleEnum::MODERATOR->value)->first();

        return $this->state(fn (array $attributes) => [
            User::ROLE_ID => $moderatorRole ? $moderatorRole->getId() : 3,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        $adminRole = Role::where(Role::NAME, RoleEnum::ADMIN->value)->first();

        return $this->state(fn (array $attributes) => [
            User::ROLE_ID => $adminRole ? $adminRole->getId() : 4,
        ]);
    }
}