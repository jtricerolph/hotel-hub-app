<?php
/**
 * NewBook API Client
 *
 * Handles all communication with NewBook API.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_NewBook_API {

    /**
     * NewBook API base URL.
     */
    private $api_base_url = 'https://api.newbook.cloud/rest/';

    /**
     * API credentials.
     */
    private $username;
    private $password;
    private $api_key;
    private $region;

    /**
     * Constructor.
     *
     * @param array $credentials API credentials (username, password, api_key, region).
     */
    public function __construct($credentials = array()) {
        $this->username = isset($credentials['username']) ? $credentials['username'] : '';
        $this->password = isset($credentials['password']) ? $credentials['password'] : '';
        $this->api_key = isset($credentials['api_key']) ? $credentials['api_key'] : '';
        $this->region = isset($credentials['region']) ? $credentials['region'] : 'au';
    }

    /**
     * Call NewBook API endpoint.
     *
     * @param string $endpoint API endpoint (e.g., 'sites_list', 'bookings_list').
     * @param array  $params   Additional request parameters.
     * @return array Response array with 'success', 'data', and 'message' keys.
     */
    public function call_api($endpoint, $params = array()) {
        // Log API request if logging is enabled
        $this->log_api_request($endpoint, $params);

        // Validate credentials
        if (empty($this->username) || empty($this->password) || empty($this->api_key)) {
            return array(
                'success' => false,
                'data' => array(),
                'message' => 'API credentials not configured'
            );
        }

        // Add required fields to request body
        $data = array_merge($params, array(
            'api_key' => $this->api_key,
            'region' => $this->region
        ));

        // Build request
        $url = $this->api_base_url . $endpoint;
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password)
            ),
            'body' => json_encode($data)
        );

        // Make request
        $response = wp_remote_post($url, $args);

        // Handle WP_Error
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'data' => array(),
                'message' => $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        // Handle non-200 response
        if ($status_code !== 200) {
            $error_message = "API returned HTTP {$status_code}";

            // Try to extract error message from response
            if ($response_data && is_array($response_data)) {
                if (isset($response_data['message'])) {
                    $error_message = $response_data['message'];
                } elseif (isset($response_data['error'])) {
                    $error_message = $response_data['error'];
                }
            }

            return array(
                'success' => false,
                'data' => array(),
                'message' => $error_message
            );
        }

        // Parse response
        if (!$response_data || !is_array($response_data)) {
            return array(
                'success' => false,
                'data' => array(),
                'message' => 'Invalid API response format'
            );
        }

        // Return NewBook response format
        return array(
            'success' => isset($response_data['success']) ? $response_data['success'] : true,
            'data' => isset($response_data['data']) ? $response_data['data'] : $response_data,
            'message' => isset($response_data['message']) ? $response_data['message'] : ''
        );
    }

    /**
     * Test API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        // Use sites_list as simple test endpoint
        $response = $this->call_api('sites_list');

        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Connection successful - API credentials verified'
            );
        }

        return array(
            'success' => false,
            'message' => 'Connection failed: ' . $response['message']
        );
    }

    /**
     * Get sites list.
     *
     * @param bool $force_refresh Force fresh data from API.
     * @return array Response array.
     */
    public function get_sites($force_refresh = false) {
        return $this->call_api('sites_list', array(
            'force_refresh' => $force_refresh
        ));
    }

    /**
     * Get bookings list.
     *
     * @param string $period_from Start date/time (YYYY-MM-DD HH:MM:SS).
     * @param string $period_to   End date/time (YYYY-MM-DD HH:MM:SS).
     * @param string $list_type   Filter type: 'staying', 'placed', 'cancelled', 'all'.
     * @param bool   $force_refresh Force fresh data from API.
     * @return array Response array.
     */
    public function get_bookings($period_from, $period_to, $list_type = 'staying', $force_refresh = false) {
        return $this->call_api('bookings_list', array(
            'period_from' => $period_from,
            'period_to' => $period_to,
            'list_type' => $list_type,
            'force_refresh' => $force_refresh
        ));
    }

    /**
     * Get single booking by ID.
     *
     * @param int  $booking_id    NewBook booking ID.
     * @param bool $force_refresh Force fresh data from API.
     * @return array Response array.
     */
    public function get_booking($booking_id, $force_refresh = false) {
        return $this->call_api('bookings_get', array(
            'booking_id' => $booking_id,
            'force_refresh' => $force_refresh
        ));
    }

    /**
     * Get task types list.
     *
     * @param bool $force_refresh Force fresh data from API.
     * @return array Response array.
     */
    public function get_task_types($force_refresh = false) {
        return $this->call_api('tasks_types_list', array(
            'force_refresh' => $force_refresh
        ));
    }

    /**
     * Get tasks list.
     *
     * @param string $period_from    Start date (YYYY-MM-DD).
     * @param string $period_to      End date (YYYY-MM-DD).
     * @param array  $task_type      Task type IDs array (e.g., [-1, -2]).
     * @param bool   $show_uncomplete Show uncompleted tasks only.
     * @param string $created_when   Filter by creation date (YYYY-MM-DD).
     * @param bool   $force_refresh  Force fresh data from API.
     * @return array Response array.
     */
    public function get_tasks($period_from, $period_to, $task_type = array(), $show_uncomplete = true, $created_when = null, $force_refresh = false) {
        $params = array(
            'period_from' => $period_from,
            'period_to' => $period_to,
            'force_refresh' => $force_refresh
        );

        if (!empty($task_type)) {
            $params['task_type'] = $task_type;
        }

        if ($show_uncomplete) {
            $params['show_uncomplete'] = 'true';
        }

        if ($created_when) {
            $params['created_when'] = $created_when;
        }

        return $this->call_api('tasks_list', $params);
    }

    /**
     * Log API request to dedicated log file.
     *
     * @param string $endpoint API endpoint.
     * @param array  $params   Request parameters.
     */
    private function log_api_request($endpoint, $params) {
        // Check if logging is enabled
        if (!get_option('hha_api_logging_enabled', false)) {
            return;
        }

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/hotel-hub-logs';
        $log_file = $log_dir . '/newbook-api.log';

        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Prepare log entry
        $timestamp = current_time('Y-m-d H:i:s');
        $params_json = json_encode($params);
        $log_entry = sprintf("%s | %s | %s\n", $timestamp, $endpoint, $params_json);

        // Write to log file
        // Using error_log with destination 3 to append to file
        error_log($log_entry, 3, $log_file);
    }
}
