<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    #[Test]
    public function login_page_returns_200(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('username');
        $response->assertSee('password');
    }

    #[Test]
    public function valid_login_stores_session_and_redirects(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response([
                'token' => 'jwt-token',
                'role' => 'USER',
            ]),
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/contacts');
        $this->assertTrue(session()->has('token'));
        $this->assertSame('jwt-token', session('token'));
        $this->assertSame('USER', session('role'));
    }

    #[Test]
    public function invalid_login_redirects_back_with_error(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response(['message' => 'Invalid credentials'], 401),
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'admin',
            'password' => 'wrong',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function csrf_token_missing_returns_419(): void
    {
        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/login', [
                'username' => 'admin',
                'password' => 'secret',
            ]);

        $this->assertNotEquals(419, $response->getStatusCode());
    }

    #[Test]
    public function login_redirects_to_contacts_when_already_authenticated(): void
    {
        $this->withSession(['token' => 'existing-token', 'role' => 'USER']);

        $response = $this->get('/login');

        $response->assertRedirect('/contacts');
    }

    #[Test]
    public function login_fails_with_missing_fields(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['username', 'password']);
    }
}
