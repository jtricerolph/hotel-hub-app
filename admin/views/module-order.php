<?php
/**
 * Admin view - Module Sort Order page.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=hotel-hub-settings')); ?>" class="page-title-action" style="margin-right: 10px;">
            &larr; Back to Settings
        </a>
        Module Sort Order
    </h1>

    <p>Drag and drop modules to customize their display order in the sidebar navigation. Changes are saved when you click "Save Module Order".</p>

    <?php if (empty($modules)) : ?>
        <div class="notice notice-info">
            <p>No modules are currently registered.</p>
        </div>
    <?php else : ?>
        <div class="hha-info-box" style="background: #f0f8ff; border: 1px solid #b8d4e8; border-radius: 4px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Instructions:</strong></p>
            <ul style="margin: 10px 0 0 20px;">
                <li>Drag modules using the handle on the left to reorder them</li>
                <li>Modules within the same department will be displayed in your custom order</li>
                <li>Click "Reset to Default Order" to restore the original module order</li>
            </ul>
        </div>

        <table class="wp-list-table widefat fixed striped" id="hha-modules-table">
            <thead>
                <tr>
                    <th style="width: 30px;"></th>
                    <th>Module</th>
                    <th style="width: 150px;">Department</th>
                    <th style="width: 100px;">Default Order</th>
                </tr>
            </thead>
            <tbody id="hha-sortable-modules">
                <?php foreach ($modules as $module_id => $module) : ?>
                    <tr data-module-id="<?php echo esc_attr($module_id); ?>">
                        <td class="hha-drag-handle" style="cursor: move;">
                            <span class="dashicons dashicons-menu"></span>
                        </td>
                        <td>
                            <span class="material-symbols-outlined" style="color: <?php echo esc_attr($module['color']); ?>; vertical-align: middle; margin-right: 8px;"><?php echo esc_html($module['icon']); ?></span>
                            <strong><?php echo esc_html($module['name']); ?></strong>
                            <?php if (!empty($module['description'])) : ?>
                                <br><small style="color: #666;"><?php echo esc_html($module['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="hha-department-badge" style="background: #e0e0e0; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                <?php echo esc_html(hha()->modules->get_department_label($module['department'])); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php echo esc_html($module['order']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit">
            <button type="button" id="hha-save-module-order" class="button button-primary">
                Save Module Order
            </button>
            <button type="button" id="hha-reset-module-order" class="button">
                Reset to Default Order
            </button>
            <span id="hha-save-status" style="margin-left: 10px; display: none;"></span>
        </p>
    <?php endif; ?>
</div>

<style>
#hha-sortable-modules .ui-sortable-helper {
    background: #f9f9f9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

#hha-sortable-modules tr {
    background: #fff;
}

.hha-drag-handle:hover {
    background: #f0f0f0;
}

.ui-sortable-placeholder {
    background: #fafafa;
    border: 2px dashed #ccc;
    visibility: visible !important;
    height: 50px;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-size: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Make modules sortable
    $('#hha-sortable-modules').sortable({
        handle: '.hha-drag-handle',
        placeholder: 'ui-sortable-placeholder',
        helper: function(e, tr) {
            // Preserve cell widths during drag
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        }
    });

    // Save module order
    $('#hha-save-module-order').on('click', function() {
        saveModuleOrder();
    });

    function saveModuleOrder() {
        var moduleOrder = [];
        $('#hha-sortable-modules tr').each(function() {
            moduleOrder.push($(this).data('module-id'));
        });

        var $button = $('#hha-save-module-order');
        var $status = $('#hha-save-status');
        var originalText = $button.text();

        $button.prop('disabled', true).text('Saving...');
        $status.hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hha_save_module_order',
                module_order: JSON.stringify(moduleOrder),
                nonce: '<?php echo wp_create_nonce('hha_save_module_order'); ?>'
            },
            success: function(response) {
                $button.prop('disabled', false).text(originalText);
                if (response.success) {
                    $status.text('Saved!').css('color', '#46b450').show();
                    setTimeout(function() { $status.fadeOut(); }, 2000);
                } else {
                    $status.text('Error: ' + response.data.message).css('color', '#dc3232').show();
                }
            },
            error: function() {
                $button.prop('disabled', false).text(originalText);
                $status.text('Failed to save. Please try again.').css('color', '#dc3232').show();
            }
        });
    }

    // Reset to default order
    $('#hha-reset-module-order').on('click', function() {
        if (!confirm('Reset module order to default? This will remove any custom ordering.')) {
            return;
        }

        var $button = $(this);
        var $status = $('#hha-save-status');
        var originalText = $button.text();

        $button.prop('disabled', true).text('Resetting...');
        $status.hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hha_reset_module_order',
                nonce: '<?php echo wp_create_nonce('hha_reset_module_order'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $button.prop('disabled', false).text(originalText);
                    $status.text('Error: ' + response.data.message).css('color', '#dc3232').show();
                }
            },
            error: function() {
                $button.prop('disabled', false).text(originalText);
                $status.text('Failed to reset. Please try again.').css('color', '#dc3232').show();
            }
        });
    });
});
</script>
