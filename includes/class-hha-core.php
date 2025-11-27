<?php
/**
 * Core controller - Main plugin controller (singleton).
 *
 * Initializes all plugin components and provides global access.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Core {

    /**
     * Singleton instance.
     *
     * @var HHA_Core
     */
    private static $instance = null;

    /**
     * Component instances.
     *
     * @var array
     */
    private $components = array();

    /**
     * Get singleton instance.
     *
     * @return HHA_Core
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor - use instance() instead.
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Initialize plugin components.
     */
    private function init_components() {
        // Core components
        $this->components['hotels']       = new HHA_Hotels();
        $this->components['integrations'] = new HHA_Integrations();
        $this->components['modules']      = new HHA_Modules();
        $this->components['pwa']          = new HHA_PWA();
        $this->components['ajax']         = new HHA_AJAX();
        $this->components['auth']         = new HHA_Auth();

        // Admin components (only in admin)
        if (is_admin()) {
            require_once HHA_PLUGIN_DIR . 'admin/class-hha-admin.php';
            $this->components['admin'] = new HHA_Admin();
        }
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Register shortcodes
        add_shortcode('hotel_hub_app', array($this, 'app_shortcode'));
        add_shortcode('hotel_hub_login', array($this, 'login_shortcode'));

        // Add body classes
        add_filter('body_class', array($this, 'add_body_classes'));

        // PWA hooks
        add_action('wp_head', array($this->components['pwa'], 'add_manifest_link'));
        add_action('wp_head', array($this->components['pwa'], 'add_pwa_meta_tags'));
        add_action('wp_footer', array($this->components['pwa'], 'register_service_worker'));
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_frontend_assets() {
        // Only on app page
        if (!$this->is_app_page()) {
            return;
        }

        // Get theme settings
        $theme_mode = get_option('hha_theme_mode', 'light');
        $theme_color = get_option('hha_theme_primary_color', '#2196f3');

        // Enqueue styles
        wp_enqueue_style(
            'hha-standalone',
            HHA_PLUGIN_URL . 'assets/css/standalone.css',
            array(),
            HHA_VERSION
        );

        wp_enqueue_style(
            'hha-themes',
            HHA_PLUGIN_URL . 'assets/css/themes.css',
            array('hha-standalone'),
            HHA_VERSION
        );

        // Add theme variables
        $custom_css = ":root { --hha-primary-color: {$theme_color}; }";
        wp_add_inline_style('hha-themes', $custom_css);

        // Enqueue scripts
        wp_enqueue_script('jquery');

        wp_enqueue_script(
            'hha-app',
            HHA_PLUGIN_URL . 'assets/js/app.js',
            array('jquery'),
            HHA_VERSION,
            true
        );

        // Localize script
        wp_localize_script('hha-app', 'hhaData', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('hha-app'),
            'themeMode'     => $theme_mode,
            'currentUserId' => get_current_user_id(),
            'homeUrl'       => home_url(),
        ));
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Only on hotel hub admin pages
        if (strpos($hook, 'hotel-hub') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');

        wp_enqueue_style(
            'hha-admin',
            HHA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HHA_VERSION
        );

        wp_enqueue_media();

        wp_enqueue_script('wp-color-picker');

        wp_enqueue_script(
            'hha-admin',
            HHA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            HHA_VERSION,
            true
        );

        wp_localize_script('hha-admin', 'hhaAdminData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('hha-admin'),
        ));
    }

    /**
     * App shortcode - renders standalone app.
     *
     * @return string App HTML.
     */
    public function app_shortcode() {
        // Require authentication
        $this->components['auth']->require_authentication();

        // Load standalone template
        ob_start();
        include HHA_PLUGIN_DIR . 'templates/standalone.php';
        return ob_get_clean();
    }

    /**
     * Login shortcode - renders custom login form.
     *
     * @return string Login form HTML.
     */
    public function login_shortcode() {
        // If already logged in, redirect to app
        if (is_user_logged_in() && $this->components['auth']->user_has_access()) {
            wp_safe_redirect($this->components['auth']->get_app_url());
            exit;
        }

        // Load login template
        ob_start();
        include HHA_PLUGIN_DIR . 'templates/login.php';
        return ob_get_clean();
    }

    /**
     * Add body classes.
     *
     * @param array $classes Existing body classes.
     * @return array Modified body classes.
     */
    public function add_body_classes($classes) {
        if ($this->is_app_page()) {
            $classes[] = 'hha-standalone';

            $theme_mode = get_option('hha_theme_mode', 'light');
            $classes[] = 'hha-theme-' . $theme_mode;
        }

        return $classes;
    }

    /**
     * Check if current page is the app page.
     *
     * @return bool True if app page, false otherwise.
     */
    private function is_app_page() {
        $app_page_id = get_option('hha_app_page_id');

        return $app_page_id && is_page($app_page_id);
    }

    /**
     * Magic getter for component access.
     *
     * @param string $name Component name.
     * @return object|null Component instance or null.
     */
    public function __get($name) {
        if (isset($this->components[$name])) {
            return $this->components[$name];
        }

        return null;
    }

    /**
     * Get plugin version.
     *
     * @return string Version number.
     */
    public function get_version() {
        return HHA_VERSION;
    }

    /**
     * Get plugin directory path.
     *
     * @return string Plugin directory path.
     */
    public function get_plugin_dir() {
        return HHA_PLUGIN_DIR;
    }

    /**
     * Get plugin directory URL.
     *
     * @return string Plugin directory URL.
     */
    public function get_plugin_url() {
        return HHA_PLUGIN_URL;
    }
}
