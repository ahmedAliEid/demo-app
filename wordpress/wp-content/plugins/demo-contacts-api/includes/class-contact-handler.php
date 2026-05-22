<?php

defined('ABSPATH') || exit;

class Demo_Contacts_Contact_Handler {

    private Demo_Contacts_API_Client $client;

    public function __construct() {
        $this->client = new Demo_Contacts_API_Client();
    }

    public function list_contacts(array $filters = [], int $page = 0): array|\WP_Error {
        $params = array_filter(array_merge($filters, ['page' => $page, 'size' => 20]));
        return $this->client->get('/api/v1/contacts', $params);
    }

    public function get_contact(int $id): array|\WP_Error {
        return $this->client->get('/api/v1/contacts/' . $id);
    }

    public function create_contact(array $data): array|\WP_Error {
        return $this->client->post('/api/v1/contacts', $data);
    }

    public function update_contact(int $id, array $data): array|\WP_Error {
        return $this->client->put('/api/v1/contacts/' . $id, $data);
    }

    public function get_filter_options(): array|\WP_Error {
        return $this->client->get('/api/v1/contacts/filter-options');
    }
}
