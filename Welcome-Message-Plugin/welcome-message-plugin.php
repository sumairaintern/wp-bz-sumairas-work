<?php
/**
 * Plugin Name: Welcome Message Plugin
 * Description: Displays a welcome message at the top of your website and blog pages
 * Version: 1.1
 * Author: sumairanoreen
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add welcome message to the top of the site
function wmp_display_welcome_message() {
    // Show on homepage OR blog page OR single posts
    if (is_front_page() || is_home() || is_single()) {
        echo '<div style="background: #4c5eafff; color: white; padding: 15px; text-align: center; font-size: 18px; border-bottom: 2px solid #3a4ccc;">
              🎉 Welcome to our website! Thanks for visiting! 🎉
              </div>';
    }
}
add_action('wp_head', 'wmp_display_welcome_message');

// Add welcome message to admin dashboard
function wmp_admin_welcome_message() {
    echo '<div class="notice notice-success is-dismissible">
          <p>Welcome Message Plugin is active! Your welcome message is showing on the homepage and blog pages.</p>
          </div>';
}
add_action('admin_notices', 'wmp_admin_welcome_message');

// Add settings page for the plugin
function wmp_add_settings_page() {
    add_options_page(
        'Welcome Message Settings',
        'Welcome Message',
        'manage_options',
        'welcome-message-plugin',
        'wmp_render_settings_page'
    );
}
add_action('admin_menu', 'wmp_add_settings_page');

// Render the settings page
function wmp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Welcome Message Plugin Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wmp_settings');
            do_settings_sections('welcome-message-plugin');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Initialize plugin settings
function wmp_settings_init() {
    register_setting('wmp_settings', 'wmp_settings');
    
    add_settings_section(
        'wmp_section',
        'Message Display Settings',
        'wmp_section_callback',
        'welcome-message-plugin'
    );
    
    add_settings_field(
        'wmp_show_on_blog',
        'Show on Blog Pages',
        'wmp_show_on_blog_render',
        'welcome-message-plugin',
        'wmp_section'
    );
    
    add_settings_field(
        'wmp_custom_message',
        'Custom Welcome Message',
        'wmp_custom_message_render',
        'welcome-message-plugin',
        'wmp_section'
    );
}
add_action('admin_init', 'wmp_settings_init');

function wmp_section_callback() {
    echo '<p>Configure where and what welcome message to display.</p>';
}

function wmp_show_on_blog_render() {
    $options = get_option('wmp_settings');
    ?>
    <label>
        <input type='checkbox' name='wmp_settings[wmp_show_on_blog]' <?php checked(isset($options['wmp_show_on_blog']), 1); ?> value='1'>
        Display welcome message on blog pages and single posts
    </label>
    <?php
}

function wmp_custom_message_render() {
    $options = get_option('wmp_settings');
    $message = isset($options['wmp_custom_message']) ? $options['wmp_custom_message'] : '🎉 Welcome to our website! Thanks for visiting! 🎉';
    ?>
    <textarea name='wmp_settings[wmp_custom_message]' rows='3' cols='50' style='width: 100%; max-width: 500px;'><?php echo esc_textarea($message); ?></textarea>
    <p class="description">Enter your custom welcome message. HTML is allowed.</p>
    <?php
}

// Enhanced display function with settings
function wmp_display_enhanced_welcome_message() {
    $options = get_option('wmp_settings');
    $show_on_blog = isset($options['wmp_show_on_blog']) ? $options['wmp_show_on_blog'] : true;
    $custom_message = isset($options['wmp_custom_message']) ? $options['wmp_custom_message'] : '🎉 Welcome to our website! Thanks for visiting! 🎉';
    
    $should_display = false;
    
    // Check where to display
    if (is_front_page()) {
        $should_display = true;
    }
    
    if ($show_on_blog && (is_home() || is_single() || is_archive())) {
        $should_display = true;
    }
    
    if ($should_display) {
        echo '<div style="background: #4c5eafff; color: white; padding: 15px; text-align: center; font-size: 18px; border-bottom: 2px solid #3a4ccc; margin: 0;">';
        echo wp_kses_post($custom_message);
        echo '</div>';
    }
}

// Replace the original function with enhanced one
remove_action('wp_head', 'wmp_display_welcome_message');
add_action('wp_head', 'wmp_display_enhanced_welcome_message');

// Add a shortcode for displaying welcome message anywhere
function wmp_welcome_shortcode($atts) {
    $options = get_option('wmp_settings');
    $custom_message = isset($options['wmp_custom_message']) ? $options['wmp_custom_message'] : '🎉 Welcome to our website! Thanks for visiting! 🎉';
    
    $atts = shortcode_atts(array(
        'style' => 'inline'
    ), $atts);
    
    if ($atts['style'] === 'inline') {
        return '<span style="color: #4c5eafff; font-weight: bold;">' . wp_kses_post($custom_message) . '</span>';
    } else {
        return '<div style="background: #4c5eafff; color: white; padding: 10px; text-align: center; border-radius: 5px; margin: 10px 0;">' . wp_kses_post($custom_message) . '</div>';
    }
}
add_shortcode('welcome_message', 'wmp_welcome_shortcode');