<?php
/**
 * Plugin Name: BZ Image Optimizer Pro
 * Plugin URI: https://example.com/bz-image-optimizer-pro
 * Description: Compress, resize, and convert images for speed and SEO with advanced WebP/AVIF support
 * Version: 1.0.0
 * Author: Sumaira Noreen
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: bz-image-optimizer
 */

if (!defined('ABSPATH')) exit;

define('BZ_OPTIMIZER_VERSION', '1.0.0');
define('BZ_OPTIMIZER_PATH', plugin_dir_path(__FILE__));
define('BZ_OPTIMIZER_URL', plugin_dir_url(__FILE__));

class BZ_Image_Optimizer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('manage_media_columns', [$this, 'add_media_columns']);
        add_action('manage_media_custom_column', [$this, 'display_media_columns'], 10, 2);
        add_action('add_attachment', [$this, 'optimize_on_upload']);
        add_action('wp_ajax_bz_optimize_image', [$this, 'ajax_optimize_image']);
        add_action('wp_ajax_bz_bulk_optimize', [$this, 'ajax_bulk_optimize']);
        add_action('wp_ajax_bz_restore_image', [$this, 'ajax_restore_image']);
        add_filter('attachment_fields_to_edit', [$this, 'add_optimization_details'], 10, 2);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'BZ Image Optimizer',
            'BZ Optimizer',
            'manage_options',
            'bz-image-optimizer',
            [$this, 'render_settings_page'],
            'dashicons-images-alt2',
            65
        );
        
        add_submenu_page(
            'bz-image-optimizer',
            'Settings',
            'Settings',
            'manage_options',
            'bz-image-optimizer',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'bz-image-optimizer',
            'Bulk Optimize',
            'Bulk Optimize',
            'manage_options',
            'bz-bulk-optimize',
            [$this, 'render_bulk_page']
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'bz-') !== false || $hook === 'upload.php' || $hook === 'post.php') {
            wp_enqueue_style('bz-optimizer-admin', BZ_OPTIMIZER_URL . 'assets/css/admin.css', [], BZ_OPTIMIZER_VERSION);
            wp_enqueue_script('bz-optimizer-admin', BZ_OPTIMIZER_URL . 'assets/js/admin.js', ['jquery'], BZ_OPTIMIZER_VERSION, true);
            
            wp_localize_script('bz-optimizer-admin', 'bzOptimizer', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bz_optimizer_nonce')
            ]);
        }
    }
    
    public function add_media_columns($columns) {
        $columns['bz_optimization'] = 'Optimization';
        return $columns;
    }
    
    public function display_media_columns($column_name, $post_id) {
        if ($column_name === 'bz_optimization') {
            $meta = get_post_meta($post_id, '_bz_optimization_data', true);
            
            if ($meta && !empty($meta)) {
                $savings = isset($meta['savings_percent']) ? $meta['savings_percent'] : 0;
                $color = $savings > 30 ? '#00a32a' : ($savings > 15 ? '#f0b849' : '#999');
                
                echo '<div class="bz-opt-badge" style="display:inline-block;background:' . esc_attr($color) . ';color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;">';
                echo esc_html(number_format($savings, 1)) . '%</div>';
                echo '<button class="button button-small bz-view-details" data-id="' . esc_attr($post_id) . '" style="margin-left:5px;">Details</button>';
            } else {
                echo '<button class="button button-small button-primary bz-optimize-now" data-id="' . esc_attr($post_id) . '">Optimize</button>';
            }
        }
    }
    
    public function add_optimization_details($form_fields, $post) {
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }
        
        $meta = get_post_meta($post->ID, '_bz_optimization_data', true);
        
        if ($meta && !empty($meta)) {
            $html = '<div class="bz-optimization-details" style="background:#f9f9f9;padding:15px;border-radius:5px;margin-top:10px;">';
            $html .= '<h3 style="margin-top:0;color:#2271b1;">Optimization Details</h3>';
            
            $html .= '<table style="width:100%;border-collapse:collapse;">';
            $html .= '<tr><td style="padding:5px 0;"><strong>New Filesize:</strong></td><td style="text-align:right;color:#00a32a;">' . esc_html(size_format($meta['new_size'])) . '</td></tr>';
            $html .= '<tr><td style="padding:5px 0;"><strong>Original Filesize:</strong></td><td style="text-align:right;">' . esc_html(size_format($meta['original_size'])) . '</td></tr>';
            $html .= '<tr><td style="padding:5px 0;"><strong>Original Saving:</strong></td><td style="text-align:right;color:#00a32a;font-weight:600;">' . esc_html(number_format($meta['savings_percent'], 2)) . '%</td></tr>';
            $html .= '<tr><td style="padding:5px 0;"><strong>Level:</strong></td><td style="text-align:right;">' . esc_html(ucfirst($meta['level'])) . '</td></tr>';
            
            if (!empty($meta['webp_generated'])) {
                $html .= '<tr><td style="padding:5px 0;"><strong>Next-Gen Generated:</strong></td><td style="text-align:right;color:#00a32a;">Yes</td></tr>';
            }
            
            if (isset($meta['thumbnails_optimized'])) {
                $html .= '<tr><td style="padding:5px 0;"><strong>Thumbnails Optimized:</strong></td><td style="text-align:right;">' . esc_html($meta['thumbnails_optimized']) . '</td></tr>';
            }
            
            $html .= '<tr><td style="padding:5px 0;"><strong>Overall Saving:</strong></td><td style="text-align:right;color:#00a32a;font-size:16px;font-weight:700;">' . esc_html(number_format($meta['overall_savings'], 2)) . '%</td></tr>';
            $html .= '</table>';
            
            $html .= '<div style="margin-top:15px;">';
            $html .= '<button type="button" class="button bz-restore-original" data-id="' . esc_attr($post->ID) . '" style="margin-right:5px;">🔄 Restore Original</button>';
            $html .= '<button type="button" class="button button-primary bz-reoptimize" data-id="' . esc_attr($post->ID) . '">⚙️ Re-Optimize</button>';
            $html .= '</div>';
            
            $html .= '</div>';
            
            $form_fields['bz_optimization_info'] = [
                'label' => '',
                'input' => 'html',
                'html' => $html,
                'show_in_edit' => true
            ];
        }
        
        return $form_fields;
    }
    
    public function optimize_on_upload($post_id) {
        if (!wp_attachment_is_image($post_id)) {
            return;
        }
        
        $auto_optimize = get_option('bz_auto_optimize', true);
        if (!$auto_optimize) {
            return;
        }
        
        $this->optimize_image($post_id);
    }
    
    public function ajax_optimize_image() {
        check_ajax_referer('bz_optimizer_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        $result = $this->optimize_image($image_id);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Optimization failed');
        }
    }
    
    public function ajax_bulk_optimize() {
        check_ajax_referer('bz_optimizer_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = 5;
        
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'meta_query' => [
                [
                    'key' => '_bz_optimization_data',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];
        
        $images = get_posts($args);
        $total = wp_count_posts('attachment')->inherit;
        
        $results = [];
        foreach ($images as $image) {
            $result = $this->optimize_image($image->ID);
            if ($result) {
                $results[] = $result;
            }
        }
        
        wp_send_json_success([
            'processed' => $offset + count($images),
            'total' => $total,
            'results' => $results,
            'complete' => count($images) < $limit
        ]);
    }
    
    public function ajax_restore_image() {
        check_ajax_referer('bz_optimizer_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        $result = $this->restore_original($image_id);
        
        if ($result) {
            wp_send_json_success('Image restored successfully');
        } else {
            wp_send_json_error('Restore failed');
        }
    }
    
    private function optimize_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        $original_size = filesize($file_path);
        $backup_path = $file_path . '.bz-backup';
        
        if (!file_exists($backup_path)) {
            copy($file_path, $backup_path);
        }
        
        $quality = get_option('bz_compression_quality', 85);
        $level = $this->get_compression_level($quality);
        
        $image = wp_get_image_editor($file_path);
        
        if (is_wp_error($image)) {
            return false;
        }
        
        $image->set_quality($quality);
        $image->save($file_path);
        
        $new_size = filesize($file_path);
        $savings = (($original_size - $new_size) / $original_size) * 100;
        
        $webp_generated = false;
        if (get_option('bz_convert_webp', true)) {
            $webp_generated = $this->convert_to_webp($file_path);
        }
        
        $metadata = wp_get_attachment_metadata($attachment_id);
        $thumbnails_optimized = 0;
        
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $base_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size => $size_data) {
                $thumb_path = $base_dir . '/' . $size_data['file'];
                if (file_exists($thumb_path)) {
                    $thumb_image = wp_get_image_editor($thumb_path);
                    if (!is_wp_error($thumb_image)) {
                        $thumb_image->set_quality($quality);
                        $thumb_image->save($thumb_path);
                        $thumbnails_optimized++;
                        
                        if (get_option('bz_convert_webp', true)) {
                            $this->convert_to_webp($thumb_path);
                        }
                    }
                }
            }
        }
        
        $overall_savings = $savings;
        
        $optimization_data = [
            'original_size' => $original_size,
            'new_size' => $new_size,
            'savings_percent' => $savings,
            'level' => $level,
            'webp_generated' => $webp_generated,
            'thumbnails_optimized' => $thumbnails_optimized,
            'overall_savings' => $overall_savings,
            'optimized_date' => current_time('mysql')
        ];
        
        update_post_meta($attachment_id, '_bz_optimization_data', $optimization_data);
        
        return $optimization_data;
    }
    
    private function convert_to_webp($file_path) {
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
        
        if (function_exists('imagewebp')) {
            $info = getimagesize($file_path);
            
            if ($info[2] === IMAGETYPE_JPEG) {
                $image = imagecreatefromjpeg($file_path);
            } elseif ($info[2] === IMAGETYPE_PNG) {
                $image = imagecreatefrompng($file_path);
            } else {
                return false;
            }
            
            if ($image) {
                imagewebp($image, $webp_path, 85);
                imagedestroy($image);
                return true;
            }
        }
        
        return false;
    }
    
    private function get_compression_level($quality) {
        if ($quality >= 90) return 'lossy';
        if ($quality >= 75) return 'smart';
        return 'aggressive';
    }
    
    private function restore_original($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $backup_path = $file_path . '.bz-backup';
        
        if (!file_exists($backup_path)) {
            return false;
        }
        
        copy($backup_path, $file_path);
        delete_post_meta($attachment_id, '_bz_optimization_data');
        
        return true;
    }
    
    public function render_settings_page() {
        include BZ_OPTIMIZER_PATH . 'templates/settings-page.php';
    }
    
    public function render_bulk_page() {
        include BZ_OPTIMIZER_PATH . 'templates/bulk-page.php';
    }
}

BZ_Image_Optimizer::get_instance();