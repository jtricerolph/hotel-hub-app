# Hotel Hub App

Multi-hotel operations management PWA with department-based modules and workforce authentication integration.

## Description

Hotel Hub App provides a comprehensive Progressive Web App (PWA) solution for managing multiple hotel operations across departments including Housekeeping, Front Desk, and Maintenance. The app integrates with NewBook booking systems and ResOS table reservation systems, while using workforce-authentication for permission management.

## Features

- **Multi-Hotel Support**: Manage multiple hotel properties from a single installation
- **Department-Based Modules**: Extensible module system for different operational departments
- **PWA Functionality**: Install as an app on mobile devices for native-like experience
- **Integration Ready**: Built-in support for NewBook and ResOS APIs
- **Permission Management**: Leverages workforce-authentication plugin for granular access control
- **Customizable Themes**: Light/Dark/Custom color schemes
- **Standalone UI**: App-like experience without WordPress theme elements
- **Secure Credential Storage**: Encrypted API keys using WordPress salts

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- **Workforce Authentication** plugin (required dependency)

## Installation

1. Upload the `hotel-hub-app` directory to `/wp-content/plugins/`
2. Ensure **Workforce Authentication** plugin is installed and activated
3. Activate Hotel Hub App through the 'Plugins' menu in WordPress
4. Navigate to Hotel Hub → Settings to configure

## Quick Start

### 1. Add a Hotel

1. Go to **Hotel Hub → Hotels → Add New Hotel**
2. Fill in hotel details (name, address, phone, website)
3. Upload hotel logo and icon
4. Link to a Workforce location
5. Save

### 2. Configure Integrations

**NewBook Integration:**
- Navigate to the **NewBook** tab on hotel edit page
- Enter username, password, and API key
- Select region (EU default)
- Test connection

**ResOS Integration:**
- Navigate to the **ResOS** tab
- Enter API key
- Test connection

### 3. Set Permissions

1. Go to **Workforce Authentication → Departments**
2. Assign users to departments (Housekeeping, Front Desk, etc.)
3. Configure permissions for each department

### 4. Install Module Plugins

Install separate module plugins to add functionality:
- `hotel-hub-housekeeping` - Room status, cleaning checklists
- `hotel-hub-frontdesk` - Check-in/out, guest requests
- `hotel-hub-maintenance` - Work orders, maintenance tracking

## Module Development

Create custom modules by hooking into the registration system:

```php
add_action('hha_register_modules', function($registry) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-my-module.php';
    $module = new My_Custom_Module();
    $registry->register_module($module);
});
```

Each module must implement `get_config()` method:

```php
public function get_config() {
    return array(
        'id' => 'my_module',
        'name' => 'My Module',
        'department' => 'housekeeping',
        'icon' => 'dashicons-icon',
        'color' => '#4caf50',
        'order' => 10,
        'permissions' => array('housekeeping.my_permission'),
        'requires_hotel' => true,
        'integrations' => array('newbook'),
    );
}
```

## Theme Customization

Customize the app appearance in **Hotel Hub → Settings**:

- **Light Mode**: Default bright theme
- **Dark Mode**: Dark theme for low-light environments
- **Custom Mode**: Choose your brand color with color picker

## API Reference

### Hotels API

```php
// Get all hotels
$hotels = hha()->hotels->get_all();

// Get active hotels only
$hotels = hha()->hotels->get_active();

// Get hotel by ID
$hotel = hha()->hotels->get($hotel_id);

// Create hotel
$hotel_id = hha()->hotels->create($hotel_data);

// Update hotel
hha()->hotels->update($hotel_id, $hotel_data);
```

### Module Registry

```php
// Get all registered modules
$modules = hha()->modules->get_modules();

// Get user's permitted modules
$user_modules = hha()->modules->get_user_modules($user_id);
```

## Security

- API credentials encrypted using WordPress `SECURE_AUTH_KEY` salt
- Nonce verification on all AJAX requests
- Permission checks on every module load
- Secure session management

## Support

For issues, feature requests, or contributions, please visit:
https://github.com/jtricerolph/hotel-hub-app

## License

GPL v2 or later

## Changelog

### 1.0.0 - 2025-01-XX
- Initial release
- Multi-hotel management
- NewBook and ResOS integration
- Module registration system
- PWA functionality
- Theme customization
