<?php
/**
 * Reports Overview Page
 *
 * Displays all available reports grouped by module.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Reports', 'hotel-hub-app'); ?></h1>

    <?php if (empty($reports)): ?>
        <div class="notice notice-info">
            <p><?php _e('No reports are currently available. Install and configure modules to enable reports.', 'hotel-hub-app'); ?></p>
        </div>
    <?php else: ?>
        <p><?php _e('Select a report below to view data and analytics.', 'hotel-hub-app'); ?></p>

        <?php foreach ($reports_by_module as $module_key => $module_reports): ?>
            <?php if (empty($module_reports)) continue; ?>

            <div class="hha-reports-module-section" style="margin-bottom: 30px;">
                <h2 style="margin-bottom: 15px;">
                    <?php
                    if ($module_key === 'core') {
                        _e('Core Reports', 'hotel-hub-app');
                    } else {
                        // Get module title
                        $modules = hha()->modules->get_modules();
                        echo isset($modules[$module_key]) ? esc_html($modules[$module_key]['title']) : esc_html(ucwords(str_replace('-', ' ', $module_key)));
                    }
                    ?>
                </h2>

                <div class="hha-reports-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($module_reports as $report_id => $report): ?>
                        <div class="hha-report-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); transition: box-shadow 0.2s;">
                            <div class="hha-report-icon" style="margin-bottom: 15px; font-size: 32px; color: #2271b1;">
                                <?php
                                if (!empty($report['icon'])) {
                                    if (strpos($report['icon'], 'dashicons') !== false) {
                                        echo '<span class="dashicons ' . esc_attr($report['icon']) . '"></span>';
                                    } else {
                                        echo wp_kses_post($report['icon']);
                                    }
                                }
                                ?>
                            </div>

                            <h3 style="margin: 0 0 10px 0; font-size: 16px;">
                                <?php echo esc_html($report['title']); ?>
                            </h3>

                            <p style="color: #646970; font-size: 13px; margin: 0 0 15px 0;">
                                <?php
                                if (!empty($report['description'])) {
                                    echo esc_html($report['description']);
                                } else {
                                    _e('View detailed report data and analytics.', 'hotel-hub-app');
                                }
                                ?>
                            </p>

                            <a href="<?php echo esc_url(admin_url('admin.php?page=hotel-hub-reports&report=' . urlencode($report_id))); ?>"
                               class="button button-primary">
                                <?php _e('View Report', 'hotel-hub-app'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.hha-report-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
