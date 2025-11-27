<?php
/**
 * Encryption utility for securing API credentials.
 *
 * Uses WordPress SECURE_AUTH_KEY and AUTH_SALT for encryption/decryption.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Encryption {

    /**
     * Encrypt a value using WordPress salts.
     *
     * @param string $value The value to encrypt.
     * @return string|false The encrypted value (base64 encoded) or false on failure.
     */
    public static function encrypt($value) {
        if (empty($value)) {
            return '';
        }

        // Check if OpenSSL is available
        if (!function_exists('openssl_encrypt')) {
            error_log('HHA_Encryption: OpenSSL not available');
            return false;
        }

        // Get encryption key and IV from WordPress salts
        $key = self::get_key();
        $iv = self::get_iv();

        if (!$key || !$iv) {
            error_log('HHA_Encryption: Unable to get encryption key or IV');
            return false;
        }

        // Encrypt the value
        $encrypted = openssl_encrypt(
            $value,
            'AES-256-CBC',
            $key,
            0,
            $iv
        );

        if ($encrypted === false) {
            error_log('HHA_Encryption: Encryption failed');
            return false;
        }

        // Return base64 encoded for safe storage
        return base64_encode($encrypted);
    }

    /**
     * Decrypt a value using WordPress salts.
     *
     * @param string $encrypted_value The encrypted value (base64 encoded).
     * @return string|false The decrypted value or false on failure.
     */
    public static function decrypt($encrypted_value) {
        if (empty($encrypted_value)) {
            return '';
        }

        // Check if OpenSSL is available
        if (!function_exists('openssl_decrypt')) {
            error_log('HHA_Encryption: OpenSSL not available');
            return false;
        }

        // Get encryption key and IV from WordPress salts
        $key = self::get_key();
        $iv = self::get_iv();

        if (!$key || !$iv) {
            error_log('HHA_Encryption: Unable to get encryption key or IV');
            return false;
        }

        // Decode from base64
        $encrypted = base64_decode($encrypted_value, true);

        if ($encrypted === false) {
            error_log('HHA_Encryption: Base64 decode failed');
            return false;
        }

        // Decrypt the value
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $key,
            0,
            $iv
        );

        if ($decrypted === false) {
            error_log('HHA_Encryption: Decryption failed');
            return false;
        }

        return $decrypted;
    }

    /**
     * Get encryption key from WordPress SECURE_AUTH_KEY.
     *
     * @return string|false The encryption key or false if not available.
     */
    private static function get_key() {
        if (!defined('SECURE_AUTH_KEY') || empty(SECURE_AUTH_KEY)) {
            return false;
        }

        // Hash the key to ensure consistent length for AES-256
        return hash('sha256', SECURE_AUTH_KEY, true);
    }

    /**
     * Get initialization vector from WordPress AUTH_SALT.
     *
     * @return string|false The IV (16 bytes) or false if not available.
     */
    private static function get_iv() {
        if (!defined('AUTH_SALT') || empty(AUTH_SALT)) {
            return false;
        }

        // Hash and take first 16 bytes for AES-256-CBC IV
        $hashed = hash('sha256', AUTH_SALT, true);
        return substr($hashed, 0, 16);
    }

    /**
     * Encrypt an array/object as JSON.
     *
     * @param array|object $data The data to encrypt.
     * @return string|false The encrypted JSON or false on failure.
     */
    public static function encrypt_json($data) {
        $json = json_encode($data);

        if ($json === false) {
            error_log('HHA_Encryption: JSON encode failed');
            return false;
        }

        return self::encrypt($json);
    }

    /**
     * Decrypt JSON back to array/object.
     *
     * @param string $encrypted_json The encrypted JSON.
     * @param bool $assoc Return as associative array instead of object.
     * @return array|object|false The decrypted data or false on failure.
     */
    public static function decrypt_json($encrypted_json, $assoc = true) {
        $json = self::decrypt($encrypted_json);

        if ($json === false) {
            return false;
        }

        $data = json_decode($json, $assoc);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log('HHA_Encryption: JSON decode failed - ' . json_last_error_msg());
            return false;
        }

        return $data;
    }
}
