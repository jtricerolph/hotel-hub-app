<?php
/**
 * Admin view - Hotels list.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Hotels</h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=hotel-hub-edit')); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">

    <?php if (empty($hotels)) : ?>
        <div class="notice notice-info">
            <p>No hotels found. <a href="<?php echo esc_url(admin_url('admin.php?page=hotel-hub-edit')); ?>">Add your first hotel</a> to get started.</p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">Logo</th>
                    <th>Name</th>
                    <th>Workforce Location</th>
                    <th>Contact</th>
                    <th>Integrations</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hotels as $hotel) : ?>
                    <?php
                    $logo_url = hha()->hotels->get_logo_url($hotel->id, 'thumbnail');
                    $edit_url = admin_url('admin.php?page=hotel-hub-edit&hotel_id=' . $hotel->id);
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?page=hotel-hub&action=delete&hotel_id=' . $hotel->id),
                        'delete-hotel'
                    );

                    // Get integrations
                    $integrations = hha()->integrations->get_by_hotel($hotel->id);
                    $integration_badges = array();

                    foreach ($integrations as $integration) {
                        if ($integration->is_active) {
                            $integration_badges[] = '<span class="hha-badge hha-badge-' . esc_attr($integration->integration_type) . '">' .
                                                   esc_html(strtoupper($integration->integration_type)) .
                                                   '</span>';
                        }
                    }

                    // Get location name if available
                    $location_name = '';
                    if ($hotel->location_id) {
                        $location_name = HHA_Admin::get_workforce_location_name($hotel->location_id);
                    }
                    ?>
                    <tr>
                        <td>
                            <?php if ($logo_url) : ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($hotel->name); ?>" style="max-width: 50px; height: auto;">
                            <?php else : ?>
                                <span class="dashicons dashicons-building" style="font-size: 40px; color: #ccc;"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($hotel->name); ?></a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($location_name); ?></td>
                        <td>
                            <?php if ($hotel->phone) : ?>
                                <div><?php echo esc_html($hotel->phone); ?></div>
                            <?php endif; ?>
                            <?php if ($hotel->website) : ?>
                                <div><a href="<?php echo esc_url($hotel->website); ?>" target="_blank">Website</a></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($integration_badges)) {
                                echo implode(' ', $integration_badges);
                            } else {
                                echo '<span style="color: #999;">None</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($hotel->is_active) : ?>
                                <span class="hha-badge hha-badge-success">Active</span>
                            <?php else : ?>
                                <span class="hha-badge hha-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>">Edit</a> |
                            <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Are you sure you want to delete this hotel?');" style="color: #a00;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.hha-badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    border-radius: 3px;
    text-transform: uppercase;
    margin: 2px;
}

.hha-badge-success {
    background: #46b450;
    color: #fff;
}

.hha-badge-inactive {
    background: #ddd;
    color: #666;
}

.hha-badge-newbook {
    background: #0073aa;
    color: #fff;
}

.hha-badge-resos {
    background: #826eb4;
    color: #fff;
}

.hha-badge-epos {
    background: #d54e21;
    color: #fff;
}
</style>
