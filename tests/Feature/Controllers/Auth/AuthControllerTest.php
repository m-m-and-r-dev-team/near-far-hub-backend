<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;

use App\Enums\Roles\RoleEnum;
use App\Models\Roles\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testLoginSuccess(): void
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();

        /** @var User $user */
        $user = User::factory()->create([
            'role_id' => $buyerRole->id,
        ]);

        $payload = [
            'email' => $user->getEmail(),
            'password' => 'password',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertOk();

        $role = $user->relatedRole();

        $expected = [
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'email_verified_at' => $user->getEmailVerifiedAt()?->toISOString(),
                'created_at' => $user->getCreatedAt()->toISOString(),
                'updated_at' => $user->getUpdatedAt()->toISOString(),
                'role_id' => $user->getRoleId(),
                'role_relation' => [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'display_name' => $role->getDisplayName(),
                    'description' => $role->getDescription(),
                    'permissions' => $role->getPermissions(),
                    'is_active' => $role->getIsActive(),
                    'created_at' => $role->getCreatedAt()->toISOString(),
                    'updated_at' => $role->getUpdatedAt()->toISOString(),
                ],
            ],
            'token' => $response['data']['token'],
            'tokenType' => $response['data']['tokenType'],
        ];


        $this->assertSame($expected, $response['data']);
    }

    public function testLoginPasswordDontMatch(): void
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();

        $user = User::factory()->create([
            'role_id' => $buyerRole->id,
        ]);

        $payload = [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertUnprocessable();

        $expected = [
            'error' => [
                'The provided credentials are incorrect.',
            ],
        ];

        $this->assertSame($expected, $response['errors']);
    }

    public function testLoginEmailWrongDontMatch(): void
    {
        $payload = [
            'email' => 'not@existing.com',
            'password' => 'any-password',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertUnprocessable();

        $expected = [
            'error' => [
                'The provided credentials are incorrect.',
            ],
        ];

        $this->assertSame($expected, $response['errors']);
    }

    public function testLogoutSuccess(): void
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();

        /** @var User $user */
        $user = User::factory()->create([
            'role_id' => $buyerRole->id,
        ]);

        $payload = [
            'email' => $user->getEmail(),
            'password' => 'password',
        ];

        $this->postJson('api/auth/login', $payload);

        $response = $this->actingAs($user)->postJson('api/auth/logout');

        $response->assertOk();
    }

    public function testLogoutUnauthorized(): void
    {
        $response = $this->postJson('api/auth/logout');

        $response->assertUnauthorized();
    }

    public function testGetCurrentUserSuccess(): void
    {
        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();

        /** @var User $user */
        $user = User::factory()->create([
            'role_id' => $buyerRole->id,
        ]);

        $response = $this->actingAs($user)->getJson('api/auth/user');

        $response->assertOk();

        $role = $user->relatedRole();

        $expected = [
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'email_verified_at' => $user->getEmailVerifiedAt()?->toISOString(),
            'role_id' => $user->getRoleId(),
            'updated_at' => $user->getUpdatedAt()->toISOString(),
            'created_at' => $user->getCreatedAt()->toISOString(),
            'id' => $user->getId(),
            'role_relation' => [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'display_name' => $role->getDisplayName(),
                'description' => $role->getDescription(),
                'permissions' => $role->getPermissions(),
                'is_active' => $role->getIsActive(),
                'created_at' => $role->getCreatedAt()->toISOString(),
                'updated_at' => $role->getUpdatedAt()->toISOString(),
            ]
        ];

        $this->assertSame($expected, $response['data']);
    }


    public function testGetCurrentUserUnauthorized(): void
    {
        $response = $this->getJson('api/auth/user');

        $response->assertUnauthorized();
    }
}
