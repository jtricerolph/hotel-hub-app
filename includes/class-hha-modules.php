<?php
/**
 * Module registry - Manages registration and loading of department modules.
 *
 * Allows separate module plugins to register themselves via action hooks.
 * Filters modules based on user permissions and hotel integrations.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Modules {

    /**
     * Registered modules.
     *
     * @var array
     */
    private $modules = array();

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', array($this, 'register_modules'), 20);
    }

    /**
     * Register modules hook - allows module plugins to register.
     */
    public function register_modules() {
        do_action('hha_register_modules', $this);
    }

    /**
     * Register a module.
     *
     * @param object $module Module object with get_config() method.
     * @return bool True on success, false on failure.
     */
    public function register_module($module) {
        // Validate module has required method
        if (!method_exists($module, 'get_config')) {
            error_log('HHA_Modules: Module must implement get_config() method');
            return false;
        }

        $config = $module->get_config();

        // Validate required config fields
        $required_fields = array('id', 'name', 'department');

        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                error_log('HHA_Modules: Module config missing required field: ' . $field);
                return false;
            }
        }

        // Set defaults for optional fields
        $config = wp_parse_args($config, array(
            'icon'             => 'dashicons-admin-generic',
            'color'            => '#2196f3',
            'order'            => 10,
            'permissions'      => array(),
            'requires_hotel'   => true,
            'integrations'     => array(),
            'description'      => '',
        ));

        // Store module with instance
        $this->modules[$config['id']] = array(
            'config'   => $config,
            'instance' => $module,
        );

        return true;
    }

    /**
     * Get all registered modules.
     *
     * @return array Array of module configs.
     */
    public function get_modules() {
        $modules = array();

        foreach ($this->modules as $module_id => $module_data) {
            $modules[$module_id] = $module_data['config'];
        }

        // Sort by order
        uasort($modules, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $modules;
    }

    /**
     * Get user's permitted modules based on workforce permissions.
     *
     * @param int|null $user_id  User ID (defaults to current user).
     * @param int|null $hotel_id Hotel ID (filters by required integrations).
     * @return array Array of permitted module configs.
     */
    public function get_user_modules($user_id = null, $hotel_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $all_modules = $this->get_modules();
        $permitted_modules = array();

        foreach ($all_modules as $module_id => $config) {
            // Check permissions
            if (!empty($config['permissions'])) {
                $has_permission = false;

                foreach ($config['permissions'] as $permission) {
                    if (wfa_user_can($user_id, $permission)) {
                        $has_permission = true;
                        break;
                    }
                }

                if (!$has_permission) {
                    continue;
                }
            }

            // Check required integrations if hotel is specified
            if ($hotel_id && !empty($config['integrations'])) {
                $has_integrations = true;

                foreach ($config['integrations'] as $integration_type) {
                    if (!hha()->integrations->is_active($hotel_id, $integration_type)) {
                        $has_integrations = false;
                        break;
                    }
                }

                if (!$has_integrations) {
                    continue;
                }
            }

            $permitted_modules[$module_id] = $config;
        }

        return $permitted_modules;
    }

    /**
     * Get modules grouped by department.
     *
     * @param int|null $user_id  User ID (defaults to current user).
     * @param int|null $hotel_id Hotel ID (filters by required integrations).
     * @return array Modules grouped by department.
     */
    public function get_modules_by_department($user_id = null, $hotel_id = null) {
        $modules = $this->get_user_modules($user_id, $hotel_id);
        $grouped = array();

        foreach ($modules as $module_id => $config) {
            $department = $config['department'];

            if (!isset($grouped[$department])) {
                $grouped[$department] = array();
            }

            $grouped[$department][$module_id] = $config;
        }

        return $grouped;
    }

    /**
     * Get module config by ID.
     *
     * @param string $module_id Module ID.
     * @return array|false Module config or false if not found.
     */
    public function get_module($module_id) {
        if (!isset($this->modules[$module_id])) {
            return false;
        }

        return $this->modules[$module_id]['config'];
    }

    /**
     * Get module instance by ID.
     *
     * @param string $module_id Module ID.
     * @return object|false Module instance or false if not found.
     */
    public function get_module_instance($module_id) {
        if (!isset($this->modules[$module_id])) {
            return false;
        }

        return $this->modules[$module_id]['instance'];
    }

    /**
     * Check if user has access to a specific module.
     *
     * @param string   $module_id Module ID.
     * @param int|null $user_id   User ID (defaults to current user).
     * @param int|null $hotel_id  Hotel ID (for integration checks).
     * @return bool True if user has access, false otherwise.
     */
    public function user_can_access_module($module_id, $user_id = null, $hotel_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $module = $this->get_module($module_id);

        if (!$module) {
            return false;
        }

        // Check permissions
        if (!empty($module['permissions'])) {
            $has_permission = false;

            foreach ($module['permissions'] as $permission) {
                if (wfa_user_can($user_id, $permission)) {
                    $has_permission = true;
                    break;
                }
            }

            if (!$has_permission) {
                return false;
            }
        }

        // Check required integrations
        if ($hotel_id && !empty($module['integrations'])) {
            foreach ($module['integrations'] as $integration_type) {
                if (!hha()->integrations->is_active($hotel_id, $integration_type)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Render module content.
     *
     * @param string $module_id Module ID.
     * @param array  $params    Optional parameters to pass to module.
     * @return void
     */
    public function render_module($module_id, $params = array()) {
        $instance = $this->get_module_instance($module_id);

        if (!$instance) {
            echo '<div class="hha-error">Module not found: ' . esc_html($module_id) . '</div>';
            return;
        }

        // Check if module has render method
        if (!method_exists($instance, 'render')) {
            echo '<div class="hha-error">Module does not implement render() method</div>';
            return;
        }

        // Render module
        $instance->render($params);
    }

    /**
     * Get module navigation data for frontend.
     *
     * @param int|null $user_id  User ID (defaults to current user).
     * @param int|null $hotel_id Hotel ID (filters by required integrations).
     * @return array Navigation data grouped by department.
     */
    public function get_navigation_data($user_id = null, $hotel_id = null) {
        $grouped_modules = $this->get_modules_by_department($user_id, $hotel_id);
        $navigation = array();

        foreach ($grouped_modules as $department => $modules) {
            $navigation[] = array(
                'department' => $department,
                'label'      => $this->get_department_label($department),
                'modules'    => array_map(function($module) {
                    return array(
                        'id'          => $module['id'],
                        'name'        => $module['name'],
                        'icon'        => $module['icon'],
                        'color'       => $module['color'],
                        'description' => $module['description'],
                    );
                }, array_values($modules)),
            );
        }

        return $navigation;
    }

    /**
     * Get human-readable department label.
     *
     * @param string $department Department slug.
     * @return string Department label.
     */
    private function get_department_label($department) {
        $labels = array(
            'housekeeping' => 'Housekeeping',
            'frontdesk'    => 'Front Desk',
            'maintenance'  => 'Maintenance',
            'restaurant'   => 'Restaurant',
            'events'       => 'Events',
            'admin'        => 'Administration',
        );

        return isset($labels[$department]) ? $labels[$department] : ucfirst($department);
    }

    /**
     * Enqueue module assets (called by modules if needed).
     *
     * @param string $module_id Module ID.
     * @param array  $scripts   Array of script URLs.
     * @param array  $styles    Array of style URLs.
     * @return void
     */
    public function enqueue_module_assets($module_id, $scripts = array(), $styles = array()) {
        // Enqueue styles
        foreach ($styles as $handle => $style_url) {
            wp_enqueue_style(
                'hha-module-' . $module_id . '-' . $handle,
                $style_url,
                array('hha-app'),
                HHA_VERSION
            );
        }

        // Enqueue scripts
        foreach ($scripts as $handle => $script_url) {
            wp_enqueue_script(
                'hha-module-' . $module_id . '-' . $handle,
                $script_url,
                array('jquery', 'hha-app'),
                HHA_VERSION,
                true
            );
        }
    }
}
