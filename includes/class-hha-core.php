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
        $this->components['reports']      = new HHA_Reports();
        $this->components['permissions']  = new HHA_Permissions();

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
        // Template override for standalone app
        add_filter('template_include', array($this, 'override_app_template'), 99);

        // Redirect all frontend to app (if enabled)
        add_action('template_redirect', array($this, 'redirect_to_app'), 5);

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Register shortcodes
        add_shortcode('hotel_hub_app', array($this, 'app_shortcode'));
        add_shortcode('hotel_hub_login', array($this, 'login_shortcode'));

        // Add body classes
        add_filter('body_class', array($this, 'add_body_classes'));

        // PWA hooks
        add_action('wp_head', array($this->components['pwa'], 'add_manifest_link'), 1);
        add_action('wp_head', array($this->components['pwa'], 'add_pwa_meta_tags'), 1);
        add_action('wp_footer', array($this->components['pwa'], 'register_service_worker'));

        // Remove WordPress default site icon and manifest on app page
        add_action('wp_head', array($this, 'remove_wp_default_pwa'), 0);
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

        // Enqueue dashicons for UI elements
        wp_enqueue_style('dashicons');

        // Enqueue Material Symbols for module icons
        wp_enqueue_style(
            'material-symbols',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200',
            array(),
            null
        );

        // Enqueue styles
        wp_enqueue_style(
            'hha-standalone',
            HHA_PLUGIN_URL . 'assets/css/standalone.css',
            array('dashicons', 'material-symbols'),
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
     * Override template for app page to use standalone template.
     *
     * @param string $template Current template path.
     * @return string Modified template path.
     */
    public function override_app_template($template) {
        if ($this->is_app_page()) {
            // Require authentication
            $this->components['auth']->require_authentication();

            // Use standalone template instead of theme template
            return HHA_PLUGIN_DIR . 'templates/standalone.php';
        }

        return $template;
    }

    /**
     * Remove WordPress default PWA elements on app page.
     */
    public function remove_wp_default_pwa() {
        if (!$this->is_app_page()) {
            return;
        }

        // Remove site icon
        remove_action('wp_head', 'wp_site_icon', 99);

        // Remove WordPress default manifest and icon links
        remove_action('wp_head', 'wp_manifest_link', 10);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
    }

    /**
     * Redirect all frontend pages to the app (if enabled).
     */
    public function redirect_to_app() {
        // Check if frontend-only mode is enabled
        if (!get_option('hha_frontend_only_mode', false)) {
            return;
        }

        // Don't redirect unauthenticated users - let them access login naturally
        if (!is_user_logged_in()) {
            return;
        }

        // Don't redirect if already on app page
        if ($this->is_app_page()) {
            return;
        }

        // Don't redirect custom login page
        $login_page_id = get_option('hha_login_page_id');
        if ($login_page_id) {
            // Check if current page is the login page
            if (is_page($login_page_id)) {
                return;
            }

            // Also check by page slug in REQUEST_URI
            $login_page = get_post($login_page_id);
            if ($login_page && isset($_SERVER['REQUEST_URI'])) {
                $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $request_path = trim($request_path, '/');
                if ($request_path === $login_page->post_name || $request_path === 'login') {
                    return;
                }
            }
        }

        // Don't redirect admin pages
        if (is_admin()) {
            return;
        }

        // Don't redirect login/logout/register
        if (isset($GLOBALS['pagenow']) && in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-signup.php'))) {
            return;
        }

        // Check for login page in REQUEST_URI as additional safety
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
            return;
        }

        // Don't redirect WordPress registration and password reset
        if (isset($_GET['action']) && in_array($_GET['action'], array('register', 'lostpassword', 'rp', 'resetpass'))) {
            return;
        }

        // Don't redirect if processing login POST
        if (isset($_POST['log']) && isset($_POST['pwd'])) {
            return;
        }

        // Don't redirect REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        // Don't redirect AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Don't redirect AJAX requests (wp-admin/admin-ajax.php)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false) {
            return;
        }

        // Don't redirect feeds
        if (is_feed()) {
            return;
        }

        // Don't redirect PWA assets (manifest, service worker, icons)
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            if (strpos($request_uri, 'hotel-hub-manifest.json') !== false ||
                strpos($request_uri, 'hotel-hub-sw.js') !== false ||
                strpos($request_uri, 'assets/icons/') !== false) {
                return;
            }
        }

        // Don't redirect if user is logging out
        if (isset($_GET['hha_logout'])) {
            return;
        }

        // Get app URL
        $app_url = $this->components['auth']->get_app_url();

        // Redirect to app
        wp_safe_redirect($app_url);
        exit;
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
