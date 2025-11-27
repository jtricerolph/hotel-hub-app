<?php
/**
 * Hotel management - CRUD operations for hotels.
 *
 * Handles hotel creation, updates, image processing, and workforce location linking.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Hotels {

    /**
     * Database table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . HHA_TABLE_PREFIX . 'hotels';
    }

    /**
     * Get all hotels.
     *
     * @param bool $active_only Whether to return only active hotels.
     * @return array Array of hotel objects.
     */
    public function get_all($active_only = false) {
        global $wpdb;

        $where = $active_only ? 'WHERE is_active = 1' : '';

        $results = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY name ASC"
        );

        return $results ? $results : array();
    }

    /**
     * Get active hotels only.
     *
     * @return array Array of active hotel objects.
     */
    public function get_active() {
        return $this->get_all(true);
    }

    /**
     * Get hotel by ID.
     *
     * @param int $hotel_id The hotel ID.
     * @return object|null Hotel object or null if not found.
     */
    public function get($hotel_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $hotel_id
            )
        );
    }

    /**
     * Get hotel by slug.
     *
     * @param string $slug The hotel slug.
     * @return object|null Hotel object or null if not found.
     */
    public function get_by_slug($slug) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE slug = %s",
                $slug
            )
        );
    }

    /**
     * Get hotels by workforce location ID.
     *
     * @param int $location_id The workforce location ID.
     * @return array Array of hotel objects.
     */
    public function get_by_location($location_id) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE location_id = %d ORDER BY name ASC",
                $location_id
            )
        );

        return $results ? $results : array();
    }

    /**
     * Create a new hotel.
     *
     * @param array $data Hotel data.
     * @return int|false Hotel ID on success, false on failure.
     */
    public function create($data) {
        global $wpdb;

        // Generate slug from name
        $slug = $this->generate_unique_slug($data['name']);

        // Prepare hotel data
        $hotel_data = array(
            'location_id' => isset($data['location_id']) ? absint($data['location_id']) : null,
            'name'        => sanitize_text_field($data['name']),
            'slug'        => $slug,
            'address'     => isset($data['address']) ? sanitize_textarea_field($data['address']) : '',
            'phone'       => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'website'     => isset($data['website']) ? esc_url_raw($data['website']) : '',
            'logo_id'     => isset($data['logo_id']) ? absint($data['logo_id']) : null,
            'icon_id'     => isset($data['icon_id']) ? absint($data['icon_id']) : null,
            'is_active'   => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        );

        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            $hotel_data,
            array(
                '%d', // location_id
                '%s', // name
                '%s', // slug
                '%s', // address
                '%s', // phone
                '%s', // website
                '%d', // logo_id
                '%d', // icon_id
                '%d', // is_active
                '%s', // created_at
                '%s', // updated_at
            )
        );

        if ($result === false) {
            error_log('HHA_Hotels: Failed to create hotel - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing hotel.
     *
     * @param int   $hotel_id The hotel ID.
     * @param array $data     Hotel data to update.
     * @return bool True on success, false on failure.
     */
    public function update($hotel_id, $data) {
        global $wpdb;

        // Prepare update data
        $update_data = array(
            'updated_at' => current_time('mysql'),
        );

        // Only update provided fields
        if (isset($data['location_id'])) {
            $update_data['location_id'] = absint($data['location_id']);
        }

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);

            // Regenerate slug if name changed
            $current_hotel = $this->get($hotel_id);
            if ($current_hotel && $current_hotel->name !== $data['name']) {
                $update_data['slug'] = $this->generate_unique_slug($data['name'], $hotel_id);
            }
        }

        if (isset($data['address'])) {
            $update_data['address'] = sanitize_textarea_field($data['address']);
        }

        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
        }

        if (isset($data['website'])) {
            $update_data['website'] = esc_url_raw($data['website']);
        }

        if (isset($data['logo_id'])) {
            $update_data['logo_id'] = absint($data['logo_id']);
        }

        if (isset($data['icon_id'])) {
            $update_data['icon_id'] = absint($data['icon_id']);
        }

        if (isset($data['is_active'])) {
            $update_data['is_active'] = (int) $data['is_active'];
        }

        // Update in database
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $hotel_id),
            null, // Let wpdb determine format
            array('%d')
        );

        if ($result === false) {
            error_log('HHA_Hotels: Failed to update hotel - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Delete a hotel (soft delete by setting is_active to 0).
     *
     * @param int $hotel_id The hotel ID.
     * @return bool True on success, false on failure.
     */
    public function delete($hotel_id) {
        return $this->update($hotel_id, array('is_active' => 0));
    }

    /**
     * Permanently delete a hotel from database.
     *
     * @param int $hotel_id The hotel ID.
     * @return bool True on success, false on failure.
     */
    public function delete_permanently($hotel_id) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $hotel_id),
            array('%d')
        );

        if ($result === false) {
            error_log('HHA_Hotels: Failed to delete hotel permanently - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Generate a unique slug from hotel name.
     *
     * @param string $name      The hotel name.
     * @param int    $exclude_id Hotel ID to exclude from uniqueness check.
     * @return string Unique slug.
     */
    private function generate_unique_slug($name, $exclude_id = 0) {
        global $wpdb;

        // Generate base slug
        $slug = sanitize_title($name);

        // Check if slug exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s AND id != %d",
                $slug,
                $exclude_id
            )
        );

        // If slug exists, append number
        if ($existing > 0) {
            $suffix = 2;
            $new_slug = $slug . '-' . $suffix;

            while ($wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s AND id != %d",
                    $new_slug,
                    $exclude_id
                )
            ) > 0) {
                $suffix++;
                $new_slug = $slug . '-' . $suffix;
            }

            $slug = $new_slug;
        }

        return $slug;
    }

    /**
     * Process uploaded hotel logo (resize to 500x200px).
     *
     * @param int $attachment_id The attachment ID.
     * @return bool True on success, false on failure.
     */
    public function process_logo($attachment_id) {
        return $this->resize_image($attachment_id, 500, 200);
    }

    /**
     * Process uploaded hotel icon (resize to 192x192px).
     *
     * @param int $attachment_id The attachment ID.
     * @return bool True on success, false on failure.
     */
    public function process_icon($attachment_id) {
        return $this->resize_image($attachment_id, 192, 192);
    }

    /**
     * Resize an uploaded image.
     *
     * @param int $attachment_id The attachment ID.
     * @param int $width         Target width.
     * @param int $height        Target height.
     * @return bool True on success, false on failure.
     */
    private function resize_image($attachment_id, $width, $height) {
        $file = get_attached_file($attachment_id);

        if (!$file || !file_exists($file)) {
            error_log('HHA_Hotels: Image file not found for attachment ' . $attachment_id);
            return false;
        }

        // Get image editor
        $image = wp_get_image_editor($file);

        if (is_wp_error($image)) {
            error_log('HHA_Hotels: Failed to load image editor - ' . $image->get_error_message());
            return false;
        }

        // Resize image
        $result = $image->resize($width, $height, true);

        if (is_wp_error($result)) {
            error_log('HHA_Hotels: Failed to resize image - ' . $result->get_error_message());
            return false;
        }

        // Save resized image
        $saved = $image->save($file);

        if (is_wp_error($saved)) {
            error_log('HHA_Hotels: Failed to save resized image - ' . $saved->get_error_message());
            return false;
        }

        // Update attachment metadata
        $metadata = wp_generate_attachment_metadata($attachment_id, $file);
        wp_update_attachment_metadata($attachment_id, $metadata);

        return true;
    }

    /**
     * Get hotel logo URL.
     *
     * @param int    $hotel_id The hotel ID.
     * @param string $size     Image size (thumbnail, medium, full).
     * @return string|false Logo URL or false if not found.
     */
    public function get_logo_url($hotel_id, $size = 'full') {
        $hotel = $this->get($hotel_id);

        if (!$hotel || !$hotel->logo_id) {
            return false;
        }

        $url = wp_get_attachment_image_url($hotel->logo_id, $size);

        return $url ? $url : false;
    }

    /**
     * Get hotel icon URL.
     *
     * @param int    $hotel_id The hotel ID.
     * @param string $size     Image size (thumbnail, medium, full).
     * @return string|false Icon URL or false if not found.
     */
    public function get_icon_url($hotel_id, $size = 'full') {
        $hotel = $this->get($hotel_id);

        if (!$hotel || !$hotel->icon_id) {
            return false;
        }

        $url = wp_get_attachment_image_url($hotel->icon_id, $size);

        return $url ? $url : false;
    }
}
