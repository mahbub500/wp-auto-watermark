<?php
/**
 * Plugin Name: WP Auto Watermark
 * Description: Automatically applies watermark text to uploaded images with bulk processing
 * Version: 1.0.0
 * Author: Mahbub
 * License: GPL v2 or later
 * Text Domain: wp-auto-watermark
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPAW_VERSION', '1.0.0');
define('WPAW_PLUGIN_FILE', __FILE__);
define('WPAW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPAW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPAW_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WP_AUTO_WATERMARK_META_KEY', '_watermarked');
define('WP_AUTO_WATERMARK_OPTION', 'wp_auto_watermark_settings');

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize plugin
use WPAutoWatermark\Admin;
use WPAutoWatermark\Ajax;

if ( is_admin() ) {
}
	new Ajax();
	new Admin();


