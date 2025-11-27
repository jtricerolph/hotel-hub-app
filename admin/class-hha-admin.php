<?php
/**
 * Admin controller - WordPress admin interface.
 *
 * Handles admin menus, pages, and hotel/integration management.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Hotel Hub',
            'Hotel Hub',
            'manage_options',
            'hotel-hub',
            array($this, 'render_hotels_page'),
            'dashicons-building',
            30
        );

        // Hotels submenu (same as main)
        add_submenu_page(
            'hotel-hub',
            'Hotels',
            'Hotels',
            'manage_options',
            'hotel-hub',
            array($this, 'render_hotels_page')
        );

        // Add/Edit Hotel
        add_submenu_page(
            'hotel-hub',
            'Add Hotel',
            'Add New',
            'manage_options',
            'hotel-hub-edit',
            array($this, 'render_hotel_edit_page')
        );

        // Settings
        add_submenu_page(
            'hotel-hub',
            'Settings',
            'Settings',
            'manage_options',
            'hotel-hub-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render hotels list page.
     */
    public function render_hotels_page() {
        $hotels = hha()->hotels->get_all();
        include HHA_PLUGIN_DIR . 'admin/views/hotels-list.php';
    }

    /**
     * Render hotel edit page.
     */
    public function render_hotel_edit_page() {
        $hotel_id = isset($_GET['hotel_id']) ? absint($_GET['hotel_id']) : 0;
        $hotel = $hotel_id ? hha()->hotels->get($hotel_id) : null;

        // Get integrations
        $newbook_integration = null;
        $resos_integration = null;

        if ($hotel_id) {
            $newbook_integration = hha()->integrations->get($hotel_id, 'newbook');
            $resos_integration = hha()->integrations->get($hotel_id, 'resos');
        }

        // Get workforce locations if available
        $locations = array();
        if (function_exists('wfa_get_all_locations')) {
            $locations = wfa_get_all_locations();
        }

        include HHA_PLUGIN_DIR . 'admin/views/hotel-edit.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        $theme_mode = get_option('hha_theme_mode', 'light');
        $theme_color = get_option('hha_theme_primary_color', '#2196f3');

        include HHA_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Handle form submissions.
     */
    public function handle_form_submissions() {
        // Handle hotel save
        if (isset($_POST['hha_save_hotel'])) {
            $this->handle_save_hotel();
        }

        // Handle integration save
        if (isset($_POST['hha_save_integration'])) {
            $this->handle_save_integration();
        }

        // Handle settings save
        if (isset($_POST['hha_save_settings'])) {
            $this->handle_save_settings();
        }

        // Handle hotel delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['hotel_id'])) {
            $this->handle_delete_hotel();
        }
    }

    /**
     * Handle save hotel form.
     */
    private function handle_save_hotel() {
        // Verify nonce
        if (!isset($_POST['hha_hotel_nonce']) || !wp_verify_nonce($_POST['hha_hotel_nonce'], 'hha_save_hotel')) {
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;

        $hotel_data = array(
            'location_id' => isset($_POST['location_id']) ? absint($_POST['location_id']) : null,
            'name'        => sanitize_text_field($_POST['name']),
            'address'     => sanitize_textarea_field($_POST['address']),
            'phone'       => sanitize_text_field($_POST['phone']),
            'website'     => esc_url_raw($_POST['website']),
            'logo_id'     => isset($_POST['logo_id']) ? absint($_POST['logo_id']) : null,
            'icon_id'     => isset($_POST['icon_id']) ? absint($_POST['icon_id']) : null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        );

        // Process uploaded images
        if ($hotel_data['logo_id']) {
            hha()->hotels->process_logo($hotel_data['logo_id']);
        }

        if ($hotel_data['icon_id']) {
            hha()->hotels->process_icon($hotel_data['icon_id']);
        }

        if ($hotel_id) {
            // Update existing hotel
            $result = hha()->hotels->update($hotel_id, $hotel_data);

            if ($result) {
                $this->add_notice('Hotel updated successfully', 'success');
            } else {
                $this->add_notice('Failed to update hotel', 'error');
            }
        } else {
            // Create new hotel
            $new_hotel_id = hha()->hotels->create($hotel_data);

            if ($new_hotel_id) {
                $this->add_notice('Hotel created successfully', 'success');

                // Redirect to edit page
                wp_safe_redirect(admin_url('admin.php?page=hotel-hub-edit&hotel_id=' . $new_hotel_id));
                exit;
            } else {
                $this->add_notice('Failed to create hotel', 'error');
            }
        }
    }

    /**
     * Handle save integration form.
     */
    private function handle_save_integration() {
        // Verify nonce
        if (!isset($_POST['hha_integration_nonce']) || !wp_verify_nonce($_POST['hha_integration_nonce'], 'hha_save_integration')) {
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        $hotel_id = absint($_POST['hotel_id']);
        $integration_type = sanitize_text_field($_POST['integration_type']);

        $settings = array();

        if ($integration_type === 'newbook') {
            $settings = array(
                'username' => sanitize_text_field($_POST['newbook_username']),
                'password' => $_POST['newbook_password'],
                'api_key'  => sanitize_text_field($_POST['newbook_api_key']),
                'region'   => sanitize_text_field($_POST['newbook_region']),
            );
        } elseif ($integration_type === 'resos') {
            $settings = array(
                'api_key' => sanitize_text_field($_POST['resos_api_key']),
            );
        }

        $is_active = isset($_POST['is_active']) ? true : false;

        $result = hha()->integrations->save($hotel_id, $integration_type, $settings, $is_active);

        if ($result) {
            $this->add_notice('Integration saved successfully', 'success');
        } else {
            $this->add_notice('Failed to save integration', 'error');
        }
    }

    /**
     * Handle save settings form.
     */
    private function handle_save_settings() {
        // Verify nonce
        if (!isset($_POST['hha_settings_nonce']) || !wp_verify_nonce($_POST['hha_settings_nonce'], 'hha_save_settings')) {
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        $theme_mode = sanitize_text_field($_POST['theme_mode']);
        $theme_color = sanitize_hex_color($_POST['theme_primary_color']);

        update_option('hha_theme_mode', $theme_mode);
        update_option('hha_theme_primary_color', $theme_color);

        $this->add_notice('Settings saved successfully', 'success');
    }

    /**
     * Handle delete hotel.
     */
    private function handle_delete_hotel() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete-hotel')) {
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        $hotel_id = absint($_GET['hotel_id']);

        $result = hha()->hotels->delete($hotel_id);

        if ($result) {
            $this->add_notice('Hotel deleted successfully', 'success');
        } else {
            $this->add_notice('Failed to delete hotel', 'error');
        }

        wp_safe_redirect(admin_url('admin.php?page=hotel-hub'));
        exit;
    }

    /**
     * Add admin notice.
     *
     * @param string $message Notice message.
     * @param string $type    Notice type (success, error, warning, info).
     */
    private function add_notice($message, $type = 'info') {
        $notices = get_transient('hha_admin_notices');

        if (!$notices) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );

        set_transient('hha_admin_notices', $notices, 60);
    }

    /**
     * Show admin notices.
     */
    public function show_admin_notices() {
        $notices = get_transient('hha_admin_notices');

        if (!$notices) {
            return;
        }

        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
        }

        delete_transient('hha_admin_notices');
    }
}
