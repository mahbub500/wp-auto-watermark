<?php

namespace WPAutoWatermark;

class Admin {

	private string $option_name = WP_AUTO_WATERMARK_OPTION;
    private string $meta_key   = WP_AUTO_WATERMARK_META_KEY;

    public function __construct() {
        // add_filter('wp_generate_attachment_metadata', [$this, 'auto_watermark_on_upload'], 10, 2);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        // add_action('wp_head', function (){
        //     $attachment_id = 80; 
        //    $test =  wp_auto_apply_watermark( $attachment_id );
        //     // $file_path = get_attached_file($attachment_id);

        //     // $font_file = WPAW_PLUGIN_DIR . '/assets/fonts/Roboto-Bold.ttf';

        //     var_dump( $test );
        // });


        
    }

    public function auto_watermark_on_upload($metadata, $attachment_id) {
        wp_auto_apply_watermark($attachment_id);
        return $metadata;
    }

    public function add_admin_menu() {
        add_submenu_page(
            'upload.php',
            __('Bulk Watermark', 'wp-auto-watermark'),
            __('Bulk Watermark', 'wp-auto-watermark'),
            'manage_options',
            'bulk-watermark',
            [$this, 'bulk_watermark_page']
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'media_page_bulk-watermark') {
            return;
        }

        wp_enqueue_style(
            'wp-auto-watermark-admin',
            WPAW_PLUGIN_URL . '/assets/admin/admin.css',
            [],
            WPAW_VERSION
        );

        wp_enqueue_script(
            'wp-auto-watermark-admin',
            WPAW_PLUGIN_URL . '/assets/admin/admin.js',
            ['jquery'],
            WPAW_VERSION,
            true
        );

        wp_localize_script('wp-auto-watermark-admin', 'wpAutoWatermark', [
            'ajaxUrl' => admin_url('admin-ajax.php', 'relative'),
            'nonce'   => wp_create_nonce('wp_auto_watermark_nonce')
        ]);
    }

    public function register_settings() {
        register_setting('wp_auto_watermark_settings_group', $this->option_name);
    }

     /**
     * Bulk watermark page
     */
    public function bulk_watermark_page() {
        $settings = get_option($this->option_name, array(
            'watermark_text' => 'Copyright',
            'font_size' => 20,
            'opacity' => 50,
            'position' => 'bottom-right'
        ));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WP Auto Watermark', 'wp-auto-watermark'); ?></h1>

            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-unwatermarked" class="nav-tab nav-tab-active" data-tab="unwatermarked"><?php echo esc_html__('Unwatermarked', 'wp-auto-watermark'); ?></a>
                <a href="#tab-watermarked" class="nav-tab" data-tab="watermarked"><?php echo esc_html__('Watermarked', 'wp-auto-watermark'); ?></a>
                <a href="#tab-settings" class="nav-tab" data-tab="settings"><?php echo esc_html__('Settings', 'wp-auto-watermark'); ?></a>
            </h2>

            <!-- Unwatermarked Images Tab -->
            <div id="tab-unwatermarked" class="tab-content">
                <div style="margin-bottom: 20px;">
                    <button type="button" id="load-unwatermarked-images" class="button button-secondary">
                        <?php echo esc_html__('Load Unwatermarked Images', 'wp-auto-watermark'); ?>
                    </button>
                </div>

                

                <!-- Watermark Controls -->
                <div id="watermark-controls" style="display:none; margin-top: 20px;">
                    <button type="button" id="start-watermark" class="button button-primary">
                        <?php echo esc_html__('Start Watermarking', 'wp-auto-watermark'); ?>
                    </button>
                    <button type="button" id="retry-failed" class="button button-secondary" style="display:none;">
                        <?php echo esc_html__('Retry Failed', 'wp-auto-watermark'); ?>
                    </button>
                </div>

                <!-- Progress Bar -->
                <div id="progress-container" style="display:none; margin-top: 20px;">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar">
                            <div id="progress-fill" class="progress-fill"></div>
                        </div>
                        <div class="progress-text">
                            <span id="progress-current">0</span> / <span id="progress-total">0</span>
                            (<span id="progress-percent">0</span>%)
                        </div>
                    </div>
                    <div id="progress-status" style="margin-top: 10px; font-weight: 500;"></div>
                </div>

                <div id="images-table-container"></div>

                <!-- Results -->
                <div id="results-container" style="display:none; margin-top: 20px;">
                    <div id="results-summary"></div>
                    <div id="failed-images"></div>
                </div>

                <!-- Status Message -->
                <div id="watermark-status" class="notice notice-info" style="display:none;">
                    <p></p>
                </div>
            </div>

            <!-- Watermarked Images Tab -->
            <div id="tab-watermarked" class="tab-content" style="display:none;">
                <button type="button" id="load-watermarked-images" class="button button-secondary">
                    <?php echo esc_html__('Load Watermarked Images', 'wp-auto-watermark'); ?>
                </button>
                <div id="watermarked-images" style="margin-top: 20px;"></div>
            </div>

            <!-- Settings Tab -->
            <div id="tab-settings" class="tab-content" style="display:none;">
                <form method="post" action="options.php">
                    <?php settings_fields('wp_auto_watermark_settings_group'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="watermark_text"><?php echo esc_html__('Watermark Text', 'wp-auto-watermark'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="watermark_text" 
                                       name="<?php echo esc_attr($this->option_name); ?>[watermark_text]" 
                                       value="<?php echo esc_attr($settings['watermark_text']); ?>" 
                                       class="regular-text">
                                <p class="description"><?php echo esc_html__('Text to display as watermark', 'wp-auto-watermark'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="font_size"><?php echo esc_html__('Font Size', 'wp-auto-watermark'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="font_size" 
                                       name="<?php echo esc_attr($this->option_name); ?>[font_size]" 
                                       value="<?php echo esc_attr($settings['font_size']); ?>" 
                                       min="10" 
                                       max="100">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="opacity"><?php echo esc_html__('Opacity (%)', 'wp-auto-watermark'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="opacity" 
                                       name="<?php echo esc_attr($this->option_name); ?>[opacity]" 
                                       value="<?php echo esc_attr($settings['opacity']); ?>" 
                                       min="0" 
                                       max="100">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="position"><?php echo esc_html__('Position', 'wp-auto-watermark'); ?></label>
                            </th>
                            <td>
                                <select id="position" name="<?php echo esc_attr($this->option_name); ?>[position]">
                                    <option value="top-left" <?php selected($settings['position'], 'top-left'); ?>>Top Left</option>
                                    <option value="top-right" <?php selected($settings['position'], 'top-right'); ?>>Top Right</option>
                                    <option value="bottom-left" <?php selected($settings['position'], 'bottom-left'); ?>>Bottom Left</option>
                                    <option value="bottom-right" <?php selected($settings['position'], 'bottom-right'); ?>>Bottom Right</option>
                                    <option value="center" <?php selected($settings['position'], 'center'); ?>>Center</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
}
