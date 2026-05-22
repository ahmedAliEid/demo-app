<?php
/**
 * Plugin Name: Demo Contacts API
 * Description: Integrates WordPress with the Spring Boot Contacts REST API.
 * Version:     1.0.0
 * Author:      Demo App Team
 */

defined('ABSPATH') || exit;

define('DEMO_CONTACTS_API_URL', get_option('demo_contacts_api_url', 'http://localhost:8080'));
define('DEMO_CONTACTS_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once DEMO_CONTACTS_PLUGIN_DIR . 'includes/class-api-client.php';
require_once DEMO_CONTACTS_PLUGIN_DIR . 'includes/class-auth-handler.php';
require_once DEMO_CONTACTS_PLUGIN_DIR . 'includes/class-contact-handler.php';

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'demo-contacts',
        plugins_url('assets/css/contacts.css', __FILE__),
        [],
        '1.0.0'
    );
    wp_enqueue_script(
        'demo-contacts',
        plugins_url('assets/js/contacts.js', __FILE__),
        [],
        '1.0.0',
        true
    );
});

register_activation_hook(__FILE__, function () {
    add_option('demo_contacts_api_url', 'http://localhost:8080');
});
