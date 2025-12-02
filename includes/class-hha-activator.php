<?php
/**
 * Plugin activation and deactivation handler.
 *
 * Creates database tables, sets default options, and manages plugin lifecycle.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Activator {

    /**
     * Plugin activation - create tables and set defaults.
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . HHA_TABLE_PREFIX;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create hotels table
        $sql_hotels = "CREATE TABLE {$table_prefix}hotels (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            location_id bigint(20) DEFAULT NULL COMMENT 'Links to workforce locations',
            name varchar(200) NOT NULL,
            slug varchar(100) NOT NULL,
            address text,
            phone varchar(50),
            website varchar(200),
            logo_id bigint(20) DEFAULT NULL COMMENT 'WordPress attachment ID',
            icon_id bigint(20) DEFAULT NULL COMMENT 'WordPress attachment ID',
            default_arrival_time varchar(5) DEFAULT '15:00' COMMENT 'Default arrival time in 24hr format (HH:MM)',
            default_departure_time varchar(5) DEFAULT '10:00' COMMENT 'Default departure time in 24hr format (HH:MM)',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY location_id (location_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        dbDelta($sql_hotels);

        // Create hotel integrations table
        $sql_integrations = "CREATE TABLE {$table_prefix}hotel_integrations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            hotel_id bigint(20) NOT NULL,
            integration_type varchar(50) NOT NULL COMMENT 'newbook, resos, epos',
            settings_json text NOT NULL COMMENT 'Encrypted JSON with credentials',
            is_active tinyint(1) DEFAULT 1,
            last_synced datetime DEFAULT NULL,
            last_sync_status varchar(20) DEFAULT NULL COMMENT 'success, error, pending',
            last_sync_message text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY hotel_id (hotel_id),
            KEY integration_type (integration_type)
        ) $charset_collate;";

        dbDelta($sql_integrations);

        // Set default options
        add_option('hha_version', HHA_VERSION);
        add_option('hha_app_page_id', '');
        add_option('hha_login_page_id', '');
        add_option('hha_theme_mode', 'light'); // light, dark, custom
        add_option('hha_theme_primary_color', '#2196f3');

        // Create app page if it doesn't exist
        self::create_app_page();

        // Add rewrite rules before flushing
        add_rewrite_rule('^hotel-hub-manifest\.json$', 'index.php?hha_manifest=1', 'top');
        add_rewrite_rule('^hotel-hub-sw\.js$', 'index.php?hha_sw=1', 'top');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation - cleanup.
     */
    public static function deactivate() {
        // Clear scheduled hooks if any
        wp_clear_scheduled_hook('hha_daily_cleanup');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Check if database upgrade is needed and run migrations.
     */
    public static function check_upgrade() {
        $installed_version = get_option('hha_version', '0.0.0');

        if (version_compare($installed_version, HHA_VERSION, '<')) {
            self::upgrade_database($installed_version);
            update_option('hha_version', HHA_VERSION);
        }
    }

    /**
     * Upgrade database schema for new versions.
     *
     * @param string $from_version The version upgrading from.
     */
    private static function upgrade_database($from_version) {
        global $wpdb;
        $table_prefix = $wpdb->prefix . HHA_TABLE_PREFIX;

        // Upgrade to 1.0.4: Add default_departure_time column
        if (version_compare($from_version, '1.0.4', '<')) {
            $wpdb->query(
                "ALTER TABLE {$table_prefix}hotels
                ADD COLUMN default_departure_time varchar(5) DEFAULT '10:00'
                COMMENT 'Default departure time in 24hr format (HH:MM)'
                AFTER default_arrival_time"
            );
        }
    }

    /**
     * Create the main app page.
     */
    private static function create_app_page() {
        $page_id = get_option('hha_app_page_id');

        // Check if page already exists
        if ($page_id && get_post($page_id)) {
            return;
        }

        // Create new page
        $page_data = array(
            'post_title'    => 'Hotel Hub',
            'post_content'  => '[hotel_hub_app]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed'
        );

        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {
            update_option('hha_app_page_id', $page_id);
        }
    }
}
