<?php

declare(strict_types=1);

namespace Tests\Feature\Contacts;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDetailTest extends TestCase
{
    #[Test]
    public function authenticated_user_can_view_contact_detail(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts/42' => Http::response([
                'id' => 42,
                'fullName' => 'Bob Smith',
                'email' => 'bob@example.com',
                'city' => 'Chicago',
            ]),
        ]);

        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->get('/contacts/42');

        $response->assertStatus(200);
        $response->assertSee('Bob Smith');
    }

    #[Test]
    public function unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/contacts/42');

        $response->assertRedirect('/login');
    }
}
