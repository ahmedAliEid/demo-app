<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\ApiClient;
use App\Services\UnauthenticatedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('token')) {
            return redirect()->route('contacts.index');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        try {
            $result = $this->apiClient->login(
                $request->input('username'),
                $request->input('password')
            );
        } catch (UnauthenticatedException) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['credentials' => 'Invalid username or password']);
        }

        $request->session()->put('token', $result['token']);
        $request->session()->put('role', $result['role'] ?? 'USER');
        $request->session()->regenerate();

        return redirect()->intended(route('contacts.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->session()->get('token');

        if ($token) {
            $this->apiClient->logout($token);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
