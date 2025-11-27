<?php
/**
 * Plugin Name: Hotel Hub App
 * Plugin URI: https://github.com/jtricerolph/hotel-hub-app
 * Description: Multi-hotel operations management PWA with department modules and workforce authentication integration
 * Version: 1.0.0
 * Author: Joseph Trice-Rolph
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hotel-hub-app
 *
 * @package Hotel_Hub_App
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('HHA_VERSION', '1.0.0');
define('HHA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HHA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HHA_TABLE_PREFIX', 'hha_');

/**
 * Check for required dependencies.
 */
function hha_check_dependencies() {
    // Check if workforce-authentication plugin is active
    if (!function_exists('wfa_user_can')) {
        add_action('admin_notices', 'hha_dependency_notice');
        return false;
    }
    return true;
}

/**
 * Display admin notice for missing dependencies.
 */
function hha_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Hotel Hub App</strong> requires the <strong>Workforce Authentication</strong> plugin to be installed and activated.
            Please install and activate Workforce Authentication to use Hotel Hub App.
        </p>
    </div>
    <?php
}

/**
 * Autoloader for plugin classes.
 */
spl_autoload_register(function($class) {
    // Only load our classes
    if (strpos($class, 'HHA_') !== 0) {
        return;
    }

    // Convert class name to file name
    $class_file = str_replace('_', '-', strtolower($class));

    // Check main includes directory
    $file_path = HHA_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

    if (file_exists($file_path)) {
        require_once $file_path;
        return;
    }

    // Check admin directory
    $admin_path = HHA_PLUGIN_DIR . 'admin/class-' . $class_file . '.php';

    if (file_exists($admin_path)) {
        require_once $admin_path;
        return;
    }
});

/**
 * Initialize the plugin.
 */
function hha_init() {
    // Check dependencies first
    if (!hha_check_dependencies()) {
        return;
    }

    // Initialize core plugin
    HHA_Core::instance();
}
add_action('plugins_loaded', 'hha_init');

/**
 * Plugin activation hook.
 */
function hha_activate() {
    require_once HHA_PLUGIN_DIR . 'includes/class-hha-activator.php';
    HHA_Activator::activate();
}
register_activation_hook(__FILE__, 'hha_activate');

/**
 * Plugin deactivation hook.
 */
function hha_deactivate() {
    require_once HHA_PLUGIN_DIR . 'includes/class-hha-activator.php';
    HHA_Activator::deactivate();
}
register_deactivation_hook(__FILE__, 'hha_deactivate');

/**
 * Get the singleton instance of the main plugin class.
 *
 * @return HHA_Core
 */
function hha() {
    return HHA_Core::instance();
}
