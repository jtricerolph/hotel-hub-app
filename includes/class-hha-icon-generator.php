<?php
/**
 * Icon Generator - Dynamically generates PWA icons.
 *
 * Creates PNG icons from SVG on-the-fly until proper icon assets are created.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_Icon_Generator {

    /**
     * SVG icon template.
     */
    private static function get_svg($size, $bg_color = '#2196f3', $fg_color = '#ffffff') {
        $padding = $size * 0.1;
        $icon_size = $size - ($padding * 2);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
    <rect width="{$size}" height="{$size}" fill="{$bg_color}" rx="15%"/>
    <g transform="translate({$padding}, {$padding})">
        <svg viewBox="0 0 24 24" width="{$icon_size}" height="{$icon_size}">
            <path fill="{$fg_color}" d="M2 17h20v2H2v-2zm11.84-9.21c.1-.24.16-.51.16-.79 0-1.1-.9-2-2-2s-2 .9-2 2c0 .28.06.55.16.79C6.25 8.6 3.27 11.93 3 16h18c-.27-4.07-3.25-7.4-7.16-8.21z"/>
        </svg>
    </g>
</svg>
SVG;
    }

    /**
     * SVG for maskable icon (with safe zone).
     */
    private static function get_maskable_svg($size, $bg_color = '#2196f3', $fg_color = '#ffffff') {
        $padding = $size * 0.2; // 20% safe zone for maskable
        $icon_size = $size - ($padding * 2);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
    <rect width="{$size}" height="{$size}" fill="{$bg_color}"/>
    <g transform="translate({$padding}, {$padding})">
        <svg viewBox="0 0 24 24" width="{$icon_size}" height="{$icon_size}">
            <path fill="{$fg_color}" d="M2 17h20v2H2v-2zm11.84-9.21c.1-.24.16-.51.16-.79 0-1.1-.9-2-2-2s-2 .9-2 2c0 .28.06.55.16.79C6.25 8.6 3.27 11.93 3 16h18c-.27-4.07-3.25-7.4-7.16-8.21z"/>
        </svg>
    </g>
</svg>
SVG;
    }

    /**
     * Serve icon as PNG.
     *
     * @param int    $size     Icon size.
     * @param bool   $maskable Is maskable icon.
     */
    public static function serve_icon($size, $maskable = false) {
        // Get theme color
        $bg_color = get_option('hha_theme_primary_color', '#2196f3');
        $fg_color = '#ffffff';

        // Get SVG
        $svg = $maskable
            ? self::get_maskable_svg($size, $bg_color, $fg_color)
            : self::get_svg($size, $bg_color, $fg_color);

        // Check if GD or Imagick is available
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            self::serve_with_imagick($svg, $size);
        } elseif (extension_loaded('gd')) {
            self::serve_with_gd($svg, $size);
        } else {
            // Fallback to SVG
            header('Content-Type: image/svg+xml');
            header('Cache-Control: public, max-age=31536000');
            echo $svg;
        }
    }

    /**
     * Generate PNG using Imagick.
     *
     * @param string $svg  SVG content.
     * @param int    $size Icon size.
     */
    private static function serve_with_imagick($svg, $size) {
        try {
            $imagick = new Imagick();
            $imagick->readImageBlob($svg);
            $imagick->setImageFormat('png');
            $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);

            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=31536000');
            echo $imagick->getImageBlob();

            $imagick->clear();
            $imagick->destroy();
        } catch (Exception $e) {
            // Fallback to SVG
            header('Content-Type: image/svg+xml');
            header('Cache-Control: public, max-age=31536000');
            echo $svg;
        }
    }

    /**
     * Generate PNG using GD (basic rendering).
     *
     * @param string $svg  SVG content.
     * @param int    $size Icon size.
     */
    private static function serve_with_gd($svg, $size) {
        // GD can't render SVG directly, so we'll create a simple placeholder
        $image = imagecreatetruecolor($size, $size);

        // Parse colors
        $bg_color = get_option('hha_theme_primary_color', '#2196f3');
        $bg_rgb = self::hex_to_rgb($bg_color);
        $bg = imagecolorallocate($image, $bg_rgb['r'], $bg_rgb['g'], $bg_rgb['b']);
        $fg = imagecolorallocate($image, 255, 255, 255);

        // Fill background
        imagefill($image, 0, 0, $bg);

        // Draw a simple representation (circle with letter H)
        $center = $size / 2;
        $radius = $size * 0.35;
        imagefilledellipse($image, $center, $center, $radius * 2, $radius * 2, $fg);

        // Try to add text if font is available
        $font_size = $size * 0.4;
        $font_file = null;

        // Common system font paths
        $font_paths = array(
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        );

        foreach ($font_paths as $path) {
            if (file_exists($path)) {
                $font_file = $path;
                break;
            }
        }

        if ($font_file) {
            $bbox = imagettfbbox($font_size, 0, $font_file, 'H');
            $text_width = abs($bbox[4] - $bbox[0]);
            $text_height = abs($bbox[5] - $bbox[1]);
            $x = $center - ($text_width / 2);
            $y = $center + ($text_height / 2);
            imagettftext($image, $font_size, 0, $x, $y, $bg, $font_file, 'H');
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=31536000');
        imagepng($image, null, 9);
        imagedestroy($image);
    }

    /**
     * Convert hex color to RGB.
     *
     * @param string $hex Hex color code.
     * @return array RGB values.
     */
    private static function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');

        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        );
    }
}
