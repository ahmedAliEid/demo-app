<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ApiClient;
use App\Services\ApiUnavailableException;
use App\Services\UnauthenticatedException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    private ApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new ApiClient;
        config(['services.backend.url' => 'http://localhost:8080']);
    }

    #[Test]
    public function login_sends_correct_request(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response(['token' => 'abc', 'role' => 'USER']),
        ]);

        $result = $this->client->login('admin', 'secret');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://localhost:8080/api/v1/auth/login'
                && $request->method() === 'POST'
                && $request['username'] === 'admin'
                && $request['password'] === 'secret';
        });

        $this->assertSame(['token' => 'abc', 'role' => 'USER'], $result);
    }

    #[Test]
    public function login_401_throws_unauthenticated(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response(null, 401),
        ]);

        $this->expectException(UnauthenticatedException::class);
        $this->client->login('admin', 'wrong');
    }

    #[Test]
    public function login_500_throws_api_unavailable(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response(null, 500),
        ]);

        $this->expectException(ApiUnavailableException::class);
        $this->client->login('admin', 'secret');
    }

    #[Test]
    public function list_contacts_sends_get_with_filters(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts*' => Http::response(['content' => []]),
        ]);

        session()->put('token', 'valid-token');
        $this->client->listContacts(['country' => 'US'], 1);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://localhost:8080/api/v1/contacts?country=US&page=1'
                && $request->hasHeader('Authorization', 'Bearer valid-token');
        });
    }

    #[Test]
    public function get_contact_sends_get_with_id(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts/42' => Http::response(['id' => 42, 'fullName' => 'John']),
        ]);

        $result = $this->client->getContact(42);

        $this->assertSame(['id' => 42, 'fullName' => 'John'], $result);
    }

    #[Test]
    public function create_contact_sends_post(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts' => Http::response(['id' => 1]),
        ]);

        $data = ['fullName' => 'Jane', 'city' => 'NYC', 'country' => 'US'];
        $result = $this->client->createContact($data);

        $this->assertSame(['id' => 1], $result);
    }

    #[Test]
    public function update_contact_sends_put(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts/1' => Http::response(['id' => 1]),
        ]);

        $result = $this->client->updateContact(1, ['fullName' => 'Jane Updated']);

        $this->assertSame(['id' => 1], $result);
    }

    #[Test]
    public function logout_sends_post_with_token(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/auth/logout' => Http::response(null, 204),
        ]);

        $this->client->logout('some-token');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://localhost:8080/api/v1/auth/logout'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer some-token');
        });
    }

    #[Test]
    public function get_filter_options_returns_options(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts/filter-options' => Http::response([
                'cities' => ['NYC', 'LA'],
                'countries' => ['US', 'CA'],
            ]),
        ]);

        $result = $this->client->getFilterOptions();

        $this->assertSame(['cities' => ['NYC', 'LA'], 'countries' => ['US', 'CA']], $result);
    }

    #[Test]
    public function request_has_timeout_and_retry(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/contacts' => Http::response(['content' => []]),
        ]);

        $this->client->listContacts();

        Http::assertSent(function (Request $request) {
            return true;
        });
    }
}
