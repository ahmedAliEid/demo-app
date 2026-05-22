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

// Health ping endpoint
add_action('rest_api_init', function () {
    register_rest_route('demo/v1', '/ping', [
        'methods'             => 'GET',
        'callback'            => fn() => ['status' => 'ok'],
        'permission_callback' => '__return_true',
    ]);
});

// ── Shortcodes ────────────────────────────────────────────────────────────────

function demo_require_classes() {
    require_once WP_PLUGIN_DIR . '/demo-contacts-api/includes/class-api-client.php';
    require_once WP_PLUGIN_DIR . '/demo-contacts-api/includes/class-auth-handler.php';
    require_once WP_PLUGIN_DIR . '/demo-contacts-api/includes/class-contact-handler.php';
}

// [demo_login_page]
add_shortcode('demo_login_page', function () {
    demo_require_classes();

    if (Demo_Contacts_Auth_Handler::is_authenticated()) {
        wp_redirect(home_url('/contacts'));
        exit;
    }

    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'demo_login')) {
            $error = 'Security check failed. Please try again.';
        } else {
            $username = sanitize_text_field(wp_unslash($_POST['username'] ?? ''));
            $password = $_POST['password'] ?? '';
            if (Demo_Contacts_Auth_Handler::login($username, $password) === true) {
                wp_redirect(home_url('/contacts'));
                exit;
            }
            $error = 'Invalid username or password.';
        }
    }

    ob_start(); ?>
    <div class="demo-login-form">
        <h2><?php echo esc_html(get_bloginfo('name')); ?> — Login</h2>
        <?php if ($error): ?>
            <div class="demo-notice-error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['expired'])): ?>
            <div class="demo-notice-error">Your session has expired. Please log in again.</div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('demo_login'); ?>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="submit">Log In</button>
        </form>
    </div>
    <?php return ob_get_clean();
});

// [demo_contacts_list]
add_shortcode('demo_contacts_list', function () {
    demo_require_classes();
    Demo_Contacts_Auth_Handler::require_auth();

    $handler     = new Demo_Contacts_Contact_Handler();
    $filter_opts = $handler->get_filter_options();
    $cities      = (!is_wp_error($filter_opts) && isset($filter_opts['cities'])) ? $filter_opts['cities'] : [];
    $countries   = (!is_wp_error($filter_opts) && isset($filter_opts['countries'])) ? $filter_opts['countries'] : [];

    $name    = sanitize_text_field(wp_unslash($_GET['name'] ?? ''));
    $city    = sanitize_text_field(wp_unslash($_GET['city'] ?? ''));
    $country = sanitize_text_field(wp_unslash($_GET['country'] ?? ''));
    $page    = max(0, intval($_GET['paged'] ?? 1) - 1);

    $filters = array_filter(compact('name', 'city', 'country'));
    $result  = $handler->list_contacts($filters, $page);

    ob_start(); ?>
    <h1>Contacts</h1>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
        <a href="<?php echo esc_url(home_url('/contact-form')); ?>" style="padding:.5rem 1rem;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;">+ Add Contact</a>
        <form method="get" class="demo-filters">
            <input type="text" name="name" placeholder="Search by name" value="<?php echo esc_attr($name); ?>">
            <select name="city">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?php echo esc_attr($c); ?>" <?php selected($city, $c); ?>><?php echo esc_html($c); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="country">
                <option value="">All Countries</option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?php echo esc_attr($c); ?>" <?php selected($country, $c); ?>><?php echo esc_html($c); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>
    <?php if (is_wp_error($result)): ?>
        <div class="demo-notice-error"><?php echo esc_html($result->get_error_message()); ?></div>
    <?php elseif (empty($result['content'])): ?>
        <div class="demo-empty-state">No contacts yet. <a href="<?php echo esc_url(home_url('/contact-form')); ?>">Add one!</a></div>
    <?php else: ?>
        <table class="demo-contacts-table">
            <thead><tr><th>Name</th><th>City</th><th>Country</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['content'] as $contact): ?>
                <tr>
                    <td><?php echo esc_html($contact['fullName']); ?></td>
                    <td><?php echo esc_html($contact['city'] ?? '—'); ?></td>
                    <td><?php echo esc_html($contact['country'] ?? '—'); ?></td>
                    <td>
                        <a href="<?php echo esc_url(home_url('/contact-detail/?id=' . intval($contact['id']))); ?>">View</a>
                        | <a href="<?php echo esc_url(home_url('/contact-form/?id=' . intval($contact['id']))); ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $total_pages = intval($result['totalPages'] ?? 1);
        $current     = $page + 1;
        if ($total_pages > 1): ?>
        <div class="demo-pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $current): ?>
                    <span class="current"><?php echo esc_html($i); ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg(['paged' => $i, 'name' => $name, 'city' => $city, 'country' => $country])); ?>"><?php echo esc_html($i); ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php return ob_get_clean();
});

// [demo_contact_detail]
add_shortcode('demo_contact_detail', function () {
    demo_require_classes();
    Demo_Contacts_Auth_Handler::require_auth();

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        wp_redirect(home_url('/contacts'));
        exit;
    }

    $handler = new Demo_Contacts_Contact_Handler();
    $contact = $handler->get_contact($id);

    ob_start();
    if (is_wp_error($contact)): ?>
        <div class="demo-notice-error"><?php echo esc_html($contact->get_error_message()); ?></div>
        <p><a href="<?php echo esc_url(home_url('/contacts')); ?>">&larr; Back to contacts</a></p>
    <?php else: ?>
        <h1><?php echo esc_html($contact['fullName']); ?></h1>
        <p>
            <a href="<?php echo esc_url(home_url('/contacts')); ?>">&larr; Back to contacts</a>
            &nbsp;|&nbsp;
            <a href="<?php echo esc_url(home_url('/contact-form/?id=' . $id)); ?>">Edit</a>
        </p>
        <table>
            <tr><th>Street</th><td><?php echo esc_html($contact['streetLine1']); ?><?php if (!empty($contact['streetLine2'])): ?>, <?php echo esc_html($contact['streetLine2']); ?><?php endif; ?></td></tr>
            <tr><th>City</th><td><?php echo esc_html($contact['city']); ?></td></tr>
            <?php if (!empty($contact['stateProvince'])): ?><tr><th>State/Province</th><td><?php echo esc_html($contact['stateProvince']); ?></td></tr><?php endif; ?>
            <?php if (!empty($contact['postalCode'])): ?><tr><th>Postal Code</th><td><?php echo esc_html($contact['postalCode']); ?></td></tr><?php endif; ?>
            <tr><th>Country</th><td><?php echo esc_html($contact['country']); ?></td></tr>
            <?php if (!empty($contact['phone'])): ?><tr><th>Phone</th><td><?php echo esc_html($contact['phone']); ?></td></tr><?php endif; ?>
        </table>
    <?php endif;
    return ob_get_clean();
});

// [demo_contact_form]
add_shortcode('demo_contact_form', function () {
    demo_require_classes();
    Demo_Contacts_Auth_Handler::require_auth();

    $handler      = new Demo_Contacts_Contact_Handler();
    $id           = intval($_GET['id'] ?? 0);
    $is_edit      = $id > 0;
    $contact      = [];
    $errors       = [];
    $nonce_action = $is_edit ? 'demo_edit_contact_' . $id : 'demo_add_contact';

    if ($is_edit) {
        $fetched = $handler->get_contact($id);
        if (!is_wp_error($fetched)) {
            $contact = $fetched;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $nonce_action)) {
            $errors['general'] = 'Security check failed.';
        } else {
            $data = [
                'fullName'      => sanitize_text_field(wp_unslash($_POST['fullName'] ?? '')),
                'streetLine1'   => sanitize_text_field(wp_unslash($_POST['streetLine1'] ?? '')),
                'streetLine2'   => sanitize_text_field(wp_unslash($_POST['streetLine2'] ?? '')),
                'city'          => sanitize_text_field(wp_unslash($_POST['city'] ?? '')),
                'stateProvince' => sanitize_text_field(wp_unslash($_POST['stateProvince'] ?? '')),
                'postalCode'    => sanitize_text_field(wp_unslash($_POST['postalCode'] ?? '')),
                'country'       => sanitize_text_field(wp_unslash($_POST['country'] ?? '')),
                'phone'         => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            ];
            $result = $is_edit
                ? $handler->update_contact($id, $data)
                : $handler->create_contact($data);

            if (!is_wp_error($result)) {
                wp_redirect(home_url('/contacts'));
                exit;
            }

            $api_errors = $result->get_error_data()['data']['errors'] ?? [];
            $errors = !empty($api_errors)
                ? array_map('esc_html', $api_errors)
                : ['general' => esc_html($result->get_error_message())];
            $contact = $data;
        }
    }

    $title = $is_edit ? 'Edit Contact' : 'Add Contact';

    ob_start(); ?>
    <h1><?php echo esc_html($title); ?></h1>
    <p><a href="<?php echo esc_url(home_url('/contacts')); ?>">&larr; Back to contacts</a></p>
    <?php if (!empty($errors['general'])): ?>
        <div class="demo-notice-error"><?php echo esc_html($errors['general']); ?></div>
    <?php endif; ?>
    <form method="post" class="demo-contact-form">
        <?php wp_nonce_field($nonce_action); ?>
        <?php
        $fields = [
            ['fullName',      'Full Name',      true],
            ['streetLine1',   'Street Line 1',  true],
            ['streetLine2',   'Street Line 2',  false],
            ['city',          'City',           true],
            ['stateProvince', 'State/Province', false],
            ['postalCode',    'Postal Code',    false],
            ['country',       'Country',        true],
            ['phone',         'Phone',          false],
        ];
        foreach ($fields as [$fname, $label, $required]):
            $val = esc_attr($contact[$fname] ?? '');
        ?>
        <label for="<?php echo esc_attr($fname); ?>">
            <?php echo esc_html($label); ?>
            <?php if ($required): ?><span style="color:#c00"> *</span><?php endif; ?>
        </label>
        <input type="text" id="<?php echo esc_attr($fname); ?>" name="<?php echo esc_attr($fname); ?>"
               value="<?php echo $val; ?>"<?php if ($required): ?> required<?php endif; ?>>
        <?php if (!empty($errors[$fname])): ?>
            <div class="field-error"><?php echo esc_html($errors[$fname]); ?></div>
        <?php endif; ?>
        <?php endforeach; ?>
        <button type="submit"><?php echo $is_edit ? 'Save Changes' : 'Add Contact'; ?></button>
    </form>
    <?php return ob_get_clean();
});
