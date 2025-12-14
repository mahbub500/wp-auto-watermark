<?php

namespace WPAutoWatermark;

use WP_Query;

class Ajax {

    /**
     * Meta key for watermark status
     */
    private string $meta_key = WP_AUTO_WATERMARK_META_KEY;

    public function __construct() {
        add_action('wp_ajax_get_unwatermarked_images', [$this, 'ajax_get_unwatermarked_images']);
        add_action('wp_ajax_process_watermark_batch', [$this, 'ajax_process_watermark_batch']);
        add_action('wp_ajax_get_watermarked_images', [$this, 'ajax_get_watermarked_images']);
    }

    /* ================= AJAX ================= */

    public function ajax_get_unwatermarked_images() {
        check_ajax_referer('wp_auto_watermark_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $query = new WP_Query([
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/jpg', 'image/png'],
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
            'meta_query'     => [
                [
                    'key'     => $this->meta_key,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        $images = [];

        foreach ($query->posts as $post) {
            $images[] = [
                'id'    => $post->ID,
                'title' => $post->post_title,
                'url'   => wp_get_attachment_url($post->ID),
                'thumb' => wp_get_attachment_image_url($post->ID, 'thumbnail'),
                'mime'  => get_post_mime_type($post->ID),
            ];
        }

        wp_send_json_success([
            'images' => $images,
            'total'  => count($images),
        ]);

        wp_die();
    }

    public function ajax_process_watermark_batch() {
        check_ajax_referer('wp_auto_watermark_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $ids = array_map('intval', $_POST['image_ids'] ?? []);

        if (empty($ids)) {
            wp_send_json_error(['message' => 'No images provided']);
        }

        $results = [
            'success' => [],
            'failed'  => [],
        ];

        foreach ($ids as $id) {
            // Call GLOBAL helper function (recommended)
            $res = wp_auto_apply_watermark($id);

            if (!empty($res['success'])) {
                $results['success'][] = $id;
            } else {
                $results['failed'][] = [
                    'id'    => $id,
                    'error' => $res['error'] ?? 'Unknown error',
                ];
            }
        }

        wp_send_json_success($results);

        wp_die();
    }

    public function ajax_get_watermarked_images() {
        check_ajax_referer('wp_auto_watermark_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $query = new \WP_Query([
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/jpg', 'image/png'],
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
            'meta_query'     => [
                [
                    'key'     => $this->meta_key,
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        $images = [];

        foreach ($query->posts as $post) {
            $images[] = [
                'id'    => $post->ID,
                'title' => $post->post_title,
                'url'   => wp_get_attachment_url($post->ID),
                'thumb' => wp_get_attachment_image_url($post->ID, 'thumbnail'),
            ];
        }

        wp_send_json_success([
            'images' => $images,
            'total'  => count($images),
        ]);

        wp_die();
    }
}
