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
        add_action('wp_ajax_hha_check_session', array($this, 'check_session'));

        // Admin AJAX actions
        add_action('wp_ajax_hha_test_newbook', array($this, 'test_newbook_connection'));
        add_action('wp_ajax_hha_test_resos', array($this, 'test_resos_connection'));
        add_action('wp_ajax_hha_save_integration', array($this, 'save_integration'));
        add_action('wp_ajax_hha_fetch_newbook_sites', array($this, 'fetch_newbook_sites'));
        add_action('wp_ajax_hha_save_category_sort', array($this, 'save_category_sort'));
        add_action('wp_ajax_hha_fetch_task_types', array($this, 'fetch_task_types'));
        add_action('wp_ajax_hha_save_task_types', array($this, 'save_task_types'));
        add_action('wp_ajax_hha_fetch_note_types', array($this, 'fetch_note_types'));
        add_action('wp_ajax_hha_save_note_types', array($this, 'save_note_types'));
        add_action('wp_ajax_hha_save_module_order', array($this, 'save_module_order'));
        add_action('wp_ajax_hha_reset_module_order', array($this, 'reset_module_order'));
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
        $location_ids = HHA_Admin::get_user_location_ids($user_id);

        if (!empty($location_ids)) {
            // User has department/location assignments - filter hotels
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
            // No location restrictions - return all hotels
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
     * Check if session is still valid.
     * Used when app resumes from background to verify authentication.
     */
    public function check_session() {
        check_ajax_referer('hha-app', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(array(
                'message' => 'Session expired',
                'code'    => 'session_expired',
            ), 401);
        }

        // Get current hotel from session
        if (!session_id()) {
            session_start();
        }
        $hotel_id = isset($_SESSION['hha_current_hotel_id']) ? absint($_SESSION['hha_current_hotel_id']) : null;

        wp_send_json_success(array(
            'valid'    => true,
            'user_id'  => $user_id,
            'hotel_id' => $hotel_id,
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

    /**
     * Fetch NewBook sites and organize by category (admin).
     */
    public function fetch_newbook_sites() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_fetch_sites')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $is_resync = isset($_POST['is_resync']) && $_POST['is_resync'];
        $existing_data = isset($_POST['existing_data']) ? json_decode(stripslashes($_POST['existing_data']), true) : array();

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Hotel ID required',
            ));
        }

        // Get NewBook integration settings
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration) {
            wp_send_json_error(array(
                'message' => 'NewBook integration not configured',
            ));
        }

        // Create NewBook API instance
        $api = new HHA_NewBook_API($integration);

        // Fetch sites
        $response = $api->get_sites(true);

        if (!$response['success']) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch sites: ' . $response['message'],
            ));
        }

        $sites = isset($response['data']) ? $response['data'] : array();

        if (empty($sites)) {
            wp_send_json_error(array(
                'message' => 'No sites found',
            ));
        }

        // Organize sites by category
        $categories = array();
        $existing_categories_map = array();
        $existing_sites_map = array();

        // Build map of existing data if resyncing (using IDs as keys)
        if ($is_resync && !empty($existing_data)) {
            foreach ($existing_data as $cat_index => $cat) {
                $cat_id = isset($cat['id']) ? $cat['id'] : $cat['name']; // Fallback to name for old data
                $existing_categories_map[$cat_id] = $cat;
                if (isset($cat['sites'])) {
                    foreach ($cat['sites'] as $site) {
                        $site_id = isset($site['site_id']) ? $site['site_id'] : $site['site_name'];
                        $existing_sites_map[$cat_id][$site_id] = $site;
                    }
                }
            }
        }

        foreach ($sites as $site) {
            $category_id = isset($site['category_id']) && !empty($site['category_id']) ? $site['category_id'] : '0';
            $category_name = isset($site['category_name']) && !empty($site['category_name']) ? $site['category_name'] : 'Uncategorized';
            $site_id = isset($site['site_id']) ? $site['site_id'] : '';
            $site_name = isset($site['site_name']) ? $site['site_name'] : '';

            // Initialize category if not exists
            if (!isset($categories[$category_id])) {
                // Check if category existed before (for resync)
                $cat_excluded = false;
                $cat_order = count($categories);

                if (isset($existing_categories_map[$category_id])) {
                    $cat_excluded = isset($existing_categories_map[$category_id]['excluded']) ? $existing_categories_map[$category_id]['excluded'] : false;
                    $cat_order = isset($existing_categories_map[$category_id]['order']) ? $existing_categories_map[$category_id]['order'] : $cat_order;
                }

                $categories[$category_id] = array(
                    'id'       => $category_id,
                    'name'     => $category_name,
                    'order'    => $cat_order,
                    'excluded' => $cat_excluded,
                    'sites'    => array(),
                );
            } else {
                // Update category name in case it changed
                $categories[$category_id]['name'] = $category_name;
            }

            // Check if site existed before (for resync)
            $site_excluded = false;
            $site_order = count($categories[$category_id]['sites']);

            if (isset($existing_sites_map[$category_id][$site_id])) {
                $site_excluded = isset($existing_sites_map[$category_id][$site_id]['excluded']) ? $existing_sites_map[$category_id][$site_id]['excluded'] : false;
                $site_order = isset($existing_sites_map[$category_id][$site_id]['order']) ? $existing_sites_map[$category_id][$site_id]['order'] : $site_order;
            }

            // Add site to category
            $categories[$category_id]['sites'][] = array(
                'site_id'   => $site_id,
                'site_name' => $site_name,
                'order'     => $site_order,
                'excluded'  => $site_excluded,
            );
        }

        // Convert to indexed array and sort by order
        $categories_array = array_values($categories);

        // Sort categories by order
        usort($categories_array, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        // Sort sites within each category by order then by name
        foreach ($categories_array as &$category) {
            usort($category['sites'], function($a, $b) {
                // If resyncing, use order
                if (isset($a['order']) && isset($b['order'])) {
                    if ($a['order'] !== $b['order']) {
                        return $a['order'] - $b['order'];
                    }
                }
                // Default: sort by site name
                return strcasecmp($a['site_name'], $b['site_name']);
            });
        }

        // Save to integration settings
        $integration['categories_sort'] = $categories_array;
        $result = hha()->integrations->save($hotel_id, 'newbook', $integration, true);

        if ($result) {
            wp_send_json_success(array(
                'message'    => 'Sites fetched successfully',
                'categories' => $categories_array,
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save categories',
            ));
        }
    }

    /**
     * Save category and site sort configuration (admin).
     */
    public function save_category_sort() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_save_category_sort')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $categories_data = isset($_POST['categories_data']) ? json_decode(stripslashes($_POST['categories_data']), true) : array();

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Hotel ID required',
            ));
        }

        if (empty($categories_data)) {
            wp_send_json_error(array(
                'message' => 'Categories data required',
            ));
        }

        // Get current NewBook integration settings
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration) {
            wp_send_json_error(array(
                'message' => 'NewBook integration not configured',
            ));
        }

        // Update categories_sort in settings
        $integration['categories_sort'] = $categories_data;

        // Save integration with updated categories
        $result = hha()->integrations->save($hotel_id, 'newbook', $integration, true);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Category and site configuration saved successfully',
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save configuration',
            ));
        }
    }

    /**
     * Fetch NewBook task types (admin).
     */
    public function fetch_task_types() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_fetch_task_types')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $is_resync = isset($_POST['is_resync']) && $_POST['is_resync'];
        $existing_data = isset($_POST['existing_data']) ? json_decode(stripslashes($_POST['existing_data']), true) : array();

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Hotel ID required',
            ));
        }

        // Get NewBook integration settings
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration) {
            wp_send_json_error(array(
                'message' => 'NewBook integration not configured',
            ));
        }

        // Create NewBook API instance
        $api = new HHA_NewBook_API($integration);

        // Fetch task types
        $response = $api->get_task_types(true);

        if (!$response['success']) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch task types: ' . $response['message'],
            ));
        }

        $task_types = isset($response['data']) ? $response['data'] : array();

        if (empty($task_types)) {
            wp_send_json_error(array(
                'message' => 'No task types found',
            ));
        }

        // Build map of existing configuration if resyncing
        $existing_config_map = array();
        if ($is_resync && !empty($existing_data)) {
            foreach ($existing_data as $task_type) {
                $existing_config_map[$task_type['id']] = $task_type;
            }
        }

        // Default colors for common task types
        $default_colors = array(
            '-1' => '#4CAF50',  // Housekeeping - Green
            '-2' => '#FF9800',  // Maintenance - Orange
        );

        $default_icons = array(
            '-1' => 'vacuum',  // Housekeeping
            '-2' => 'build',   // Maintenance
        );

        // Process task types with configuration
        $task_types_configured = array();
        foreach ($task_types as $task_type) {
            $task_id = isset($task_type['id']) ? $task_type['id'] : '';
            $task_name = isset($task_type['name']) ? $task_type['name'] : '';

            // Get existing configuration or use defaults
            if (isset($existing_config_map[$task_id])) {
                $color = $existing_config_map[$task_id]['color'];
                $icon = $existing_config_map[$task_id]['icon'];
            } else {
                $color = isset($default_colors[$task_id]) ? $default_colors[$task_id] : '#9e9e9e';
                $icon = isset($default_icons[$task_id]) ? $default_icons[$task_id] : 'event';
            }

            $task_types_configured[] = array(
                'id'    => $task_id,
                'name'  => $task_name,
                'color' => $color,
                'icon'  => $icon,
            );
        }

        // Save to integration settings
        $integration['task_types'] = $task_types_configured;
        $result = hha()->integrations->save($hotel_id, 'newbook', $integration, true);

        if ($result) {
            wp_send_json_success(array(
                'message'    => 'Task types fetched successfully',
                'task_types' => $task_types_configured,
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save task types',
            ));
        }
    }

    /**
     * Save task types configuration (admin).
     */
    public function save_task_types() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_save_task_types')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $task_types_data = isset($_POST['task_types_data']) ? json_decode(stripslashes($_POST['task_types_data']), true) : array();

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Hotel ID required',
            ));
        }

        if (empty($task_types_data)) {
            wp_send_json_error(array(
                'message' => 'Task types data required',
            ));
        }

        // Get current NewBook integration settings
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration) {
            wp_send_json_error(array(
                'message' => 'NewBook integration not configured',
            ));
        }

        // Update task_types in settings
        $integration['task_types'] = $task_types_data;

        // Save integration with updated task types
        $result = hha()->integrations->save($hotel_id, 'newbook', $integration, true);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Task types configuration saved successfully',
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save task types configuration',
            ));
        }
    }

    /**
     * Fetch NewBook note types (admin).
     */
    public function fetch_note_types() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_fetch_note_types')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $is_resync = isset($_POST['is_resync']) && $_POST['is_resync'];
        $existing_data = isset($_POST['existing_data']) ? json_decode(stripslashes($_POST['existing_data']), true) : array();

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Hotel ID required',
            ));
        }

        // Get NewBook integration settings
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration) {
            wp_send_json_error(array(
                'message' => 'NewBook integration not configured',
            ));
        }

        // Create NewBook API instance
        $api = new HHA_NewBook_API($integration);

        // Fetch note types
        $response = $api->get_note_types(true);

        if (!$response['success']) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch note types: ' . $response['message'],
            ));
        }

        $note_types = isset($response['data']) ? $response['data'] : array();

        if (empty($note_types)) {
            wp_send_json_error(array(
                'message' => 'No note types found',
            ));
        }

        // Build map of existing configuration if resyncing
        $existing_config_map = array();
        if ($is_resync && !empty($existing_data)) {
            foreach ($existing_data as $note_type) {
                $existing_config_map[$note_type['id']] = $note_type;
            }
        }

        // Default colors for common note types
        $default_colors = array(
            '1' => '#2196F3',  // Information - Blue
            '2' => '#FF9800',  // Warning - Orange
        );

        $default_icons = array(
            '1' => 'info',     // Information
            '2' => 'warning',  // Warning
        );

        // Process note types with configuration
        $note_types_configured = array();
        foreach ($note_types as $note_type) {
            $note_id = isset($note_type['note_type_id']) ? $note_type['note_type_id'] : '';
            $note_name = isset($note_type['note_type_name']) ? $note_type['note_type_name'] : '';
            $note_default = isset($note_type['note_type_default']) ? $note_type['note_type_default'] : '0';

            // Get existing configuration or use defaults
            if (isset($existing_config_map[$note_id])) {
                $color = $existing_config_map[$note_id]['color'];
                $icon = $existing_config_map[$note_id]['icon'];
            } else {
                $color = isset($default_colors[$note_id]) ? $default_colors[$note_id] : '#9e9e9e';
                $icon = isset($default_icons[$note_id]) ? $default_icons[$note_id] : 'note_add';
            }

            $note_types_configured[] = array(
                'id'      => $note_id,
                'name'    => $note_name,
                'default' => $note_default,
                'color'   => $color,
                'icon'    => $icon,
            );
        }

        // Save to integration settings
        $integration['note_types'] = $note_types_configured;
        $result = hha()->integrations->save($hotel_id, 'newbook', $integration, true);

        if ($result) {
            wp_send_json_success(array(
                'message'    => 'Note types fetched successfully',
                'note_types' => $note_types_configured,
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save note types',
            ));
        }
    }

    /**
     * Save note types configuration (admin).
     */
    public function save_note_types() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_save_note_types')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $hotel_id = isset($_POST['hotel_id']) ? absint($_POST['hotel_id']) : 0;
        $note_types_data = isset($_POST['note_types_data']) ? json_decode(stripslashes($_POST['note_types_data']), true) : array();

        if (!$hotel_id) {
            wp_send_json_error(array(
                'message' => 'Hotel ID required',
            ));
        }

        if (empty($note_types_data)) {
            wp_send_json_error(array(
                'message' => 'Note types data required',
            ));
        }

        // Get current NewBook integration settings
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration) {
            wp_send_json_error(array(
                'message' => 'NewBook integration not configured',
            ));
        }

        // Update note_types in settings
        $integration['note_types'] = $note_types_data;

        // Save integration with updated note types
        $result = hha()->integrations->save($hotel_id, 'newbook', $integration, true);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Note types configuration saved successfully',
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to save note types configuration',
            ));
        }
    }

    /**
     * Save module sort order (admin).
     */
    public function save_module_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_save_module_order')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        $module_order = isset($_POST['module_order']) ? json_decode(stripslashes($_POST['module_order']), true) : array();

        if (empty($module_order) || !is_array($module_order)) {
            wp_send_json_error(array(
                'message' => 'Module order data required',
            ));
        }

        // Sanitize module IDs
        $module_order = array_map('sanitize_text_field', $module_order);

        // Save to options
        update_option('hha_module_order', $module_order);

        wp_send_json_success(array(
            'message' => 'Module order saved successfully',
        ));
    }

    /**
     * Reset module sort order to default (admin).
     */
    public function reset_module_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hha_reset_module_order')) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce',
            ));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
            ));
        }

        // Delete the custom order option
        delete_option('hha_module_order');

        wp_send_json_success(array(
            'message' => 'Module order reset to default',
        ));
    }
}
