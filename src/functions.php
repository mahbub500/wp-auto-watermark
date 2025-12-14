<?php
if ( ! defined('ABSPATH') ) {
    exit;
}



/**
 * Apply watermark to attachment
 */
if ( ! function_exists('wp_auto_apply_watermark') ) {

    function wp_auto_apply_watermark($attachment_id): array {

        if (get_post_meta($attachment_id, WP_AUTO_WATERMARK_META_KEY, true)) {
            return ['success' => false, 'error' => 'Already watermarked'];
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            return ['success' => false, 'error' => 'Unsupported format'];
        }

        $settings = get_option(WP_AUTO_WATERMARK_OPTION, [
            'watermark_text' => '© Copyright',
            'font_size'      => 28,
            'opacity'        => 70,
            'position'       => 'bottom-right',
        ]);

        $font_file = WPAW_PLUGIN_DIR . '/assets/fonts/Roboto-Bold.ttf';
        if (!file_exists($font_file)) {
            return ['success' => false, 'error' => 'Font file missing'];
        }

        try {

            // Load image
            $image = ($mime_type === 'image/png')
                ? imagecreatefrompng($file_path)
                : imagecreatefromjpeg($file_path);

            if (!$image) {
                throw new Exception('Image load failed');
            }

            // Required for PNG + transparency
            imagealphablending($image, true);
            imagesavealpha($image, true);

            $width  = imagesx($image);
            $height = imagesy($image);

            $text = (string) ($settings['watermark_text'] ?? '© Copyright');
            $size = (int) ($settings['font_size'] ?? 28);

            $bbox = imagettfbbox($size, 0, $font_file, $text);
            if ($bbox === false) {
                throw new Exception('imagettfbbox() failed');
            }

            $text_width  = abs($bbox[4] - $bbox[0]);
            $text_height = abs($bbox[5] - $bbox[1]);

            // Calculate position
            [$x, $y] = wp_auto_calculate_position(
                $settings['position'],
                $width,
                $height,
                $text_width,
                $text_height
            );

            // Clamp text inside canvas
            $x = (int) max(0, min($x, $width  - $text_width));
            $y = (int) max(0, min($y, $height - $text_height));

            // Opacity handling (0–127 GD alpha)
            $opacity = isset($settings['opacity']) ? (int) $settings['opacity'] : 70;
            $opacity = max(1, min(100, $opacity));
            $alpha   = (int) round(127 * (1 - ($opacity / 100)));

            // Shadow
            $shadow = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
            imagettftext(
                $image,
                $size,
                0,
                $x + 2,
                $y + $text_height + 2,
                $shadow,
                $font_file,
                $text
            );

            // Main text
            $color = imagecolorallocatealpha($image, 255, 255, 255, $alpha);
            $result = imagettftext(
                $image,
                $size,
                0,
                $x,
                $y + $text_height,
                $color,
                $font_file,
                $text
            );

            if ($result === false) {
                throw new Exception('imagettftext() failed');
            }

            // Save image
            if ($mime_type === 'image/png') {
                imagepng($image, $file_path, 9);
            } else {
                imagejpeg($image, $file_path, 90);
            }

            imagedestroy($image);

            // Uncomment if you want to prevent re-processing
            update_post_meta($attachment_id, WP_AUTO_WATERMARK_META_KEY, time());

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Calculate watermark position
 */
if ( ! function_exists('wp_auto_calculate_position') ) {

    function wp_auto_calculate_position(
        string $position,
        int $img_width,
        int $img_height,
        int $text_width,
        int $text_height
    ): array {

        $padding = 20;

        switch ($position) {

            case 'top-left':
                return [$padding, $padding];

            case 'top-right':
                return [
                    $img_width - $text_width - $padding,
                    $padding
                ];

            case 'bottom-left':
                return [
                    $padding,
                    $img_height - $text_height - $padding
                ];

            case 'center':
                return [
                    (int) (($img_width  - $text_width)  / 2),
                    (int) (($img_height - $text_height) / 2)
                ];

            case 'bottom-right':
            default:
                return [
                    $img_width - $text_width - $padding,
                    $img_height - $text_height - $padding
                ];
        }
    }
}

if ( ! function_exists('wp_auto_is_auto_watermark_enabled') ) {

    /**
     * Check if auto watermark on upload is enabled
     *
     * @param string $option_name The name of your plugin settings option
     * @return bool True if enabled, false otherwise
     */
    function wp_auto_is_auto_watermark_enabled($option_name = 'wp_auto_watermark_settings') {
        $settings = get_option($option_name, array());
        return !empty($settings['auto_watermark_upload']);
    }
}

if ( ! function_exists('wp_auto_is_bulk_watermark_enabled') ) {

    /**
     * Check if Bulk Watermark is enabled
     *
     * @param string $option_name The name of your plugin settings option
     * @return bool True if enabled, false otherwise
     */
    function wp_auto_is_bulk_watermark_enabled($option_name = 'wp_auto_watermark_settings') {
        $settings = get_option($option_name, array());
        return !empty($settings['bulk_watermark_enabled']);
    }
}
