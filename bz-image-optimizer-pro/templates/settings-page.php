<?php
/**
 * Settings Page Template
 * Path: templates/settings-page.php
 */

if (!defined('ABSPATH')) exit;

// Save settings
if (isset($_POST['bz_save_settings']) && check_admin_referer('bz_settings_save', 'bz_settings_nonce')) {
    update_option('bz_compression_quality', intval($_POST['bz_compression_quality']));
    update_option('bz_auto_optimize', isset($_POST['bz_auto_optimize']) ? 1 : 0);
    update_option('bz_convert_webp', isset($_POST['bz_convert_webp']) ? 1 : 0);
    update_option('bz_convert_avif', isset($_POST['bz_convert_avif']) ? 1 : 0);
    update_option('bz_max_width', intval($_POST['bz_max_width']));
    update_option('bz_max_height', intval($_POST['bz_max_height']));
    update_option('bz_exclude_folders', sanitize_textarea_field($_POST['bz_exclude_folders']));
    
    echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Settings saved successfully!</strong></p></div>';
}

// Get current settings
$quality = get_option('bz_compression_quality', 85);
$auto_optimize = get_option('bz_auto_optimize', 1);
$convert_webp = get_option('bz_convert_webp', 1);
$convert_avif = get_option('bz_convert_avif', 0);
$max_width = get_option('bz_max_width', 2560);
$max_height = get_option('bz_max_height', 2560);
$exclude_folders = get_option('bz_exclude_folders', '');

// Get statistics
$args = array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_bz_optimization_data',
            'compare' => 'EXISTS'
        )
    )
);

$optimized_images = get_posts($args);
$total_images = wp_count_posts('attachment')->inherit;
$total_saved = 0;
$total_original = 0;

foreach ($optimized_images as $image) {
    $meta = get_post_meta($image->ID, '_bz_optimization_data', true);
    if ($meta) {
        $total_original += $meta['original_size'];
        $total_saved += ($meta['original_size'] - $meta['new_size']);
    }
}

$avg_savings = count($optimized_images) > 0 ? ($total_saved / $total_original) * 100 : 0;
?>

<div class="wrap bz-optimizer-wrap">
    <div class="bz-optimizer-header">
        <h1>🚀 BZ Image Optimizer Pro</h1>
        <p>Compress, resize, and convert images for blazing-fast performance</p>
    </div>
    
    <!-- Statistics Dashboard -->
    <div class="bz-stats-grid">
        <div class="bz-stat-box">
            <div class="bz-stat-value"><?php echo number_format(count($optimized_images)); ?></div>
            <div class="bz-stat-label">Images Optimized</div>
        </div>
        
        <div class="bz-stat-box green">
            <div class="bz-stat-value"><?php echo size_format($total_saved); ?></div>
            <div class="bz-stat-label">Total Space Saved</div>
        </div>
        
        <div class="bz-stat-box orange">
            <div class="bz-stat-value"><?php echo number_format($avg_savings, 1); ?>%</div>
            <div class="bz-stat-label">Average Savings</div>
        </div>
        
        <div class="bz-stat-box blue">
            <div class="bz-stat-value"><?php echo number_format($total_images - count($optimized_images)); ?></div>
            <div class="bz-stat-label">Images Remaining</div>
        </div>
    </div>
    
    <!-- Settings Form -->
    <form method="post" action="" id="bz-settings-form">
        <?php wp_nonce_field('bz_settings_save', 'bz_settings_nonce'); ?>
        
        <!-- Compression Settings -->
        <div class="bz-card">
            <h2>⚙️ Compression Settings</h2>
            
            <div class="bz-form-group">
                <label for="bz_compression_quality">
                    Compression Quality (1-100)
                </label>
                <input type="number" 
                       id="bz_compression_quality" 
                       name="bz_compression_quality" 
                       value="<?php echo esc_attr($quality); ?>" 
                       min="1" 
                       max="100"
                       required>
                <span class="description">
                    Recommended: <strong>85</strong>. Higher values = better quality but larger files. Lower values = more compression but reduced quality.
                </span>
            </div>
            
            <div class="bz-form-group">
                <label>
                    <input type="checkbox" 
                           name="bz_auto_optimize" 
                           value="1" 
                           <?php checked($auto_optimize, 1); ?>>
                    Auto-optimize images on upload
                </label>
                <span class="description">
                    Automatically optimize images when they are uploaded to the media library.
                </span>
            </div>
        </div>
        
        <!-- Next-Gen Formats -->
        <div class="bz-card">
            <h2>🖼️ Next-Gen Formats</h2>
            
            <div class="bz-form-group">
                <label>
                    <input type="checkbox" 
                           name="bz_convert_webp" 
                           value="1" 
                           <?php checked($convert_webp, 1); ?>>
                    Convert to WebP format
                </label>
                <span class="description">
                    Generate WebP versions alongside original images for better compression (25-35% smaller). Widely supported by modern browsers.
                </span>
            </div>
            
            <div class="bz-form-group">
                <label>
                    <input type="checkbox" 
                           name="bz_convert_avif" 
                           value="1" 
                           <?php checked($convert_avif, 1); ?>
                           disabled>
                    Convert to AVIF format <span style="color:#999;">(Coming Soon)</span>
                </label>
                <span class="description">
                    Generate AVIF versions for even better compression (requires PHP 8.1+). Up to 50% smaller than JPEG.
                </span>
            </div>
        </div>
        
        <!-- Resize Settings -->
        <div class="bz-card">
            <h2>📐 Resize Settings</h2>
            
            <div class="bz-form-group">
                <label for="bz_max_width">
                    Maximum Width (pixels)
                </label>
                <input type="number" 
                       id="bz_max_width" 
                       name="bz_max_width" 
                       value="<?php echo esc_attr($max_width); ?>" 
                       min="0">
                <span class="description">
                    Resize images larger than this width. Set to <strong>0</strong> to disable. Recommended: 2560px for most websites.
                </span>
            </div>
            
            <div class="bz-form-group">
                <label for="bz_max_height">
                    Maximum Height (pixels)
                </label>
                <input type="number" 
                       id="bz_max_height" 
                       name="bz_max_height" 
                       value="<?php echo esc_attr($max_height); ?>" 
                       min="0">
                <span class="description">
                    Resize images larger than this height. Set to <strong>0</strong> to disable. Recommended: 2560px for most websites.
                </span>
            </div>
        </div>
        
        <!-- Exclusions -->
        <div class="bz-card">
            <h2>🚫 Exclusions</h2>
            
            <div class="bz-form-group">
                <label for="bz_exclude_folders">
                    Exclude Folders (one per line)
                </label>
                <textarea id="bz_exclude_folders" 
                          name="bz_exclude_folders" 
                          rows="4" 
                          style="width:100%;max-width:600px;"
                          placeholder="/wp-content/uploads/woocommerce_uploads/&#10;/wp-content/uploads/protected/"><?php echo esc_textarea($exclude_folders); ?></textarea>
                <span class="description">
                    Enter folder paths to exclude from optimization (one per line). Example: <code>/wp-content/uploads/woocommerce_uploads/</code>
                </span>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="bz-card">
            <h2>ℹ️ System Information</h2>
            
            <table style="width:100%;max-width:600px;">
                <tr>
                    <td style="padding:8px 0;"><strong>PHP Version:</strong></td>
                    <td style="text-align:right;"><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td style="padding:8px 0;"><strong>GD Library:</strong></td>
                    <td style="text-align:right;">
                        <?php echo extension_loaded('gd') ? '✅ Installed' : '❌ Not installed'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 0;"><strong>Imagick:</strong></td>
                    <td style="text-align:right;">
                        <?php echo extension_loaded('imagick') ? '✅ Installed' : '❌ Not installed'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 0;"><strong>WebP Support:</strong></td>
                    <td style="text-align:right;">
                        <?php echo function_exists('imagewebp') ? '✅ Supported' : '❌ Not supported'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 0;"><strong>Max Upload Size:</strong></td>
                    <td style="text-align:right;"><?php echo ini_get('upload_max_filesize'); ?></td>
                </tr>
                <tr>
                    <td style="padding:8px 0;"><strong>PHP Memory Limit:</strong></td>
                    <td style="text-align:right;"><?php echo ini_get('memory_limit'); ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Save Button -->
        <button type="submit" name="bz_save_settings" class="bz-btn-primary">
            💾 Save Settings
        </button>
    </form>
    
    <!-- Help Section -->
    <div class="bz-card" style="margin-top:30px;background:#f0f7ff;border-color:#2271b1;">
        <h2 style="border-color:#2271b1;">💡 Quick Tips</h2>
        <ul style="margin:0;padding-left:20px;">
            <li style="margin-bottom:10px;">For best results, use a quality setting between <strong>82-87</strong></li>
            <li style="margin-bottom:10px;">Enable WebP conversion to reduce file sizes by 25-35%</li>
            <li style="margin-bottom:10px;">Use the Bulk Optimize feature to process existing images</li>
            <li style="margin-bottom:10px;">Original images are always backed up - you can restore them anytime</li>
            <li style="margin-bottom:10px;">Monitor the statistics dashboard to track your savings</li>
        </ul>
    </div>
</div>