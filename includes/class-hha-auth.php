<?php
/**
 * Authentication - Custom login and logout handling.
 *
 * Provides app-like login screen and secure authentication flow.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Auth {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', array($this, 'handle_logout'));
        add_action('login_form', array($this, 'add_login_styles'));
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
    }

    /**
     * Handle custom logout.
     */
    public function handle_logout() {
        // Check if logout requested
        if (!isset($_GET['hha_logout'])) {
            return;
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'hha-logout')) {
            wp_die('Invalid logout request');
        }

        // Destroy session
        if (session_id()) {
            session_destroy();
        }

        // Log out user
        wp_logout();

        // Redirect to login page
        $login_url = $this->get_login_url();
        wp_safe_redirect($login_url);
        exit;
    }

    /**
     * Get login page URL.
     *
     * @return string Login URL.
     */
    public function get_login_url() {
        $login_page_id = get_option('hha_login_page_id');

        if ($login_page_id && get_post($login_page_id)) {
            return get_permalink($login_page_id);
        }

        // Fall back to WordPress login
        return wp_login_url();
    }

    /**
     * Get app page URL.
     *
     * @return string App URL.
     */
    public function get_app_url() {
        $app_page_id = get_option('hha_app_page_id');

        if ($app_page_id && get_post($app_page_id)) {
            return get_permalink($app_page_id);
        }

        return home_url();
    }

    /**
     * Add custom styles to login page.
     */
    public function add_login_styles() {
        $theme_color = get_option('hha_theme_primary_color', '#2196f3');
        ?>
        <style>
            body.login {
                background: #f5f5f5;
            }

            .login h1 a {
                background-image: url('<?php echo esc_url(HHA_PLUGIN_URL . 'assets/icons/icon-192x192.png'); ?>');
                background-size: contain;
                width: 120px;
                height: 120px;
            }

            .login form {
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .login #wp-submit {
                background: <?php echo esc_attr($theme_color); ?>;
                border: none;
                border-radius: 6px;
                padding: 12px;
                font-size: 16px;
                font-weight: 600;
                width: 100%;
                transition: opacity 0.2s;
            }

            .login #wp-submit:hover {
                opacity: 0.9;
            }

            .login #wp-submit:active {
                opacity: 0.8;
            }

            .login input[type="text"],
            .login input[type="password"] {
                border-radius: 6px;
                padding: 12px;
                font-size: 16px;
            }

            .login label {
                font-size: 14px;
                font-weight: 500;
                color: #333;
            }
        </style>
        <?php
    }

    /**
     * Redirect after login.
     *
     * @param string  $redirect_to           URL to redirect to.
     * @param string  $requested_redirect_to Requested redirect URL.
     * @param WP_User $user                  User object.
     * @return string Redirect URL.
     */
    public function login_redirect($redirect_to, $requested_redirect_to, $user) {
        // If redirect requested, use it
        if (!empty($requested_redirect_to)) {
            return $requested_redirect_to;
        }

        // Check if user has hotel hub permissions
        if (isset($user->ID)) {
            $user_modules = hha()->modules->get_user_modules($user->ID);

            // If user has modules, redirect to app
            if (!empty($user_modules)) {
                return $this->get_app_url();
            }
        }

        // Default redirect
        return $redirect_to;
    }

    /**
     * Create custom login page.
     *
     * @return int|false Page ID or false on failure.
     */
    public function create_login_page() {
        $login_page_id = get_option('hha_login_page_id');

        // Check if page already exists
        if ($login_page_id && get_post($login_page_id)) {
            return $login_page_id;
        }

        // Create new page
        $page_data = array(
            'post_title'    => 'Hotel Hub Login',
            'post_content'  => '[hotel_hub_login]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
        );

        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {
            update_option('hha_login_page_id', $page_id);
            return $page_id;
        }

        return false;
    }

    /**
     * Check if user is logged in and has hotel hub access.
     *
     * @param int|null $user_id User ID (defaults to current user).
     * @return bool True if has access, false otherwise.
     */
    public function user_has_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Check if user has any modules
        $user_modules = hha()->modules->get_user_modules($user_id);

        return !empty($user_modules);
    }

    /**
     * Require authentication for app access.
     *
     * @return void
     */
    public function require_authentication() {
        if (!is_user_logged_in()) {
            $login_url = $this->get_login_url();
            wp_safe_redirect($login_url);
            exit;
        }

        // Allow logged-in users through even if they have no modules
        // The app will show a placeholder message for users without permissions
    }

    /**
     * Get current hotel from session.
     *
     * @return int|null Hotel ID or null if not set.
     */
    public function get_current_hotel_id() {
        if (!session_id()) {
            session_start();
        }

        return isset($_SESSION['hha_current_hotel_id']) ? absint($_SESSION['hha_current_hotel_id']) : null;
    }

    /**
     * Set current hotel in session.
     *
     * @param int $hotel_id Hotel ID.
     * @return void
     */
    public function set_current_hotel_id($hotel_id) {
        if (!session_id()) {
            session_start();
        }

        $_SESSION['hha_current_hotel_id'] = absint($hotel_id);
    }

    /**
     * Clear current hotel from session.
     *
     * @return void
     */
    public function clear_current_hotel() {
        if (!session_id()) {
            session_start();
        }

        unset($_SESSION['hha_current_hotel_id']);
    }
}
