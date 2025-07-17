<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testLoginSuccess(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $payload = [
            'email' => $user->getEmail(),
            'password' => 'password',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertOk();

//        $expected = [
//            'user' => [
//                'id' => $user->getId(),
//                'name' => $user->getName(),
//                'email' => $user->getEmail(),
//                'email_verified_at' => $user->getEmailVerifiedAt(),
//                'created_at' => $user->getCreatedAt(),
//                'updated_at' => $user->getUpdatedAt(),
//            ],
//            'token' => '1|i7gfBZEc1nxsFHigFHWY3OzFAFfwZgyIwgb2YZdJ4fe91ea4',
//            'tokenType' => 'Bearer',
//        ];
//
//        $this->assertSame($expected, $response['data']);
    }

    public function testLoginPasswordDontMatch(): void
    {
        $user = User::factory()->create();

        $payload = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertUnprocessable();

        $expected = [
            'email' => [
                'The provided credentials are incorrect.',
            ],
        ];

        $this->assertSame($expected, $response['errors']);
    }

    public function testLoginEmailWrongDontMatch(): void
    {
        $payload = [
            'email' => 'useremail@mail.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertUnprocessable();

        $expected = [
            'email' => [
                'The provided credentials are incorrect.',
            ],
        ];

        $this->assertSame($expected, $response['errors']);
    }
}
