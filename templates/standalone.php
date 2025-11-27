<?php
/**
 * Standalone template - Clean app experience without WordPress theme elements.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get user's available modules
$user_modules = hha()->modules->get_user_modules();
$user_hotels = hha()->hotels->get_active();
$current_user = wp_get_current_user();
$current_hotel_id = hha()->auth->get_current_hotel_id();

// Get theme mode and set appropriate status bar color to match header
$theme_mode = get_option('hha_theme_mode', 'light');
$status_bar_color = '#ffffff'; // Default white for light theme
if ($theme_mode === 'dark') {
    $status_bar_color = '#2d2d2d'; // Dark header background
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="theme-color" content="<?php echo esc_attr($status_bar_color); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Hotel Hub">

    <title><?php echo esc_html(get_bloginfo('name')); ?> - Hotel Hub</title>

    <?php wp_head(); ?>

    <style>
        /* Reset body styles to remove theme interference */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: var(--hha-bg-color, #f5f5f5) !important;
            overflow-x: hidden;
        }

        /* Hide any theme elements that might leak through */
        body > *:not(#hha-standalone-app) {
            display: none !important;
        }

        #hha-standalone-app {
            display: block !important;
            min-height: 100vh;
        }
    </style>
</head>
<body <?php body_class('hha-standalone'); ?>>

<div id="hha-standalone-app">
    <!-- App Header -->
    <div class="hha-header">
        <div class="hha-header-left">
            <button class="hha-menu-btn" aria-label="Menu">
                <span class="dashicons dashicons-menu"></span>
            </button>
            <h1 class="hha-header-title">Hotel Hub</h1>
        </div>
        <div class="hha-header-right">
            <button class="hha-hotel-selector-btn" aria-label="Select Hotel">
                <span class="dashicons dashicons-building"></span>
                <span class="hha-current-hotel-name">
                    <?php
                    if ($current_hotel_id) {
                        $current_hotel = hha()->hotels->get($current_hotel_id);
                        echo $current_hotel ? esc_html($current_hotel->name) : 'Select Hotel';
                    } else {
                        echo 'Select Hotel';
                    }
                    ?>
                </span>
            </button>
        </div>
    </div>

    <!-- Sidebar Menu -->
    <div class="hha-sidebar">
        <div class="hha-sidebar-overlay"></div>
        <div class="hha-sidebar-content">
            <div class="hha-sidebar-header">
                <h3>Menu</h3>
                <button class="hha-sidebar-close" aria-label="Close Menu">×</button>
            </div>

            <div class="hha-sidebar-user">
                <div class="hha-user-avatar">
                    <?php echo get_avatar($current_user->ID, 48); ?>
                </div>
                <div class="hha-user-info">
                    <strong><?php echo esc_html($current_user->display_name); ?></strong>
                    <span><?php echo esc_html($current_user->user_email); ?></span>
                </div>
            </div>

            <div class="hha-sidebar-nav" id="hha-navigation">
                <!-- Populated by JavaScript -->
                <div class="hha-loading-nav">
                    <div class="hha-spinner"></div>
                </div>
            </div>

            <div class="hha-sidebar-footer">
                <button class="hha-reload-btn" id="hha-reload-app">
                    <span class="dashicons dashicons-update"></span>
                    Reload App
                </button>
                <?php
                $app_url = hha()->auth->get_app_url();
                $logout_url = add_query_arg(
                    array(
                        'hha_logout' => '1',
                        '_wpnonce' => wp_create_nonce('hha-logout')
                    ),
                    $app_url
                );
                ?>
                <a href="<?php echo esc_url($logout_url); ?>" class="hha-logout-btn">
                    <span class="dashicons dashicons-exit"></span>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Hotel Selector Modal -->
    <div class="hha-hotel-selector" style="display: none;">
        <div class="hha-modal-overlay"></div>
        <div class="hha-modal-content">
            <div class="hha-modal-header">
                <h3>Select Hotel</h3>
                <button class="hha-modal-close" aria-label="Close">×</button>
            </div>
            <div class="hha-modal-body">
                <div class="hha-hotel-list">
                    <?php foreach ($user_hotels as $hotel) : ?>
                        <div class="hha-hotel-item" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                            <div class="hha-hotel-icon">
                                <?php
                                $icon_url = hha()->hotels->get_icon_url($hotel->id, 'thumbnail');
                                if ($icon_url) :
                                ?>
                                    <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($hotel->name); ?>">
                                <?php else : ?>
                                    <span class="dashicons dashicons-building"></span>
                                <?php endif; ?>
                            </div>
                            <div class="hha-hotel-info">
                                <strong><?php echo esc_html($hotel->name); ?></strong>
                                <?php if ($hotel->address) : ?>
                                    <span><?php echo esc_html($hotel->address); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($current_hotel_id == $hotel->id) : ?>
                                <span class="hha-hotel-active">✓</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Module Container -->
    <div class="hha-module-container">
        <!-- Module content loaded here -->
        <div class="hha-welcome">
            <div class="hha-welcome-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <?php if (empty($user_modules)) : ?>
                <h2>No Module Permissions</h2>
                <p>You currently don't have permission to access any modules.</p>
                <p style="color: #666; font-size: 14px; margin-top: 20px;">Please contact your administrator to request access.</p>
            <?php else : ?>
                <h2>Welcome to Hotel Hub</h2>
                <p>Select a hotel and choose a module from the menu to get started.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Install PWA Prompt -->
    <div class="hha-install-prompt" style="display: none;">
        <div class="hha-install-prompt-content">
            <span class="dashicons dashicons-smartphone"></span>
            <div>
                <strong>Install Hotel Hub</strong>
                <p>Add to home screen for quick access</p>
            </div>
            <button class="hha-install-btn">Install</button>
            <button class="hha-install-dismiss" aria-label="Dismiss">×</button>
        </div>
    </div>

    <!-- Offline Indicator -->
    <div class="hha-offline-indicator" style="display: none;">
        <span class="dashicons dashicons-warning"></span>
        You are offline
    </div>

    <!-- Loading Overlay -->
    <div class="hha-loading-overlay" style="display: none;">
        <div class="hha-spinner"></div>
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>
