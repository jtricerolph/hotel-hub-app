<?php
/**
 * Integration management - NewBook and ResOS integrations.
 *
 * Handles integration credentials (encrypted), connection testing, and sync tracking.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Integrations {

    /**
     * Database table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Integration types.
     */
    const TYPE_NEWBOOK = 'newbook';
    const TYPE_RESOS   = 'resos';
    const TYPE_EPOS    = 'epos';

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . HHA_TABLE_PREFIX . 'hotel_integrations';
    }

    /**
     * Get all integrations for a hotel.
     *
     * @param int $hotel_id The hotel ID.
     * @return array Array of integration objects.
     */
    public function get_by_hotel($hotel_id) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE hotel_id = %d ORDER BY integration_type ASC",
                $hotel_id
            )
        );

        return $results ? $results : array();
    }

    /**
     * Get specific integration for a hotel.
     *
     * @param int    $hotel_id        The hotel ID.
     * @param string $integration_type Integration type (newbook, resos, epos).
     * @return object|null Integration object or null if not found.
     */
    public function get($hotel_id, $integration_type) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE hotel_id = %d AND integration_type = %s",
                $hotel_id,
                $integration_type
            )
        );
    }

    /**
     * Get integration by ID.
     *
     * @param int $integration_id The integration ID.
     * @return object|null Integration object or null if not found.
     */
    public function get_by_id($integration_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $integration_id
            )
        );
    }

    /**
     * Create or update an integration.
     *
     * @param int    $hotel_id        The hotel ID.
     * @param string $integration_type Integration type.
     * @param array  $settings        Settings array (will be encrypted).
     * @param bool   $is_active       Whether integration is active.
     * @return int|false Integration ID on success, false on failure.
     */
    public function save($hotel_id, $integration_type, $settings, $is_active = true) {
        global $wpdb;

        // Encrypt settings
        $encrypted_settings = HHA_Encryption::encrypt_json($settings);

        if ($encrypted_settings === false) {
            error_log('HHA_Integrations: Failed to encrypt settings');
            return false;
        }

        // Check if integration already exists
        $existing = $this->get($hotel_id, $integration_type);

        if ($existing) {
            // Update existing integration
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'settings_json' => $encrypted_settings,
                    'is_active'     => (int) $is_active,
                    'updated_at'    => current_time('mysql'),
                ),
                array(
                    'hotel_id'        => $hotel_id,
                    'integration_type' => $integration_type,
                ),
                array('%s', '%d', '%s'),
                array('%d', '%s')
            );

            if ($result === false) {
                error_log('HHA_Integrations: Failed to update integration - ' . $wpdb->last_error);
                return false;
            }

            return $existing->id;

        } else {
            // Create new integration
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'hotel_id'        => $hotel_id,
                    'integration_type' => $integration_type,
                    'settings_json'   => $encrypted_settings,
                    'is_active'       => (int) $is_active,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%d', '%s', '%s')
            );

            if ($result === false) {
                error_log('HHA_Integrations: Failed to create integration - ' . $wpdb->last_error);
                return false;
            }

            return $wpdb->insert_id;
        }
    }

    /**
     * Get decrypted settings for an integration.
     *
     * @param int    $hotel_id        The hotel ID.
     * @param string $integration_type Integration type.
     * @return array|false Settings array or false on failure.
     */
    public function get_settings($hotel_id, $integration_type) {
        $integration = $this->get($hotel_id, $integration_type);

        if (!$integration || empty($integration->settings_json)) {
            return false;
        }

        return HHA_Encryption::decrypt_json($integration->settings_json);
    }

    /**
     * Delete an integration.
     *
     * @param int    $hotel_id        The hotel ID.
     * @param string $integration_type Integration type.
     * @return bool True on success, false on failure.
     */
    public function delete($hotel_id, $integration_type) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array(
                'hotel_id'        => $hotel_id,
                'integration_type' => $integration_type,
            ),
            array('%d', '%s')
        );

        if ($result === false) {
            error_log('HHA_Integrations: Failed to delete integration - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Update sync status for an integration.
     *
     * @param int    $hotel_id        The hotel ID.
     * @param string $integration_type Integration type.
     * @param string $status          Sync status (success, error, pending).
     * @param string $message         Sync message.
     * @return bool True on success, false on failure.
     */
    public function update_sync_status($hotel_id, $integration_type, $status, $message = '') {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array(
                'last_synced'       => current_time('mysql'),
                'last_sync_status'  => $status,
                'last_sync_message' => $message,
                'updated_at'        => current_time('mysql'),
            ),
            array(
                'hotel_id'        => $hotel_id,
                'integration_type' => $integration_type,
            ),
            array('%s', '%s', '%s', '%s'),
            array('%d', '%s')
        );

        if ($result === false) {
            error_log('HHA_Integrations: Failed to update sync status - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Test NewBook connection.
     *
     * @param array $settings NewBook settings (username, password, api_key, region).
     * @return array Result array with 'success' and 'message' keys.
     */
    public function test_newbook_connection($settings) {
        // Validate required settings
        if (empty($settings['username']) || empty($settings['password']) || empty($settings['api_key'])) {
            return array(
                'success' => false,
                'message' => 'Missing required credentials (username, password, api_key)',
            );
        }

        // Default to EU region
        $region = isset($settings['region']) ? $settings['region'] : 'eu';

        // NewBook API endpoints by region
        $endpoints = array(
            'eu' => 'https://api-eu.newbook.cloud',
            'us' => 'https://api-us.newbook.cloud',
            'au' => 'https://api-au.newbook.cloud',
        );

        $base_url = isset($endpoints[$region]) ? $endpoints[$region] : $endpoints['eu'];

        // Test API connection with simple request
        $response = wp_remote_get(
            $base_url . '/api/v1/properties',
            array(
                'timeout' => 10,
                'headers' => array(
                    'X-API-Key'    => $settings['api_key'],
                    'Content-Type' => 'application/json',
                ),
            )
        );

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'Connection successful',
            );
        } elseif ($status_code === 401 || $status_code === 403) {
            return array(
                'success' => false,
                'message' => 'Authentication failed - check credentials',
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Connection failed with status code: ' . $status_code,
            );
        }
    }

    /**
     * Test ResOS connection.
     *
     * @param array $settings ResOS settings (api_key).
     * @return array Result array with 'success' and 'message' keys.
     */
    public function test_resos_connection($settings) {
        // Validate required settings
        if (empty($settings['api_key'])) {
            return array(
                'success' => false,
                'message' => 'Missing required API key',
            );
        }

        // ResOS API endpoint
        $base_url = 'https://api.resos.com';

        // Test API connection
        $response = wp_remote_get(
            $base_url . '/v2/venues',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $settings['api_key'],
                    'Content-Type'  => 'application/json',
                ),
            )
        );

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'Connection successful',
            );
        } elseif ($status_code === 401 || $status_code === 403) {
            return array(
                'success' => false,
                'message' => 'Authentication failed - check API key',
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Connection failed with status code: ' . $status_code,
            );
        }
    }

    /**
     * Get NewBook API credentials for a hotel.
     *
     * @param int $hotel_id The hotel ID.
     * @return array|false Credentials array or false if not configured.
     */
    public function get_newbook_credentials($hotel_id) {
        $settings = $this->get_settings($hotel_id, self::TYPE_NEWBOOK);

        if (!$settings || empty($settings['api_key'])) {
            return false;
        }

        // Add default region if not set
        if (!isset($settings['region'])) {
            $settings['region'] = 'eu';
        }

        return $settings;
    }

    /**
     * Get ResOS API credentials for a hotel.
     *
     * @param int $hotel_id The hotel ID.
     * @return array|false Credentials array or false if not configured.
     */
    public function get_resos_credentials($hotel_id) {
        $settings = $this->get_settings($hotel_id, self::TYPE_RESOS);

        if (!$settings || empty($settings['api_key'])) {
            return false;
        }

        return $settings;
    }

    /**
     * Check if a hotel has a specific integration configured and active.
     *
     * @param int    $hotel_id        The hotel ID.
     * @param string $integration_type Integration type.
     * @return bool True if configured and active, false otherwise.
     */
    public function is_active($hotel_id, $integration_type) {
        $integration = $this->get($hotel_id, $integration_type);

        return $integration && $integration->is_active == 1;
    }
}
