<?php

defined('ABSPATH') || exit;

class Demo_Contacts_Auth_Handler {

    public static function login(string $username, string $password): bool|\WP_Error {
        $client   = new Demo_Contacts_API_Client();
        $response = $client->post('/api/v1/auth/login', compact('username', 'password'), false);

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['token'])) {
            return new \WP_Error('login_failed', 'Invalid response from authentication server.');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['demo_jwt']  = sanitize_text_field($response['token']);
        $_SESSION['demo_role'] = sanitize_text_field($response['role'] ?? 'user');

        return true;
    }

    public static function logout(): void {
        $client = new Demo_Contacts_API_Client();
        $client->post('/api/v1/auth/logout');
        self::clear_session();
    }

    public static function get_token(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['demo_jwt'] ?? null;
    }

    public static function get_role(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['demo_role'] ?? 'user';
    }

    public static function is_authenticated(): bool {
        return self::get_token() !== null;
    }

    public static function require_auth(): void {
        if (!self::is_authenticated()) {
            wp_redirect(home_url('/login'));
            exit;
        }
    }

    public static function clear_session(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['demo_jwt'], $_SESSION['demo_role']);
    }
}
