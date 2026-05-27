<?php

declare(strict_types=1);

namespace Tests\Feature\Contacts;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactListTest extends TestCase
{
    #[Test]
    public function authenticated_user_can_view_contacts(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts*' => Http::response([
                'content' => [
                    ['id' => 1, 'fullName' => 'Alice', 'city' => 'NYC'],
                ],
            ]),
            'http://localhost:8080/api/v1/contacts/filter-options' => Http::response([
                'cities' => ['NYC', 'LA'],
                'countries' => ['US'],
            ]),
        ]);

        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $response = $this->get('/contacts');

        $response->assertStatus(200);
        $response->assertSee('Alice');
    }

    #[Test]
    public function unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/contacts');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function filter_params_forwarded_to_api(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts*' => Http::response(['content' => []]),
            'http://localhost:8080/api/v1/contacts/filter-options' => Http::response([
                'cities' => [], 'countries' => [],
            ]),
        ]);

        $this->withSession(['token' => 'valid-token', 'role' => 'USER']);

        $this->get('/contacts?country=US&city=NYC');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), 'country=US')
                && str_contains($request->url(), 'city=NYC');
        });
    }
}
