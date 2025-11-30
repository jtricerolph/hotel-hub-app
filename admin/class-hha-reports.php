<?php
/**
 * Reports Manager - Handles report registration and display
 *
 * Provides infrastructure for modules to register their own reports.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Reports {

    /**
     * Registered reports.
     *
     * @var array
     */
    private $reports = array();

    /**
     * Constructor.
     */
    public function __construct() {
        // Allow modules to register reports
        add_action('init', array($this, 'init_reports'), 20);
    }

    /**
     * Initialize reports - trigger registration hook.
     */
    public function init_reports() {
        /**
         * Fires when reports can be registered.
         *
         * Modules should hook into this action to register their reports.
         *
         * @param HHA_Reports $this The reports manager instance.
         */
        do_action('hha_register_reports', $this);
    }

    /**
     * Register a report.
     *
     * @param string $id       Unique report ID (e.g., 'hhdl-task-completions').
     * @param array  $args     Report configuration.
     *                         - title: Report title (required)
     *                         - callback: Render callback function (required)
     *                         - capability: Required capability (default: 'view_reports')
     *                         - module: Module ID this report belongs to (optional)
     *                         - icon: Icon class/HTML (optional)
     * @return bool True on success, false on failure.
     */
    public function register($id, $args) {
        // Validate required fields
        if (empty($args['title']) || empty($args['callback'])) {
            return false;
        }

        // Set defaults
        $defaults = array(
            'capability' => 'view_reports',
            'module' => '',
            'icon' => 'dashicons-chart-bar'
        );

        $args = wp_parse_args($args, $defaults);

        // Store report
        $this->reports[$id] = $args;

        return true;
    }

    /**
     * Get all registered reports.
     *
     * @return array Registered reports.
     */
    public function get_reports() {
        return $this->reports;
    }

    /**
     * Get a specific report by ID.
     *
     * @param string $id Report ID.
     * @return array|null Report configuration or null if not found.
     */
    public function get_report($id) {
        return isset($this->reports[$id]) ? $this->reports[$id] : null;
    }

    /**
     * Check if user can view reports.
     *
     * @param string $report_id Optional specific report ID to check.
     * @return bool True if user can view, false otherwise.
     */
    public function user_can_view($report_id = null) {
        // Check general reports capability
        if (!current_user_can('manage_options')) {
            // Check if Workforce Authentication is available
            if (function_exists('wfa_user_can')) {
                if (!wfa_user_can('view_reports')) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // If specific report, check its capability
        if ($report_id) {
            $report = $this->get_report($report_id);
            if ($report && !current_user_can($report['capability'])) {
                if (function_exists('wfa_user_can')) {
                    return wfa_user_can($report['capability']);
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Render reports list page.
     */
    public function render_reports_page() {
        if (!$this->user_can_view()) {
            wp_die(__('You do not have permission to access reports.', 'hotel-hub-app'));
        }

        $reports = $this->get_reports();

        // Group reports by module
        $reports_by_module = array();
        $reports_by_module['core'] = array();

        foreach ($reports as $id => $report) {
            $module = !empty($report['module']) ? $report['module'] : 'core';
            if (!isset($reports_by_module[$module])) {
                $reports_by_module[$module] = array();
            }
            $reports_by_module[$module][$id] = $report;
        }

        // Load template
        include HHA_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * Render a specific report.
     *
     * @param string $report_id Report ID to render.
     */
    public function render_report($report_id) {
        $report = $this->get_report($report_id);

        if (!$report) {
            wp_die(__('Report not found.', 'hotel-hub-app'));
        }

        if (!$this->user_can_view($report_id)) {
            wp_die(__('You do not have permission to view this report.', 'hotel-hub-app'));
        }

        // Call the report's render callback
        if (is_callable($report['callback'])) {
            call_user_func($report['callback']);
        } else {
            echo '<div class="notice notice-error"><p>' . __('Report callback is not callable.', 'hotel-hub-app') . '</p></div>';
        }
    }
}
