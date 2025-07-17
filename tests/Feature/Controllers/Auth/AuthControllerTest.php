<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testLogin(): void
    {
        $user = User::factory()->create();

        $payload = [
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertStatus(200);
    }
}
