<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ApiUnavailableException extends RuntimeException {}

class UnauthenticatedException extends RuntimeException {}

class ApiClient
{
    private const TIMEOUT = 5;
    private const RETRY_TIMES = 3;
    private const RETRY_DELAY_MS = 500;

    private function baseRequest(): PendingRequest
    {
        $request = Http::timeout(self::TIMEOUT)
            ->retry(self::RETRY_TIMES, self::RETRY_DELAY_MS, function (RequestException $e, $request) {
                $response = $e->response;
                if ($response && $response->status() < 500) {
                    return false;
                }
                return true;
            });

        $token = session('token');
        if ($token) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.backend.url'), '/');
    }

    private function handleResponse(\Illuminate\Http\Client\Response $response, string $context): array
    {
        if ($response->status() === 401) {
            session()->flush();
            throw new UnauthenticatedException('Session expired or invalid');
        }

        if ($response->status() === 409) {
            return [
                'conflict' => true,
                'message' => $response->json('message', 'Conflict'),
            ];
        }

        if ($response->status() >= 500) {
            Log::error('Backend API unavailable', [
                'context' => $context,
                'status' => $response->status(),
            ]);
            throw new ApiUnavailableException('Backend service is currently unavailable');
        }

        return $response->json() ?? [];
    }

    public function login(string $username, string $password): array
    {
        $response = $this->baseRequest()->post($this->baseUrl().'/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        return $this->handleResponse($response, 'login');
    }

    public function logout(string $token): void
    {
        $this->baseRequest()->withToken($token)
            ->post($this->baseUrl().'/api/v1/auth/logout');
    }

    public function listContacts(array $filters = [], int $page = 0): array
    {
        $params = array_merge($filters, ['page' => $page]);
        $response = $this->baseRequest()->get($this->baseUrl().'/api/v1/contacts', $params);

        return $this->handleResponse($response, 'listContacts');
    }

    public function getContact(int $id): array
    {
        $response = $this->baseRequest()->get($this->baseUrl()."/api/v1/contacts/{$id}");

        return $this->handleResponse($response, 'getContact');
    }

    public function createContact(array $data): array
    {
        $response = $this->baseRequest()->post($this->baseUrl().'/api/v1/contacts', $data);

        return $this->handleResponse($response, 'createContact');
    }

    public function updateContact(int $id, array $data): array
    {
        $response = $this->baseRequest()->put($this->baseUrl()."/api/v1/contacts/{$id}", $data);

        return $this->handleResponse($response, 'updateContact');
    }

    public function getFilterOptions(): array
    {
        $response = $this->baseRequest()->get($this->baseUrl().'/api/v1/contacts/filter-options');

        return $this->handleResponse($response, 'getFilterOptions');
    }
}
