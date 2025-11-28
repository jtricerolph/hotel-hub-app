# Hotel Hub App - Module Development Guide

This guide explains how to develop custom modules for Hotel Hub App.

## Module Architecture

Hotel Hub App uses an object-based module registration system. Each module is a PHP class that implements specific methods required by the `HHA_Modules` registry.

## Creating a Module

### Step 1: Create Your Plugin

Create a standard WordPress plugin with a unique name:

```php
<?php
/**
 * Plugin Name: Hotel Hub - My Custom Module
 * Description: Custom module for Hotel Hub App
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MY_MODULE_VERSION', '1.0.0');
define('MY_MODULE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MY_MODULE_PLUGIN_URL', plugin_url(__FILE__));

// Include core class
require_once MY_MODULE_PLUGIN_DIR . 'includes/class-my-module-core.php';

// Initialize module
function my_module_init() {
    My_Module_Core::instance();
}
add_action('plugins_loaded', 'my_module_init');
```

### Step 2: Create Core Module Class

Create `includes/class-my-module-core.php`:

```php
<?php
/**
 * Core module class
 */
class My_Module_Core {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Register with Hotel Hub App
        add_action('hha_register_modules', array($this, 'register_module'));

        // Register permissions with Workforce Authentication
        add_action('wfa_register_permissions', array($this, 'register_permissions'));
    }

    /**
     * Register module with Hotel Hub App
     *
     * @param HHA_Modules $modules_manager
     */
    public function register_module($modules_manager) {
        $modules_manager->register_module($this);
    }

    /**
     * Get module configuration (REQUIRED by HHA_Modules)
     *
     * @return array Module configuration
     */
    public function get_config() {
        return array(
            'id'             => 'my_module',           // Unique module ID
            'name'           => __('My Module', 'my-module'),
            'description'    => __('Custom module description', 'my-module'),
            'department'     => 'housekeeping',        // Department slug
            'icon'           => 'dashicons-admin-tools',
            'color'          => '#2196f3',             // Hex color
            'order'          => 10,                    // Display order
            'permissions'    => array(                  // Required permissions
                'my_module_access',
                'my_module_admin'
            ),
            'requires_hotel' => true,                   // Requires hotel selection
            'integrations'   => array('newbook'),       // Required integrations
            'settings_pages' => array(                  // Settings pages
                array(
                    'slug'       => 'my-module-settings',
                    'title'      => __('My Module Settings', 'my-module'),
                    'menu_title' => __('My Module', 'my-module'),
                    'callback'   => array('My_Module_Settings', 'render')
                )
            )
        );
    }

    /**
     * Render module content (REQUIRED by HHA_Modules)
     *
     * Called when module is displayed to the user
     *
     * @param array $params Optional parameters
     */
    public function render($params = array()) {
        // Check permissions
        if (!$this->user_can_access()) {
            echo '<div class="hha-error">You do not have permission to access this module.</div>';
            return;
        }

        // Render your module UI
        echo '<div class="my-module-container">';
        echo '<h1>My Custom Module</h1>';
        // Your module content here
        echo '</div>';
    }

    /**
     * Register permissions
     *
     * @param WFA_Permissions $permissions_manager
     */
    public function register_permissions($permissions_manager) {
        $permissions_manager->register_permission(
            'my_module_access',
            __('Access My Module', 'my-module'),
            __('View and use My Module', 'my-module'),
            'my_module'
        );

        $permissions_manager->register_permission(
            'my_module_admin',
            __('My Module Administration', 'my-module'),
            __('Configure My Module settings', 'my-module'),
            'my_module'
        );
    }

    /**
     * Check if user can access module
     */
    private function user_can_access() {
        if (function_exists('wfa_user_has_permission')) {
            return wfa_user_has_permission('my_module_access');
        }
        return current_user_can('edit_posts');
    }
}
```

## Common Pitfalls & Troubleshooting

### Error: "Cannot use object of type HHA_Modules as array"

**Problem:** Trying to register your module using array syntax instead of calling the registration method.

**Incorrect:**
```php
add_action('hha_register_modules', function($modules) {
    // ❌ WRONG - This will cause a fatal error
    $modules['my_module'] = array(
        'id' => 'my_module',
        'name' => 'My Module',
        // ... config
    );
});
```

**Correct:**
```php
public function register_module($modules_manager) {
    // ✅ CORRECT - Register the object instance
    $modules_manager->register_module($this);
}
```

### Error: "Module must implement get_config() method"

**Problem:** Your module class is missing the required `get_config()` method.

**Solution:** Every module class must have a `get_config()` method that returns the module configuration array.

```php
public function get_config() {
    return array(
        'id'         => 'my_module',  // Required
        'name'       => 'My Module',  // Required
        'department' => 'housekeeping', // Required
        // Optional fields below
        'icon'           => 'dashicons-admin-tools',
        'color'          => '#2196f3',
        'order'          => 10,
        'permissions'    => array(),
        'requires_hotel' => true,
        'integrations'   => array(),
        'description'    => '',
        'settings_pages' => array()
    );
}
```

### Error: "Module config missing required field: {field}"

**Problem:** Your `get_config()` method is missing one of the required fields.

**Required fields:**
- `id` - Unique module identifier (string)
- `name` - Display name (string)
- `department` - Department slug (string)

**Example:**
```php
public function get_config() {
    return array(
        'id'         => 'my_module',      // ✅ Required
        'name'       => 'My Module',      // ✅ Required
        'department' => 'housekeeping',   // ✅ Required
        // All other fields are optional
    );
}
```

### Error: "Module does not implement render() method"

**Problem:** Your module class is missing the required `render()` method.

**Solution:** Add a `render()` method to output your module's UI:

```php
public function render($params = array()) {
    // Output your module's HTML
    echo '<div class="my-module">';
    echo '<h1>My Module</h1>';
    // Your content here
    echo '</div>';
}
```

## Module Configuration Reference

### Required Fields

```php
'id' => 'my_module',           // Unique identifier (no spaces, lowercase)
'name' => 'My Module',         // Display name shown in UI
'department' => 'housekeeping' // Department: housekeeping, frontdesk, maintenance, etc.
```

### Optional Fields

```php
'icon' => 'dashicons-admin-tools',     // WordPress Dashicon class
'color' => '#2196f3',                  // Hex color for module accent
'order' => 10,                         // Display order (lower = first)
'description' => 'Module description', // Short description
'permissions' => array(                // Required permission keys
    'my_module_access',
    'my_module_admin'
),
'requires_hotel' => true,              // Require hotel selection (default: true)
'integrations' => array('newbook'),    // Required integrations
'settings_pages' => array(             // Admin settings pages
    array(
        'slug'       => 'my-settings',
        'title'      => 'Settings Title',
        'menu_title' => 'Menu Title',
        'callback'   => array('My_Settings_Class', 'render')
    )
)
```

## Available Departments

Use these department slugs in your module config:

- `housekeeping` - Housekeeping operations
- `frontdesk` - Front desk / reception
- `maintenance` - Maintenance & repairs
- `restaurant` - Food & beverage
- `events` - Events & conferences
- `admin` - Administration

## Available Integrations

Modules can require specific integrations to be configured:

- `newbook` - NewBook PMS integration
- `resos` - ResOS table reservations

If a module requires an integration, it will only be shown for hotels that have that integration configured.

## HHA_Modules API Reference

Methods available on the `$modules_manager` object passed to `hha_register_modules`:

```php
// Register a module (pass your module instance)
$modules_manager->register_module($module_instance): bool

// Get all registered modules
$modules_manager->get_modules(): array

// Get user's permitted modules
$modules_manager->get_user_modules($user_id = null, $hotel_id = null): array

// Get modules by department
$modules_manager->get_modules_by_department($user_id = null, $hotel_id = null): array

// Get specific module config
$modules_manager->get_module($module_id): array|false

// Get module instance
$modules_manager->get_module_instance($module_id): object|false

// Check user access to module
$modules_manager->user_can_access_module($module_id, $user_id = null, $hotel_id = null): bool

// Render a module
$modules_manager->render_module($module_id, $params = array()): void
```

## Accessing Hotel Hub APIs

### Get Hotel Information

```php
// Get current hotel from session/context
$hotel_id = hha_get_current_hotel_id();
$hotel = hha()->hotels->get($hotel_id);

// Access hotel data
$hotel_name = $hotel->name;
$hotel_address = $hotel->address;
```

### Access Integrations

```php
// Get NewBook integration settings
$newbook_settings = hha()->integrations->get_settings($hotel_id, 'newbook');

// Get NewBook API client
require_once HHA_PLUGIN_DIR . 'includes/class-hha-newbook-api.php';
$api = new HHA_NewBook_API($newbook_settings);

// Make API calls
$sites = $api->get_sites();
$bookings = $api->get_bookings($start_date, $end_date);
```

### Check Integration Status

```php
// Check if integration is active for hotel
if (hha()->integrations->is_active($hotel_id, 'newbook')) {
    // NewBook is configured for this hotel
}
```

## Best Practices

1. **Always check dependencies**: Verify Hotel Hub App and Workforce Authentication are active before initializing
2. **Use singletons**: Follow the singleton pattern for your main module class
3. **Check permissions**: Always verify user permissions before rendering sensitive data
4. **Namespace your code**: Use unique class prefixes to avoid conflicts
5. **Enqueue assets properly**: Use WordPress enqueue functions for CSS/JS
6. **Sanitize inputs**: Always sanitize and validate user input
7. **Escape outputs**: Use `esc_html()`, `esc_attr()`, etc. when outputting data
8. **Follow WordPress coding standards**: Use WordPress coding conventions
9. **Provide fallbacks**: Handle cases where dependencies aren't available
10. **Document your permissions**: Clearly document what each permission allows

## Example: Complete Module with AJAX

```php
<?php
class My_Module_Core {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('hha_register_modules', array($this, 'register_module'));
        add_action('wfa_register_permissions', array($this, 'register_permissions'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_my_module_action', array($this, 'handle_ajax'));
    }

    public function register_module($modules_manager) {
        $modules_manager->register_module($this);
    }

    public function get_config() {
        return array(
            'id'          => 'my_module',
            'name'        => __('My Module', 'my-module'),
            'department'  => 'housekeeping',
            'icon'        => 'dashicons-admin-tools',
            'color'       => '#2196f3',
            'permissions' => array('my_module_access')
        );
    }

    public function render($params = array()) {
        if (!wfa_user_can('my_module_access')) {
            echo '<div class="hha-error">Access denied</div>';
            return;
        }

        include MY_MODULE_PLUGIN_DIR . 'templates/module-view.php';
    }

    public function register_permissions($permissions_manager) {
        $permissions_manager->register_permission(
            'my_module_access',
            __('Access My Module', 'my-module'),
            __('View and use My Module', 'my-module'),
            'my_module'
        );
    }

    public function enqueue_assets() {
        // Only load on module pages
        if (!$this->is_module_page()) {
            return;
        }

        wp_enqueue_style(
            'my-module',
            MY_MODULE_PLUGIN_URL . 'assets/css/module.css',
            array(),
            MY_MODULE_VERSION
        );

        wp_enqueue_script(
            'my-module',
            MY_MODULE_PLUGIN_URL . 'assets/js/module.js',
            array('jquery'),
            MY_MODULE_VERSION,
            true
        );

        wp_localize_script('my-module', 'myModuleData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_module_nonce')
        ));
    }

    public function handle_ajax() {
        check_ajax_referer('my_module_nonce', 'nonce');

        if (!wfa_user_can('my_module_access')) {
            wp_send_json_error(array('message' => 'Access denied'));
        }

        // Handle AJAX request
        $result = $this->do_something();

        wp_send_json_success($result);
    }

    private function is_module_page() {
        if (function_exists('hha_is_module_page')) {
            return hha_is_module_page('my_module');
        }
        return false;
    }
}
```

## Testing Your Module

1. **Activate dependencies**: Ensure Workforce Authentication and Hotel Hub App are active
2. **Activate your module**: Activate your module plugin
3. **Check registration**: Verify your module appears in Hotel Hub navigation
4. **Test permissions**:
   - Create a test department
   - Assign your module's permissions to the department
   - Create a test user in that department
   - Log in as test user and verify access
5. **Test integration requirements**: If your module requires integrations, verify it only shows for hotels with those integrations configured
6. **Test rendering**: Click on your module and verify the UI renders correctly
7. **Test AJAX**: If using AJAX, test all endpoints with browser dev tools
8. **Error handling**: Test error cases (missing permissions, missing integrations, etc.)

## Support

For questions or issues with module development:
- Review the `HHA_Modules` class: `includes/class-hha-modules.php`
- Check example modules in the `modules/` directory
- Refer to Workforce Authentication permissions documentation: `PERMISSIONS.md`

## License

GPL v2 or later
