<?php

defined('ABSPATH') || exit;

// Start PHP session early
add_action('init', function () {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}, 1);

// Enqueue parent theme stylesheet
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'contacts-child-parent',
        get_template_directory_uri() . '/style.css'
    );
});

// Health ping endpoint (constitution principle VI)
add_action('rest_api_init', function () {
    register_rest_route('demo/v1', '/ping', [
        'methods'             => 'GET',
        'callback'            => fn() => ['status' => 'ok'],
        'permission_callback' => '__return_true',
    ]);
});
