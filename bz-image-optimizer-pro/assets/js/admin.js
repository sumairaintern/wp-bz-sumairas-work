jQuery(document).ready(function($) {
    
    // Optimize single image
    $(document).on('click', '.bz-optimize-now', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const imageId = btn.data('id');
        const originalText = btn.text();
        
        btn.prop('disabled', true).html('<span class="bz-spinner"></span> Optimizing...');
        
        $.ajax({
            url: bzOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'bz_optimize_image',
                nonce: bzOptimizer.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    const savings = response.data.savings_percent;
                    const color = savings > 30 ? '#00a32a' : (savings > 15 ? '#f0b849' : '#999');
                    
                    btn.parent().html(
                        '<div class="bz-opt-badge" style="background:' + color + ';color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;">' +
                        savings.toFixed(1) + '%</div>' +
                        '<button class="button button-small bz-view-details" data-id="' + imageId + '" style="margin-left:5px;">Details</button>'
                    );
                    
                    showNotice('Image optimized successfully! Saved ' + savings.toFixed(1) + '%', 'success');
                } else {
                    btn.prop('disabled', false).text(originalText);
                    showNotice('Optimization failed: ' + response.data, 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).text(originalText);
                showNotice('An error occurred during optimization', 'error');
            }
        });
    });
    
    // View optimization details (modal)
    $(document).on('click', '.bz-view-details', function(e) {
        e.preventDefault();
        const imageId = $(this).data('id');
        
        // Trigger WordPress media modal or custom implementation
        alert('Viewing details for image ID: ' + imageId + '\n\nClick "Edit" in the media library to see full optimization details including:\n- New filesize\n- Original filesize\n- Savings percentage\n- Compression level\n- WebP/AVIF generation\n- Thumbnails optimized\n- Restore & Re-optimize options');
    });
    
    // Restore original image
    $(document).on('click', '.bz-restore-original', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to restore the original image? This will remove all optimizations.')) {
            return;
        }
        
        const btn = $(this);
        const imageId = btn.data('id');
        const originalText = btn.text();
        
        btn.prop('disabled', true).html('<span class="bz-spinner"></span> Restoring...');
        
        $.ajax({
            url: bzOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'bz_restore_image',
                nonce: bzOptimizer.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Image restored successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    btn.prop('disabled', false).html(originalText);
                    showNotice('Restore failed: ' + response.data, 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalText);
                showNotice('An error occurred during restore', 'error');
            }
        });
    });
    
    // Re-optimize image
    $(document).on('click', '.bz-reoptimize', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const imageId = btn.data('id');
        const originalText = btn.text();
        
        btn.prop('disabled', true).html('<span class="bz-spinner"></span> Re-optimizing...');
        
        $.ajax({
            url: bzOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'bz_optimize_image',
                nonce: bzOptimizer.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Image re-optimized successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    btn.prop('disabled', false).html(originalText);
                    showNotice('Re-optimization failed: ' + response.data, 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalText);
                showNotice('An error occurred during re-optimization', 'error');
            }
        });
    });
    
    // Bulk optimization
    let bulkInProgress = false;
    let processedCount = 0;
    let totalCount = 0;
    
    $('#bz-start-bulk').on('click', function(e) {
        e.preventDefault();
        
        if (bulkInProgress) {
            return;
        }
        
        if (!confirm('This will optimize all unoptimized images in your media library. This may take several minutes depending on the number of images. Continue?')) {
            return;
        }
        
        bulkInProgress = true;
        processedCount = 0;
        
        $(this).prop('disabled', true).html('<span class="bz-spinner"></span> Processing...');
        $('#bz-bulk-progress').show();
        
        processBulkBatch(0);
    });
    
    function processBulkBatch(offset) {
        $.ajax({
            url: bzOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'bz_bulk_optimize',
                nonce: bzOptimizer.nonce,
                offset: offset
            },
            success: function(response) {
                if (response.success) {
                    processedCount = response.data.processed;
                    totalCount = response.data.total;
                    
                    const percentage = Math.round((processedCount / totalCount) * 100);
                    
                    $('#bz-progress-fill').css('width', percentage + '%').text(percentage + '%');
                    $('#bz-progress-text').text('Processed ' + processedCount + ' of ' + totalCount + ' images');
                    
                    // Add results to log
                    response.data.results.forEach(function(result) {
                        const timestamp = new Date().toLocaleTimeString();
                        $('#bz-bulk-log').prepend(
                            '<div style="padding:8px;border-bottom:1px solid #ddd;">' +
                            '<strong>[' + timestamp + ']</strong> Optimized image - Saved ' + result.savings_percent.toFixed(1) + '% ' +
                            '(Original: ' + formatBytes(result.original_size) + ' → New: ' + formatBytes(result.new_size) + ')' +
                            '</div>'
                        );
                    });
                    
                    if (!response.data.complete) {
                        // Continue processing
                        setTimeout(function() {
                            processBulkBatch(processedCount);
                        }, 500);
                    } else {
                        // Bulk optimization complete
                        bulkInProgress = false;
                        $('#bz-start-bulk').prop('disabled', false).text('Start Bulk Optimization');
                        showNotice('🎉 Bulk optimization completed! Processed ' + processedCount + ' images successfully.', 'success');
                    }
                } else {
                    bulkInProgress = false;
                    $('#bz-start-bulk').prop('disabled', false).text('Start Bulk Optimization');
                    showNotice('Bulk optimization failed: ' + response.data, 'error');
                }
            },
            error: function() {
                bulkInProgress = false;
                $('#bz-start-bulk').prop('disabled', false).text('Start Bulk Optimization');
                showNotice('An error occurred during bulk optimization', 'error');
            }
        });
    }
    
    // Helper function to format bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Show notification
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : (type === 'error' ? 'notice-error' : 'notice-warning');
        
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible" style="margin:15px 0;"><p>' + message + '</p></div>');
        
        if ($('.bz-optimizer-wrap').length) {
            $('.bz-optimizer-wrap').prepend(notice);
        } else {
            $('.wrap').prepend(notice);
        }
        
        // Add dismiss button functionality
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to top to show notice
        $('html, body').animate({
            scrollTop: 0
        }, 300);
    }
    
    // Settings form submission (if you want AJAX submission)
    $('#bz-settings-form').on('submit', function(e) {
        // Let the form submit normally for now
        // You can add AJAX submission here if needed
    });
    
});