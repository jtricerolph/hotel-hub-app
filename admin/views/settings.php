<?php
/**
 * Admin view - Settings page.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Hotel Hub Settings</h1>

    <form method="post" action="">
        <?php wp_nonce_field('hha_save_settings', 'hha_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th><label for="theme_mode">Theme Mode</label></th>
                <td>
                    <select id="theme_mode" name="theme_mode">
                        <option value="light" <?php selected($theme_mode, 'light'); ?>>Light</option>
                        <option value="dark" <?php selected($theme_mode, 'dark'); ?>>Dark</option>
                        <option value="custom" <?php selected($theme_mode, 'custom'); ?>>Custom</option>
                    </select>
                    <p class="description">Select the theme mode for the app interface.</p>
                </td>
            </tr>

            <tr class="hha-custom-color-row" <?php echo $theme_mode !== 'custom' ? 'style="display: none;"' : ''; ?>>
                <th><label for="theme_primary_color">Primary Color</label></th>
                <td>
                    <input type="text" id="theme_primary_color" name="theme_primary_color" class="hha-color-picker"
                           value="<?php echo esc_attr($theme_color); ?>">
                    <p class="description">Choose the primary color for custom theme mode.</p>
                </td>
            </tr>

            <tr>
                <th><label>App Page</label></th>
                <td>
                    <?php
                    $app_page_id = get_option('hha_app_page_id');
                    if ($app_page_id && get_post($app_page_id)) {
                        $app_url = get_permalink($app_page_id);
                        echo '<p><a href="' . esc_url($app_url) . '" target="_blank">' . esc_html(get_the_title($app_page_id)) . '</a></p>';
                    } else {
                        echo '<p>No app page configured.</p>';
                    }
                    ?>
                    <p class="description">The page where the app is displayed.</p>
                </td>
            </tr>

            <tr>
                <th><label>Login Page</label></th>
                <td>
                    <?php
                    $login_page_id = get_option('hha_login_page_id');
                    if ($login_page_id && get_post($login_page_id)) {
                        $login_url = get_permalink($login_page_id);
                        echo '<p><a href="' . esc_url($login_url) . '" target="_blank">' . esc_html(get_the_title($login_page_id)) . '</a></p>';
                    } else {
                        echo '<p>No login page configured. <button type="button" class="button" id="hha-create-login-page">Create Login Page</button></p>';
                    }
                    ?>
                    <p class="description">Custom login page for the app.</p>
                </td>
            </tr>

            <tr>
                <th><label for="frontend_only_mode">Frontend-Only Mode</label></th>
                <td>
                    <input type="checkbox" id="frontend_only_mode" name="frontend_only_mode" value="1"
                           <?php checked($frontend_only_mode, true); ?>>
                    <label for="frontend_only_mode">Redirect all frontend pages to the app</label>
                    <p class="description">
                        When enabled, all frontend pages (home, posts, pages) will redirect to the Hotel Hub app.
                        Admin and login pages will still work normally. <strong>Warning:</strong> This will make
                        the app the only accessible page on your site's frontend.
                    </p>
                </td>
            </tr>

            <tr>
                <th><label for="api_logging_enabled">NewBook API Logging</label></th>
                <td>
                    <input type="checkbox" id="api_logging_enabled" name="api_logging_enabled" value="1"
                           <?php checked($api_logging_enabled, true); ?>>
                    <label for="api_logging_enabled">Enable API request logging</label>
                    <p class="description">
                        When enabled, all NewBook API requests will be logged to a dedicated file for monitoring and debugging.
                        Use this during development to track API usage and prevent rate limiting.
                        <?php if ($api_logging_enabled && $api_log_file): ?>
                            <br><strong>Log file:</strong> <code><?php echo esc_html($api_log_file); ?></code>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th><label>PWA Manifest</label></th>
                <td>
                    <p><a href="<?php echo esc_url(home_url('/hotel-hub-manifest.json')); ?>" target="_blank">View Manifest</a></p>
                    <p class="description">Progressive Web App manifest configuration.</p>
                </td>
            </tr>

            <tr>
                <th><label>Service Worker</label></th>
                <td>
                    <p><a href="<?php echo esc_url(home_url('/hotel-hub-sw.js')); ?>" target="_blank">View Service Worker</a></p>
                    <p class="description">PWA service worker for offline functionality.</p>
                </td>
            </tr>

            <tr>
                <th><label>Plugin Version</label></th>
                <td>
                    <p><?php echo esc_html(HHA_VERSION); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="hha_save_settings" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>

<style>
.hha-color-picker {
    max-width: 100px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize color picker
    $('.hha-color-picker').wpColorPicker();

    // Show/hide custom color based on theme mode
    $('#theme_mode').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.hha-custom-color-row').show();
        } else {
            $('.hha-custom-color-row').hide();
        }
    });

    // Create login page
    $('#hha-create-login-page').on('click', function() {
        if (confirm('Create a custom login page for Hotel Hub?')) {
            $.post(ajaxurl, {
                action: 'hha_create_login_page',
                nonce: '<?php echo wp_create_nonce('hha-admin'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to create login page');
                }
            });
        }
    });
});
</script>
