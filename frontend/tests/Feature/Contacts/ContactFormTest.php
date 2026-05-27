<?php

declare(strict_types=1);

namespace Tests\Feature\Contacts;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    #[Test]
    public function create_contact_with_missing_fields_returns_validation_error(): void
    {
        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->post('/contacts', []);

        $response->assertSessionHasErrors(['fullName', 'streetLine1', 'city', 'country']);
    }

    #[Test]
    public function valid_create_contact_redirects(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts' => Http::response(['id' => 1]),
        ]);

        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->post('/contacts', [
            'fullName' => 'Charlie',
            'streetLine1' => '123 Main St',
            'city' => 'Boston',
            'country' => 'US',
        ]);

        $response->assertRedirect('/contacts');
    }

    #[Test]
    public function create_page_returns_200(): void
    {
        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->get('/contacts/create');

        $response->assertStatus(200);
    }

    #[Test]
    public function edit_page_returns_200(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts/1' => Http::response([
                'id' => 1,
                'fullName' => 'Charlie',
            ]),
        ]);

        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->get('/contacts/1/edit');

        $response->assertStatus(200);
    }

    #[Test]
    public function update_contact_redirects_to_detail(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts/1' => Http::response(['id' => 1]),
        ]);

        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->put('/contacts/1', [
            'fullName' => 'Charlie Updated',
            'streetLine1' => '123 Main St',
            'city' => 'Boston',
            'country' => 'US',
        ]);

        $response->assertRedirect('/contacts/1');
    }
}
