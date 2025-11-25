<?php
/**
 * Bulk Optimization Page Template
 * Path: templates/bulk-page.php
 */

if (!defined('ABSPATH')) exit;

// Get unoptimized images count
$args = array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_bz_optimization_data',
            'compare' => 'NOT EXISTS'
        )
    )
);

$unoptimized_images = get_posts($args);
$unoptimized_count = count($unoptimized_images);
$total_images = wp_count_posts('attachment')->inherit;
$optimized_count = $total_images - $unoptimized_count;

// Calculate total size of unoptimized images
$total_unoptimized_size = 0;
foreach ($unoptimized_images as $image) {
    $file_path = get_attached_file($image->ID);
    if (file_exists($file_path)) {
        $total_unoptimized_size += filesize($file_path);
    }
}
?>

<div class="wrap bz-optimizer-wrap">
    <div class="bz-optimizer-header">
        <h1>⚡ Bulk Optimization</h1>
        <p>Optimize all your media library images in one go</p>
    </div>
    
    <!-- Library Status -->
    <div class="bz-card">
        <h2>📊 Library Status</h2>
        
        <div style="margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding: 25px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; font-weight: 700; margin-bottom:5px;">
                        <?php echo number_format($total_images); ?>
                    </div>
                    <div style="font-size: 14px; opacity:0.9;">
                        Total Images
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color:#fff; padding: 25px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; font-weight: 700; margin-bottom:5px;">
                        <?php echo number_format($optimized_count); ?>
                    </div>
                    <div style="font-size: 14px; opacity:0.9;">
                        Already Optimized
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color:#fff; padding: 25px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; font-weight: 700; margin-bottom:5px;">
                        <?php echo number_format($unoptimized_count); ?>
                    </div>
                    <div style="font-size: 14px; opacity:0.9;">
                        Pending Optimization
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color:#fff; padding: 25px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; font-weight: 700; margin-bottom:5px;">
                        <?php echo size_format($total_unoptimized_size); ?>
                    </div>
                    <div style="font-size: 14px; opacity:0.9;">
                        Size to Optimize
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($unoptimized_count > 0): ?>
            <div style="background: linear-gradient(135deg, #e7f5ff 0%, #d4edff 100%); border-left: 4px solid #2271b1; padding: 20px; margin-bottom: 25px; border-radius: 6px;">
                <h3 style="margin:0 0 10px 0;color:#2271b1;">
                    ℹ️ Ready to optimize
                </h3>
                <p style="margin:0;font-size:15px;line-height:1.6;">
                    <strong><?php echo number_format($unoptimized_count); ?> images</strong> are waiting to be optimized. 
                    Based on average savings of 30%, you could potentially save approximately 
                    <strong><?php echo size_format($total_unoptimized_size * 0.30); ?></strong> of storage space.
                </p>
            </div>
            
            <div style="text-align:center;margin:30px 0;">
                <button type="button" id="bz-start-bulk" class="bz-btn-primary" style="font-size: 18px; padding: 18px 50px;">
                    🚀 Start Bulk Optimization
                </button>
            </div>
            
            <div id="bz-bulk-progress" class="bz-bulk-progress" style="display: none; margin-top: 30px;">
                <h3 style="margin-top: 0;">Optimization Progress</h3>
                
                <div class="bz-progress-bar">
                    <div id="bz-progress-fill" class="bz-progress-fill" style="width: 0%;">0%</div>
                </div>
                
                <div id="bz-progress-text" class="bz-progress-text">
                    Preparing to optimize images...
                </div>
                
                <div style="margin-top: 20px; max-height: 400px; overflow-y: auto; background: #fff; border: 1px solid #ddd; border-radius: 6px;">
                    <div id="bz-bulk-log" style="min-height:100px;"></div>
                </div>
            </div>
        <?php else: ?>
            <div style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 4px solid #00a32a; padding: 30px; border-radius: 8px; text-align: center;">
                <div style="font-size: 64px; margin-bottom: 15px;">✅</div>
                <h3 style="margin: 0 0 15px 0; color: #00a32a; font-size:24px;">All Images Optimized!</h3>
                <p style="margin: 0; color: #155724; font-size:16px;">
                    Excellent! All <?php echo number_format($total_images); ?> images in your media library have been optimized for peak performance.
                </p>
                <div style="margin-top:20px;">
                    <a href="<?php echo admin_url('upload.php'); ?>" class="button button-primary button-large">
                        View Media Library
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Optimization Tips -->
    <div class="bz-card">
        <h2>💡 Optimization Tips</h2>
        
        <div style="display: grid; gap: 15px;">
            <div style="display: flex; align-items: start; padding: 20px; background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f1 100%); border-radius: 8px; border-left:4px solid #667eea;">
                <div style="font-size: 32px; margin-right: 20px;">⚡</div>
                <div>
                    <strong style="font-size:16px;color:#1d2327;">Processing Speed</strong>
                    <p style="margin: 8px 0 0 0; color: #666; font-size: 14px; line-height:1.6;">
                        Bulk optimization processes 5 images at a time to prevent server overload. Large libraries may take several minutes. 
                        The process runs in the background, so you can leave this page open.
                    </p>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; padding: 20px; background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f1 100%); border-radius: 8px; border-left:4px solid #00a32a;">
                <div style="font-size: 32px; margin-right: 20px;">💾</div>
                <div>
                    <strong style="font-size:16px;color:#1d2327;">Backup Protection</strong>
                    <p style="margin: 8px 0 0 0; color: #666; font-size: 14px; line-height:1.6;">
                        Original images are automatically backed up before optimization. You can restore them anytime from the media library 
                        by clicking "Edit" on any image and using the "Restore Original" button.
                    </p>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; padding: 20px; background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f1 100%); border-radius: 8px; border-left:4px solid #f0b849;">
                <div style="font-size: 32px; margin-right: 20px;">🎯</div>
                <div>
                    <strong style="font-size:16px;color:#1d2327;">Quality Settings</strong>
                    <p style="margin: 8px 0 0 0; color: #666; font-size: 14px; line-height:1.6;">
                        Adjust compression quality and other settings in the 
                        <a href="<?php echo admin_url('admin.php?page=bz-image-optimizer'); ?>">Settings page</a> 
                        before running bulk optimization. Recommended quality: 85 for best balance.
                    </p>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; padding: 20px; background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f1 100%); border-radius: 8px; border-left:4px solid #2271b1;">
                <div style="font-size: 32px; margin-right: 20px;">🌐</div>
                <div>
                    <strong style="font-size:16px;color:#1d2327;">Next-Gen Formats</strong>
                    <p style="margin: 8px 0 0 0; color: #666; font-size: 14px; line-height:1.6;">
                        Enable WebP conversion in settings to automatically generate modern format versions for faster loading. 
                        WebP images are typically 25-35% smaller than JPEG while maintaining quality.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Impact -->
    <div class="bz-card">
        <h2>📈 Expected Performance Impact</h2>
        
        <div style="background:#f9f9f9;padding:20px;border-radius:8px;">
            <p style="margin:0 0 15px 0;font-size:15px;">
                Optimizing your images can lead to significant improvements:
            </p>
            
            <ul style="margin:0;padding-left:25px;font-size:14px;line-height:2;">
                <li><strong>30-50% reduction</strong> in image file sizes</li>
                <li><strong>20-40% faster</strong> page load times</li>
                <li><strong>Improved SEO</strong> rankings due to better Core Web Vitals</li>
                <li><strong>Reduced bandwidth</strong> usage and hosting costs</li>
                <li><strong>Better user experience</strong> especially on mobile devices</li>
            </ul>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="bz-card">
        <h2>❓ Need Help?</h2>
        
        <p style="margin: 0 0 15px 0; font-size:15px;">
            If you encounter any issues during bulk optimization:
        </p>
        
        <ul style="margin: 0; padding-left: 20px; font-size:14px; line-height:2;">
            <li>Make sure your server has enough memory (256MB+ recommended)</li>
            <li>Check that PHP extensions (GD or Imagick) are installed and enabled</li>
            <li>Try optimizing in smaller batches if you have a very large library</li>
            <li>Increase PHP max_execution_time if you experience timeout errors</li>
            <li>Contact your hosting provider if optimization consistently fails</li>
        </ul>
        
        <div style="margin-top: 25px; padding: 20px; background: linear-gradient(135deg, #e7f5ff 0%, #d4edff 100%); border-radius: 8px; border-left:4px solid #2271b1;">
            <strong style="color:#2271b1;">📧 Support:</strong> Need assistance? Check the 
            <a href="<?php echo admin_url('admin.php?page=bz-image-optimizer'); ?>">System Information</a> 
            section in Settings to verify your server configuration.
        </div>
    </div>
</div>