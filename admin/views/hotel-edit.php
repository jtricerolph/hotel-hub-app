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

                <?php if ($newbook_integration && $newbook_integration->is_active) : ?>
                    <hr style="margin: 30px 0;">

                    <h3>Site Categories & Sorting</h3>
                    <p>Manage how sites are categorized and sorted for modules like Twin Optimiser.</p>

                    <div id="hha-category-management">
                        <?php
                        $categories_data = isset($newbook_settings['categories_sort']) ? $newbook_settings['categories_sort'] : array();
                        ?>

                        <p class="submit">
                            <button type="button" id="hha-fetch-sites" class="button button-secondary" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                                <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Fetch Sites from NewBook
                            </button>
                            <?php if (!empty($categories_data)) : ?>
                                <button type="button" id="hha-resync-sites" class="button button-secondary" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Resync Sites
                                </button>
                            <?php endif; ?>
                        </p>

                        <div id="hha-categories-container">
                            <?php if (!empty($categories_data)) : ?>
                                <div class="hha-info-box">
                                    <p><strong>Instructions:</strong></p>
                                    <ul style="margin-left: 20px;">
                                        <li>Drag categories to reorder them</li>
                                        <li>Click "Sort Sites" to manage the order of sites within each category</li>
                                        <li>Use checkboxes to exclude categories or sites from modules</li>
                                        <li>Click "Resync Sites" to update the list when sites are added or changed in NewBook</li>
                                    </ul>
                                </div>

                                <table class="wp-list-table widefat fixed striped" id="hha-categories-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 30px;"></th>
                                            <th>Category</th>
                                            <th style="width: 100px;">Sites Count</th>
                                            <th style="width: 120px;">Actions</th>
                                            <th style="width: 80px;">Exclude</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hha-sortable-categories">
                                        <?php foreach ($categories_data as $index => $category) : ?>
                                            <tr data-category-index="<?php echo esc_attr($index); ?>">
                                                <td class="hha-drag-handle" style="cursor: move;">
                                                    <span class="dashicons dashicons-menu"></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo esc_html($category['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $active_sites = isset($category['sites']) ? count(array_filter($category['sites'], function($site) {
                                                        return empty($site['excluded']);
                                                    })) : 0;
                                                    $total_sites = isset($category['sites']) ? count($category['sites']) : 0;
                                                    echo esc_html($active_sites . ' / ' . $total_sites);
                                                    ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="button button-small hha-sort-sites"
                                                            data-category-index="<?php echo esc_attr($index); ?>"
                                                            data-category-name="<?php echo esc_attr($category['name']); ?>">
                                                        Sort Sites
                                                    </button>
                                                </td>
                                                <td style="text-align: center;">
                                                    <input type="checkbox" class="hha-exclude-category"
                                                           data-category-index="<?php echo esc_attr($index); ?>"
                                                           <?php checked(!empty($category['excluded']), true); ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <p class="submit">
                                    <button type="button" id="hha-save-category-sort" class="button button-primary" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                                        Save Category & Site Configuration
                                    </button>
                                </p>
                            <?php else : ?>
                                <div class="notice notice-info inline">
                                    <p>Click "Fetch Sites from NewBook" to load and configure site categories.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr style="margin: 30px 0;">

                    <h3>Task Types Configuration</h3>
                    <p>Configure task types from NewBook for blocking sites on modules.</p>

                    <div id="hha-task-types-management">
                        <?php
                        $task_types_data = isset($newbook_settings['task_types']) ? $newbook_settings['task_types'] : array();
                        ?>

                        <p class="submit">
                            <button type="button" id="hha-fetch-task-types" class="button button-secondary" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                                <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Fetch Task Types from NewBook
                            </button>
                            <?php if (!empty($task_types_data)) : ?>
                                <button type="button" id="hha-resync-task-types" class="button button-secondary" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Resync Task Types
                                </button>
                            <?php endif; ?>
                        </p>

                        <div id="hha-task-types-container">
                            <?php if (!empty($task_types_data)) : ?>
                                <table class="wp-list-table widefat fixed striped" id="hha-task-types-table">
                                    <thead>
                                        <tr>
                                            <th>Task Type</th>
                                            <th style="width: 150px;">Color</th>
                                            <th style="width: 150px;">Icon</th>
                                            <th style="width: 100px;">Preview</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($task_types_data as $index => $task_type) : ?>
                                            <tr data-task-type-index="<?php echo esc_attr($index); ?>">
                                                <td>
                                                    <strong><?php echo esc_html($task_type['name']); ?></strong>
                                                    <br><small>ID: <?php echo esc_html($task_type['id']); ?></small>
                                                </td>
                                                <td>
                                                    <input type="color" class="hha-task-type-color"
                                                           data-task-type-index="<?php echo esc_attr($index); ?>"
                                                           value="<?php echo esc_attr(isset($task_type['color']) ? $task_type['color'] : '#9e9e9e'); ?>">
                                                </td>
                                                <td>
                                                    <select class="hha-task-type-icon" data-task-type-index="<?php echo esc_attr($index); ?>">
                                                        <?php
                                                        $current_icon = isset($task_type['icon']) ? $task_type['icon'] : 'build';
                                                        $icons = array(
                                                            'build' => 'Build (Wrench)',
                                                            'cleaning_services' => 'Cleaning Services',
                                                            'event' => 'Event (Calendar)',
                                                            'meeting_room' => 'Meeting Room',
                                                            'construction' => 'Construction',
                                                            'home_repair_service' => 'Home Repair',
                                                            'plumbing' => 'Plumbing',
                                                            'electrical_services' => 'Electrical',
                                                            'ac_unit' => 'AC Unit',
                                                            'block' => 'Block',
                                                            'error' => 'Error',
                                                            'warning' => 'Warning',
                                                            'info' => 'Info',
                                                        );
                                                        foreach ($icons as $value => $label) :
                                                        ?>
                                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_icon, $value); ?>>
                                                                <?php echo esc_html($label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="hha-task-preview"
                                                         style="background-color: <?php echo esc_attr(isset($task_type['color']) ? $task_type['color'] : '#9e9e9e'); ?>;
                                                                padding: 8px;
                                                                border-radius: 3px;
                                                                color: white;
                                                                text-align: center;">
                                                        <span class="material-icons" style="font-size: 18px; vertical-align: middle;">
                                                            <?php echo esc_html($current_icon); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <p class="submit">
                                    <button type="button" id="hha-save-task-types" class="button button-primary" data-hotel-id="<?php echo esc_attr($hotel->id); ?>">
                                        Save Task Types Configuration
                                    </button>
                                </p>
                            <?php else : ?>
                                <div class="notice notice-info inline">
                                    <p>Click "Fetch Task Types from NewBook" to load and configure task types.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

    // Category/Site Management
    let categoriesData = <?php echo !empty($categories_data) ? json_encode($categories_data) : '[]'; ?>;

    // Make categories sortable
    if ($('#hha-sortable-categories').length) {
        $('#hha-sortable-categories').sortable({
            handle: '.hha-drag-handle',
            update: function(event, ui) {
                updateCategoriesOrder();
            }
        });
    }

    function updateCategoriesOrder() {
        let newOrder = [];
        $('#hha-sortable-categories tr').each(function() {
            let index = $(this).data('category-index');
            newOrder.push(categoriesData[index]);
        });
        categoriesData = newOrder;
    }

    // Exclude category checkbox
    $(document).on('change', '.hha-exclude-category', function() {
        let index = $(this).data('category-index');
        let currentIndex = $('#hha-sortable-categories tr').index($(this).closest('tr'));
        if (categoriesData[currentIndex]) {
            categoriesData[currentIndex].excluded = $(this).is(':checked');
        }
    });

    // Fetch sites from NewBook
    $('#hha-fetch-sites, #hha-resync-sites').on('click', function() {
        let hotelId = $(this).data('hotel-id');
        let isResync = $(this).attr('id') === 'hha-resync-sites';
        let $button = $(this);
        let originalText = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin" style="margin-top: 3px;"></span> Loading...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hha_fetch_newbook_sites',
                hotel_id: hotelId,
                is_resync: isResync,
                existing_data: isResync ? JSON.stringify(categoriesData) : null,
                nonce: '<?php echo wp_create_nonce('hha_fetch_sites'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    categoriesData = response.data.categories;
                    location.reload(); // Reload to show updated UI
                } else {
                    alert('Error: ' + response.data.message);
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Failed to fetch sites. Please try again.');
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Sort sites button
    $(document).on('click', '.hha-sort-sites', function() {
        let categoryIndex = $(this).data('category-index');
        let currentIndex = $('#hha-sortable-categories tr').index($(this).closest('tr'));
        let category = categoriesData[currentIndex];

        if (!category || !category.sites) {
            alert('No sites found in this category.');
            return;
        }

        showSitesSortModal(currentIndex, category);
    });

    function showSitesSortModal(categoryIndex, category) {
        let modalHtml = `
            <div id="hha-sites-modal" class="hha-modal">
                <div class="hha-modal-content">
                    <div class="hha-modal-header">
                        <h2>Sort Sites: ${category.name}</h2>
                        <span class="hha-modal-close">&times;</span>
                    </div>
                    <div class="hha-modal-body">
                        <p>Drag sites to reorder them. Use checkboxes to exclude sites from modules.</p>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th>Site Name</th>
                                    <th style="width: 80px;">Exclude</th>
                                </tr>
                            </thead>
                            <tbody id="hha-sortable-sites">
        `;

        category.sites.forEach((site, index) => {
            modalHtml += `
                <tr data-site-index="${index}">
                    <td class="hha-drag-handle" style="cursor: move;">
                        <span class="dashicons dashicons-menu"></span>
                    </td>
                    <td>${site.site_name}</td>
                    <td style="text-align: center;">
                        <input type="checkbox" class="hha-exclude-site"
                               data-site-index="${index}"
                               ${site.excluded ? 'checked' : ''}>
                    </td>
                </tr>
            `;
        });

        modalHtml += `
                            </tbody>
                        </table>
                    </div>
                    <div class="hha-modal-footer">
                        <button type="button" class="button button-primary hha-save-sites-order"
                                data-category-index="${categoryIndex}">
                            Save Site Order
                        </button>
                        <button type="button" class="button hha-modal-close-btn">Cancel</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#hha-sites-modal').fadeIn();

        // Make sites sortable
        $('#hha-sortable-sites').sortable({
            handle: '.hha-drag-handle'
        });

        // Exclude site checkbox
        $(document).on('change', '.hha-exclude-site', function() {
            let siteIndex = $(this).data('site-index');
            let currentSiteIndex = $('#hha-sortable-sites tr').index($(this).closest('tr'));
            if (category.sites[currentSiteIndex]) {
                category.sites[currentSiteIndex].excluded = $(this).is(':checked');
            }
        });
    }

    // Save sites order
    $(document).on('click', '.hha-save-sites-order', function() {
        let categoryIndex = $(this).data('category-index');
        let category = categoriesData[categoryIndex];

        let newSitesOrder = [];
        $('#hha-sortable-sites tr').each(function() {
            let index = $(this).data('site-index');
            newSitesOrder.push(category.sites[index]);
        });

        categoriesData[categoryIndex].sites = newSitesOrder;

        // Update the sites count in the main table
        let activeCount = newSitesOrder.filter(s => !s.excluded).length;
        $(`tr[data-category-index="${categoryIndex}"] td:eq(2)`).text(`${activeCount} / ${newSitesOrder.length}`);

        $('#hha-sites-modal').fadeOut(function() {
            $(this).remove();
        });
    });

    // Close modal
    $(document).on('click', '.hha-modal-close, .hha-modal-close-btn', function() {
        $('#hha-sites-modal').fadeOut(function() {
            $(this).remove();
        });
    });

    // Save category & site configuration
    $('#hha-save-category-sort').on('click', function() {
        let hotelId = $(this).data('hotel-id');
        let $button = $(this);
        let originalText = $button.text();

        $button.prop('disabled', true).text('Saving...');

        // Update order indexes
        updateCategoriesOrder();
        categoriesData.forEach((category, catIndex) => {
            category.order = catIndex;
            if (category.sites) {
                category.sites.forEach((site, siteIndex) => {
                    site.order = siteIndex;
                });
            }
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hha_save_category_sort',
                hotel_id: hotelId,
                categories_data: JSON.stringify(categoriesData),
                nonce: '<?php echo wp_create_nonce('hha_save_category_sort'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Configuration saved successfully!');
                    $button.prop('disabled', false).text(originalText);
                } else {
                    alert('Error: ' + response.data.message);
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('Failed to save configuration. Please try again.');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Task Types Management
    let taskTypesData = <?php echo !empty($task_types_data) ? json_encode($task_types_data) : '[]'; ?>;

    // Fetch task types from NewBook
    $('#hha-fetch-task-types, #hha-resync-task-types').on('click', function() {
        let hotelId = $(this).data('hotel-id');
        let isResync = $(this).attr('id') === 'hha-resync-task-types';
        let $button = $(this);
        let originalText = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin" style="margin-top: 3px;"></span> Loading...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hha_fetch_task_types',
                hotel_id: hotelId,
                is_resync: isResync,
                existing_data: isResync ? JSON.stringify(taskTypesData) : null,
                nonce: '<?php echo wp_create_nonce('hha_fetch_task_types'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    taskTypesData = response.data.task_types;
                    location.reload(); // Reload to show updated UI
                } else {
                    alert('Error: ' + response.data.message);
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Failed to fetch task types. Please try again.');
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Update preview when color changes
    $(document).on('change', '.hha-task-type-color', function() {
        let index = $(this).data('task-type-index');
        let color = $(this).val();
        let $preview = $('tr[data-task-type-index="' + index + '"] .hha-task-preview');
        $preview.css('background-color', color);

        if (taskTypesData[index]) {
            taskTypesData[index].color = color;
        }
    });

    // Update preview when icon changes
    $(document).on('change', '.hha-task-type-icon', function() {
        let index = $(this).data('task-type-index');
        let icon = $(this).val();
        let $preview = $('tr[data-task-type-index="' + index + '"] .hha-task-preview .material-icons');
        $preview.text(icon);

        if (taskTypesData[index]) {
            taskTypesData[index].icon = icon;
        }
    });

    // Save task types configuration
    $('#hha-save-task-types').on('click', function() {
        let hotelId = $(this).data('hotel-id');
        let $button = $(this);
        let originalText = $button.text();

        $button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hha_save_task_types',
                hotel_id: hotelId,
                task_types_data: JSON.stringify(taskTypesData),
                nonce: '<?php echo wp_create_nonce('hha_save_task_types'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Task types configuration saved successfully!');
                    $button.prop('disabled', false).text(originalText);
                } else {
                    alert('Error: ' + response.data.message);
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('Failed to save task types. Please try again.');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>

<!-- Load Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- Modal Styles -->
<style>
.hha-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.hha-modal-content {
    background-color: #fff;
    margin: 5% auto;
    border: 1px solid #ccc;
    width: 80%;
    max-width: 800px;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.hha-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    position: relative;
}

.hha-modal-header h2 {
    margin: 0;
    padding-right: 30px;
}

.hha-modal-close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.hha-modal-close:hover {
    color: #000;
}

.hha-modal-body {
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
}

.hha-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.hha-modal-footer .button {
    margin-left: 10px;
}

.hha-info-box {
    background: #f0f8ff;
    border: 1px solid #b8d4e8;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.hha-info-box ul {
    margin-bottom: 0;
}

.dashicons-spin {
    animation: rotation 2s infinite linear;
}

@keyframes rotation {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(359deg);
    }
}

#hha-sortable-categories .ui-sortable-helper {
    background: #f9f9f9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

#hha-sortable-sites .ui-sortable-helper {
    background: #f9f9f9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>
