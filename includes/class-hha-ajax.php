<?php
/**
 * AJAX handlers - Frontend AJAX request handlers.
 *
 * Handles all AJAX requests from the app including module loading,
 * hotel switching, and integration testing.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_AJAX {

    /**
     * Constructor - Register AJAX handlers.
     */
    public function __construct() {
        // User-facing AJAX actions
        add_action('wp_ajax_hha_get_user_modules', array($this, 'get_user_modules'));
        add_action('wp_ajax_hha_get_user_hotels', array($this, 'get_user_hotels'));
        add_action('wp_ajax_hha_set_current_hotel', array($this, 'set_current_hotel'));
        add_action('wp_ajax_hha_load_module', array($this, 'load_module'));
        add_action('wp_ajax_hha_get_navigation', array($this, 'get_navigation'));

        // Admin AJAX actions
        add_action('wp_ajax_hha_test_newbook', array($this, 'test_newbook_connection'));
        add_action('wp_ajax_hha_test_resos', array($this, 'test_resos_connection'));
        add_action('wp_ajax_hha_save_integration', array($this, 'save_integration'));
    }

    /**
     * Get user's permitted modules.
     */
    public function get_user_modules() {
        check_ajax_referer('hha-app', 'nonce');

        $user_id = get_current_user_id();
        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : null;

        $modules = hha()->modules->get_user_modules($user_id, $hotel_id);

        wp_send_json_success(array(
            'modules' => array_values($modules),
        ));
    }

    /**
     * Get hotels accessible to user (based on workforce locations).
     */
    public function get_user_hotels() {
        check_ajax_referer('hha-app', 'nonce');

        $user_id = get_current_user_id();

        // Get all active hotels
        $all_hotels = hha()->hotels->get_active();

        // Filter by user's workforce locations if available
        $user_hotels = array();

        if (function_exists('wfa_get_user_locations')) {
            $user_locations = wfa_get_user_locations($user_id);
            $location_ids = wp_list_pluck($user_locations, 'id');

            foreach ($all_hotels as $hotel) {
                // Include hotel if no location assigned or if user has access to location
                if (!$hotel->location_id || in_array($hotel->location_id, $location_ids)) {
                    $user_hotels[] = array(
                        'id'       => $hotel->id,
                        'name'     => $hotel->name,
                        'slug'     => $hotel->slug,
                        'logo_url' => hha()->hotels->get_logo_url($hotel->id, 'medium'),
                        'icon_url' => hha()->hotels->get_icon_url($hotel->id, 'thumbnail'),
                    );
                }
            }
        } else {
            // If workforce functions not available, return all hotels
            foreach ($all_hotels as $hotel) {
                $user_hotels[] = array(
                    'id'       => $hotel->id,
                    'name'     => $hotel->name,
                    'slug'     => $hotel->slug,
                    'logo_url' => hha()->hotels->get_logo_url($hotel->id, 'medium'),
                    'icon_url' => hha()->hotels->get_icon_url($hotel->id, 'thumbnail'),
                );
            }
        }

        wp_send_json_success(array(
            'hotels' => $user_hotels,
        ));
    }

    /**
     * Set current hotel in session.
     */
    public function set_current_hotel() {
        check_ajax_referer('hha-app', 'nonce');

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Invalid hotel ID',
            ));
        }

        // Verify hotel exists and is active
        $hotel = hha()->hotels->get($hotel_id);

        if (!$hotel || !$hotel->is_active) {
            wp_send_json_error(array(
                'message' => 'Hotel not found or inactive',
            ));
        }

        // Store in session
        if (!session_id()) {
            session_start();
        }

        $_SESSION['hha_current_hotel_id'] = $hotel_id;

        wp_send_json_success(array(
            'hotel' => array(
                'id'   => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
            ),
        ));
    }

    /**
     * Load module content.
     */
    public function load_module() {
        check_ajax_referer('hha-app', 'nonce');

        $module_id = isset($_POST['module_id']) ? sanitize_text_field($_POST['module_id']) : '';
        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : null;

        if (!$module_id) {
            wp_send_json_error(array(
                'message' => 'Module ID required',
            ));
        }

        // Check user has access to module
        $user_id = get_current_user_id();

        if (!hha()->modules->user_can_access_module($module_id, $user_id, $hotel_id)) {
            wp_send_json_error(array(
                'message' => 'Access denied',
            ));
        }

        // Get module config
        $module_config = hha()->modules->get_module($module_id);

        if (!$module_config) {
            wp_send_json_error(array(
                'message' => 'Module not found',
            ));
        }

        // Render module
        ob_start();
        hha()->modules->render_module($module_id, array(
            'hotel_id' => $hotel_id,
        ));
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content,
            'config'  => $module_config,
        ));
    }

    /**
     * Get navigation data for current user.
     */
    public function get_navigation() {
        check_ajax_referer('hha-app', 'nonce');

        $user_id = get_current_user_id();
        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : null;

        $navigation = hha()->modules->get_navigation_data($user_id, $hotel_id);

        wp_send_json_success(array(
            'navigation' => $navigation,
        ));
    }

    /**
     * Test NewBook connection (admin).
     */
    public function test_newbook_connection() {
        check_ajax_referer('hha-admin', 'nonce');

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $region = isset($_POST['region']) ? sanitize_text_field($_POST['region']) : 'eu';

        $result = hha()->integrations->test_newbook_connection(array(
            'username' => $username,
            'password' => $password,
            'api_key'  => $api_key,
            'region'   => $region,
        ));

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test ResOS connection (admin).
     */
    public function test_resos_connection() {
        check_ajax_referer('hha-admin', 'nonce');

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        $result = hha()->integrations->test_resos_connection(array(
            'api_key' => $api_key,
        ));

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Save integration settings (admin).
     */
    public function save_integration() {
        check_ajax_referer('hha-admin', 'nonce');

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $integration_type = isset($_POST['integration_type']) ? sanitize_text_field($_POST['integration_type']) : '';
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $is_active = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true;

        if (!$hotel_id || !$integration_type) {
            wp_send_json_error(array(
                'message' => 'Hotel ID and integration type required',
            ));
        }

        // Save integration
        $result = hha()->integrations->save($hotel_id, $integration_type, $settings, $is_active);

        if ($result) {
            wp_send_json_success(array(
                'message'        => 'Integration saved successfully',
                'integration_id' => $result,
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save integration',
            ));
        }
    }
}
