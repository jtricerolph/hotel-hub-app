<?php
/**
 * Admin view - Hotel edit page.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_new = !$hotel;
$page_title = $is_new ? 'Add New Hotel' : 'Edit Hotel';
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>

    <div class="hha-tabs">
        <ul class="hha-tab-nav">
            <li><a href="#tab-basic" class="hha-tab-link active">Basic Information</a></li>
            <?php if (!$is_new) : ?>
                <li><a href="#tab-newbook" class="hha-tab-link">NewBook Integration</a></li>
                <li><a href="#tab-resos" class="hha-tab-link">ResOS Integration</a></li>
            <?php endif; ?>
        </ul>

        <!-- Basic Information Tab -->
        <div id="tab-basic" class="hha-tab-content active">
            <form method="post" action="">
                <?php wp_nonce_field('hha_save_hotel', 'hha_hotel_nonce'); ?>
                <input type="hidden" name="hotel_id" value="<?php echo $hotel ? esc_attr($hotel->id) : ''; ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="name">Hotel Name *</label></th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" required
                                   value="<?php echo $hotel ? esc_attr($hotel->name) : ''; ?>">
                        </td>
                    </tr>

                    <?php if (!empty($locations)) : ?>
                        <tr>
                            <th><label for="location_id">Workforce Location</label></th>
                            <td>
                                <select id="location_id" name="location_id">
                                    <option value="">-- No Location --</option>
                                    <?php foreach ($locations as $location) : ?>
                                        <option value="<?php echo esc_attr($location->id); ?>"
                                                <?php selected($hotel && $hotel->location_id == $location->id); ?>>
                                            <?php echo esc_html($location->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Link this hotel to a workforce location for permission management.</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <th><label for="address">Address</label></th>
                        <td>
                            <textarea id="address" name="address" rows="3" class="large-text"><?php echo $hotel ? esc_textarea($hotel->address) : ''; ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td>
                            <input type="text" id="phone" name="phone" class="regular-text"
                                   value="<?php echo $hotel ? esc_attr($hotel->phone) : ''; ?>">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="website">Website</label></th>
                        <td>
                            <input type="url" id="website" name="website" class="regular-text"
                                   value="<?php echo $hotel ? esc_attr($hotel->website) : ''; ?>"
                                   placeholder="https://">
                        </td>
                    </tr>

                    <tr>
                        <th><label>Logo</label></th>
                        <td>
                            <div class="hha-image-upload">
                                <input type="hidden" id="logo_id" name="logo_id" value="<?php echo $hotel && $hotel->logo_id ? esc_attr($hotel->logo_id) : ''; ?>">
                                <div class="hha-image-preview">
                                    <?php
                                    $logo_url = $hotel ? hha()->hotels->get_logo_url($hotel->id, 'medium') : '';
                                    if ($logo_url) :
                                    ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="max-width: 250px;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button hha-upload-image-btn" data-target="logo_id">
                                    <?php echo $logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                                </button>
                                <?php if ($logo_url) : ?>
                                    <button type="button" class="button hha-remove-image-btn" data-target="logo_id">Remove</button>
                                <?php endif; ?>
                                <p class="description">Recommended size: 500x200px. Will be auto-resized on upload.</p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th><label>Icon</label></th>
                        <td>
                            <div class="hha-image-upload">
                                <input type="hidden" id="icon_id" name="icon_id" value="<?php echo $hotel && $hotel->icon_id ? esc_attr($hotel->icon_id) : ''; ?>">
                                <div class="hha-image-preview">
                                    <?php
                                    $icon_url = $hotel ? hha()->hotels->get_icon_url($hotel->id, 'thumbnail') : '';
                                    if ($icon_url) :
                                    ?>
                                        <img src="<?php echo esc_url($icon_url); ?>" alt="Icon" style="max-width: 100px;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button hha-upload-image-btn" data-target="icon_id">
                                    <?php echo $icon_url ? 'Change Icon' : 'Upload Icon'; ?>
                                </button>
                                <?php if ($icon_url) : ?>
                                    <button type="button" class="button hha-remove-image-btn" data-target="icon_id">Remove</button>
                                <?php endif; ?>
                                <p class="description">Recommended size: 192x192px. Will be auto-resized on upload.</p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="is_active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="is_active" name="is_active" value="1"
                                       <?php checked($hotel ? $hotel->is_active : 1, 1); ?>>
                                Active
                            </label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="hha_save_hotel" class="button button-primary">Save Hotel</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hotel-hub')); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>

        <?php if (!$is_new) : ?>
            <!-- NewBook Integration Tab -->
            <div id="tab-newbook" class="hha-tab-content">
                <form method="post" action="" class="hha-integration-form">
                    <?php
                    wp_nonce_field('hha_save_integration', 'hha_integration_nonce');
                    $newbook_settings = $newbook_integration ? hha()->integrations->get_settings($hotel->id, 'newbook') : array();
                    ?>
                    <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel->id); ?>">
                    <input type="hidden" name="integration_type" value="newbook">

                    <table class="form-table">
                        <tr>
                            <th><label for="newbook_username">Username *</label></th>
                            <td>
                                <input type="text" id="newbook_username" name="newbook_username" class="regular-text"
                                       value="<?php echo isset($newbook_settings['username']) ? esc_attr($newbook_settings['username']) : ''; ?>">
                            </td>
                        </tr>

                        <tr>
                            <th><label for="newbook_password">Password *</label></th>
                            <td>
                                <input type="password" id="newbook_password" name="newbook_password" class="regular-text"
                                       value="<?php echo isset($newbook_settings['password']) ? esc_attr($newbook_settings['password']) : ''; ?>"
                                       placeholder="<?php echo $newbook_integration ? '(hidden)' : ''; ?>">
                            </td>
                        </tr>

                        <tr>
                            <th><label for="newbook_api_key">API Key *</label></th>
                            <td>
                                <input type="text" id="newbook_api_key" name="newbook_api_key" class="regular-text"
                                       value="<?php echo isset($newbook_settings['api_key']) ? esc_attr($newbook_settings['api_key']) : ''; ?>">
                            </td>
                        </tr>

                        <tr>
                            <th><label for="newbook_region">Region</label></th>
                            <td>
                                <select id="newbook_region" name="newbook_region">
                                    <option value="eu" <?php selected(isset($newbook_settings['region']) ? $newbook_settings['region'] : 'eu', 'eu'); ?>>EU</option>
                                    <option value="us" <?php selected(isset($newbook_settings['region']) ? $newbook_settings['region'] : '', 'us'); ?>>US</option>
                                    <option value="au" <?php selected(isset($newbook_settings['region']) ? $newbook_settings['region'] : '', 'au'); ?>>AU</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="newbook_is_active">Status</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="newbook_is_active" name="is_active" value="1"
                                           <?php checked($newbook_integration ? $newbook_integration->is_active : 0, 1); ?>>
                                    Active
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="hha_save_integration" class="button button-primary">Save Integration</button>
                        <button type="button" class="button hha-test-connection" data-type="newbook">Test Connection</button>
                    </p>
                </form>
            </div>

            <!-- ResOS Integration Tab -->
            <div id="tab-resos" class="hha-tab-content">
                <form method="post" action="" class="hha-integration-form">
                    <?php
                    wp_nonce_field('hha_save_integration', 'hha_integration_nonce');
                    $resos_settings = $resos_integration ? hha()->integrations->get_settings($hotel->id, 'resos') : array();
                    ?>
                    <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel->id); ?>">
                    <input type="hidden" name="integration_type" value="resos">

                    <table class="form-table">
                        <tr>
                            <th><label for="resos_api_key">API Key *</label></th>
                            <td>
                                <input type="text" id="resos_api_key" name="resos_api_key" class="regular-text"
                                       value="<?php echo isset($resos_settings['api_key']) ? esc_attr($resos_settings['api_key']) : ''; ?>">
                            </td>
                        </tr>

                        <tr>
                            <th><label for="resos_is_active">Status</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="resos_is_active" name="is_active" value="1"
                                           <?php checked($resos_integration ? $resos_integration->is_active : 0, 1); ?>>
                                    Active
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="hha_save_integration" class="button button-primary">Save Integration</button>
                        <button type="button" class="button hha-test-connection" data-type="resos">Test Connection</button>
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hha-tabs {
    margin-top: 20px;
}

.hha-tab-nav {
    border-bottom: 1px solid #ccc;
    margin: 0;
    padding: 0;
    list-style: none;
}

.hha-tab-nav li {
    display: inline-block;
    margin: 0;
}

.hha-tab-link {
    display: block;
    padding: 10px 20px;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    margin-bottom: -1px;
}

.hha-tab-link.active {
    background: #fff;
    border-color: #ccc;
}

.hha-tab-content {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccc;
    border-top: none;
}

.hha-tab-content.active {
    display: block;
}

.hha-image-upload {
    max-width: 500px;
}

.hha-image-preview {
    margin-bottom: 10px;
}

.hha-image-preview img {
    border: 1px solid #ddd;
    padding: 5px;
    background: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.hha-tab-link').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.hha-tab-link').removeClass('active');
        $(this).addClass('active');

        $('.hha-tab-content').removeClass('active');
        $(target).addClass('active');
    });
});
</script>
