<?php
/**
 * Permissions Manager
 *
 * Registers and manages permissions with Workforce Authentication.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Permissions {

    /**
     * Constructor - Register hooks.
     */
    public function __construct() {
        add_action('wfa_register_permissions', array($this, 'register_permissions'));
    }

    /**
     * Register all Hotel Hub App permissions.
     *
     * @param WFA_Permissions $permissions_manager Permissions manager instance.
     */
    public function register_permissions($permissions_manager) {
        // Notes permissions
        $permissions_manager->register_permission(
            'hha_notes_view_all',
            'View All Notes',
            'Allow users to view all notes in the system',
            'Hotel Hub App'
        );

        $permissions_manager->register_permission(
            'hha_notes_create',
            'Create Notes',
            'Allow users to create new notes',
            'Hotel Hub App'
        );

        $permissions_manager->register_permission(
            'hha_notes_edit',
            'Edit Notes',
            'Allow users to edit existing notes',
            'Hotel Hub App'
        );

        $permissions_manager->register_permission(
            'hha_notes_delete',
            'Delete Notes',
            'Allow users to delete notes',
            'Hotel Hub App'
        );

        // Register individual note type permissions dynamically
        $this->register_note_type_permissions($permissions_manager);
    }

    /**
     * Register note type specific permissions.
     *
     * @param WFA_Permissions $permissions_manager Permissions manager instance.
     */
    private function register_note_type_permissions($permissions_manager) {
        // Get all hotels
        $hotels = hha()->hotels->get_all();

        if (empty($hotels)) {
            return;
        }

        // Track registered note types to avoid duplicates
        $registered_types = array();

        foreach ($hotels as $hotel) {
            // Get NewBook integration settings
            $integration = hha()->integrations->get_settings($hotel->id, 'newbook');

            if (!$integration || !isset($integration['note_types'])) {
                continue;
            }

            $note_types = $integration['note_types'];

            foreach ($note_types as $note_type) {
                $note_id = $note_type['id'];
                $note_name = $note_type['name'];

                // Skip if already registered
                if (isset($registered_types[$note_id])) {
                    continue;
                }

                // Register permission for this note type
                $permissions_manager->register_permission(
                    'hha_note_type_' . $note_id,
                    'View Note Type: ' . $note_name,
                    'Allow users to view notes of type: ' . $note_name,
                    'Hotel Hub App - Note Types'
                );

                $registered_types[$note_id] = true;
            }
        }
    }

    /**
     * Check if user can view all notes.
     *
     * @param int $user_id User ID (optional, defaults to current user).
     * @return bool
     */
    public static function can_view_all_notes($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        return function_exists('wfa_user_can') && wfa_user_can('hha_notes_view_all', $user_id);
    }

    /**
     * Check if user can create notes.
     *
     * @param int $user_id User ID (optional, defaults to current user).
     * @return bool
     */
    public static function can_create_notes($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        return function_exists('wfa_user_can') && wfa_user_can('hha_notes_create', $user_id);
    }

    /**
     * Check if user can edit notes.
     *
     * @param int $user_id User ID (optional, defaults to current user).
     * @return bool
     */
    public static function can_edit_notes($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        return function_exists('wfa_user_can') && wfa_user_can('hha_notes_edit', $user_id);
    }

    /**
     * Check if user can delete notes.
     *
     * @param int $user_id User ID (optional, defaults to current user).
     * @return bool
     */
    public static function can_delete_notes($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        return function_exists('wfa_user_can') && wfa_user_can('hha_notes_delete', $user_id);
    }

    /**
     * Check if user can view a specific note type.
     *
     * @param string|int $note_type_id Note type ID.
     * @param int        $user_id      User ID (optional, defaults to current user).
     * @return bool
     */
    public static function can_view_note_type($note_type_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // If user can view all notes, they can view this type
        if (self::can_view_all_notes($user_id)) {
            return true;
        }

        // Check specific note type permission
        return function_exists('wfa_user_can') && wfa_user_can('hha_note_type_' . $note_type_id, $user_id);
    }

    /**
     * Filter notes array by user permissions.
     *
     * @param array $notes   Array of notes.
     * @param int   $user_id User ID (optional, defaults to current user).
     * @return array Filtered notes array.
     */
    public static function filter_notes_by_permission($notes, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // If user can view all notes, return everything
        if (self::can_view_all_notes($user_id)) {
            return $notes;
        }

        // Filter notes by type permissions
        $filtered_notes = array();

        foreach ($notes as $note) {
            $note_type_id = isset($note['note_type_id']) ? $note['note_type_id'] : null;

            if ($note_type_id && self::can_view_note_type($note_type_id, $user_id)) {
                $filtered_notes[] = $note;
            }
        }

        return $filtered_notes;
    }

    /**
     * Get note type configuration with color and icon.
     *
     * @param string|int $note_type_id Note type ID.
     * @param int        $hotel_id     Hotel ID.
     * @return array|null Note type config or null if not found.
     */
    public static function get_note_type_config($note_type_id, $hotel_id) {
        $integration = hha()->integrations->get_settings($hotel_id, 'newbook');

        if (!$integration || !isset($integration['note_types'])) {
            return null;
        }

        foreach ($integration['note_types'] as $note_type) {
            if ($note_type['id'] == $note_type_id) {
                return $note_type;
            }
        }

        return null;
    }
}
