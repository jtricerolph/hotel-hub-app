<?php
/**
 * Module Department Overview Page
 *
 * Displays list of modules in a specific department with links to their settings.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html($department_label); ?> Modules</h1>

    <?php if (empty($department_modules)): ?>
        <div class="notice notice-info">
            <p><?php _e('No modules installed for this department yet.', 'hotel-hub-app'); ?></p>
        </div>
    <?php else: ?>
        <div class="hha-modules-grid">
            <?php foreach ($department_modules as $module_id => $module): ?>
                <div class="hha-module-card">
                    <div class="hha-module-icon">
                        <span class="dashicons <?php echo esc_attr($module['icon']); ?>" style="color: <?php echo esc_attr($module['color']); ?>"></span>
                    </div>
                    <div class="hha-module-info">
                        <h2><?php echo esc_html($module['name']); ?></h2>
                        <p class="description"><?php echo esc_html($module['description']); ?></p>

                        <?php if (!empty($module['settings_pages'])): ?>
                            <div class="hha-module-actions">
                                <?php foreach ($module['settings_pages'] as $page): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page['slug'])); ?>" class="button">
                                        <?php echo esc_html($page['menu_title']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($module['integrations'])): ?>
                            <div class="hha-module-requirements">
                                <strong><?php _e('Required Integrations:', 'hotel-hub-app'); ?></strong>
                                <?php echo esc_html(implode(', ', array_map('ucfirst', $module['integrations']))); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($module['permissions'])): ?>
                            <div class="hha-module-permissions">
                                <strong><?php _e('Required Permissions:', 'hotel-hub-app'); ?></strong>
                                <ul>
                                    <?php foreach ($module['permissions'] as $permission): ?>
                                        <li><code><?php echo esc_html($permission); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .hha-modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .hha-module-card {
        background: #fff;
        border: 1px solid #dcdcdc;
        border-radius: 4px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.2s;
    }

    .hha-module-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .hha-module-icon {
        margin-bottom: 15px;
    }

    .hha-module-icon .dashicons {
        font-size: 48px;
        width: 48px;
        height: 48px;
    }

    .hha-module-info h2 {
        margin: 0 0 10px 0;
        font-size: 18px;
    }

    .hha-module-info .description {
        margin: 0 0 15px 0;
        color: #666;
    }

    .hha-module-actions {
        margin-bottom: 15px;
    }

    .hha-module-actions .button {
        margin-right: 8px;
        margin-bottom: 8px;
    }

    .hha-module-requirements,
    .hha-module-permissions {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #f0f0f0;
        font-size: 13px;
    }

    .hha-module-requirements strong,
    .hha-module-permissions strong {
        display: block;
        margin-bottom: 5px;
    }

    .hha-module-permissions ul {
        margin: 5px 0 0 20px;
    }

    .hha-module-permissions li {
        margin: 3px 0;
    }

    .hha-module-permissions code {
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 2px;
        font-size: 12px;
    }
</style>
