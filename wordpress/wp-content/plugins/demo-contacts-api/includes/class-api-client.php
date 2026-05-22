<?php

defined('ABSPATH') || exit;

class Demo_Contacts_API_Client {

    private string $base_url;

    public function __construct() {
        $this->base_url = rtrim(DEMO_CONTACTS_API_URL, '/');
    }

    private function request(string $method, string $path, array $body = [], bool $auth = true): array|\WP_Error {
        $args = [
            'method'  => strtoupper($method),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15,
        ];

        if ($auth) {
            $token = Demo_Contacts_Auth_Handler::get_token();
            if ($token) {
                $args['headers']['Authorization'] = 'Bearer ' . $token;
            }
        }

        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($this->base_url . $path, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true) ?? [];

        if ($code === 401) {
            Demo_Contacts_Auth_Handler::clear_session();
            return new \WP_Error('unauthorized', 'Session expired. Please log in again.');
        }

        if ($code >= 400) {
            $detail = $data['detail'] ?? 'An error occurred';
            return new \WP_Error('api_error_' . $code, esc_html($detail), ['status' => $code, 'data' => $data]);
        }

        return $data;
    }

    public function post(string $path, array $body = [], bool $auth = true): array|\WP_Error {
        return $this->request('POST', $path, $body, $auth);
    }

    public function get(string $path, array $params = [], bool $auth = true): array|\WP_Error {
        $url = $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url, [], $auth);
    }

    public function put(string $path, array $body = []): array|\WP_Error {
        return $this->request('PUT', $path, $body);
    }
}
