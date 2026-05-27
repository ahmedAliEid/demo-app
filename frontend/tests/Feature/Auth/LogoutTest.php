<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    #[Test]
    public function logout_clears_session_and_redirects_to_login(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/logout' => Http::response(null, 204),
        ]);

        $this->withSession(['token' => 'some-token', 'role' => 'USER']);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertFalse(session()->has('token'));
        $this->assertFalse(session()->has('role'));
    }

    #[Test]
    public function unauthenticated_logout_redirects_to_login(): void
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }
}
