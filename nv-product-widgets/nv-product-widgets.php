<?php
/**
 * Plugin Name: AS Product Widgets
 * Description: Lightweight Elementor widgets for WooCommerce product pages. Built for Nordiska Varuhuset. v1.7.57 adds the "Headline style" control to NV: Media + Headline + Text (the last widget that was missing it) and adds self-hosted video (MP4/WebM) as an alternative to the still image on both NV: Media + Headline + Text and NV: Feature / Image + Text, with poster + autoplay/loop/muted/controls options.
 * Version: 1.7.57
 * Author: Alpha Studio
 * Text Domain: nv-product-widgets
 * Requires Plugins: elementor, woocommerce
 * Plugin URI: https://alphaecom.org
 * Author URI: https://alphaecom.org
 * License: Proprietary
 */

if (!defined('ABSPATH')) exit;

define('NV_PW_VERSION', '1.7.57');
define('NV_PW_FILE', __FILE__);
define('NV_PW_DIR', plugin_dir_path(__FILE__));
define('NV_PW_URL', plugin_dir_url(__FILE__));

add_filter('plugin_action_links_' . plugin_basename(__FILE__), static function(array $links): array {
    $settings_url = admin_url('admin.php?page=nv-pw-module-settings');
    $templates_url = admin_url('admin.php?page=nv-product-templates');
    array_unshift(
        $links,
        '<a href="' . esc_url($settings_url) . '">' . esc_html__('Inställningar', 'nv-product-widgets') . '</a>',
        '<a href="' . esc_url($templates_url) . '">' . esc_html__('Product Templates', 'nv-product-widgets') . '</a>'
    );
    return $links;
});

// Guarantee .avif uploads to the media library (WordPress 6.5+ supports AVIF
// natively; this makes it work on hosts/older setups where it isn't enabled,
// so image-marker widgets can use .avif icons).
add_filter('upload_mimes', static function(array $mimes): array {
    if (!isset($mimes['avif'])) {
        $mimes['avif'] = 'image/avif';
    }
    return $mimes;
});
add_filter('wp_check_filetype_and_ext', static function(array $data, $file, $filename, $mimes = null) {
    if (!empty($data['ext']) && !empty($data['type'])) {
        return $data;
    }
    $ext = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
    if ($ext === 'avif') {
        $data['ext']  = 'avif';
        $data['type'] = 'image/avif';
    }
    return $data;
}, 10, 4);

add_action('plugins_loaded', function() {
    if (!did_action('elementor/loaded') || !class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>AS Product Widgets requires Elementor and WooCommerce.</p></div>';
        });
        return;
    }
    require_once NV_PW_DIR . 'includes/class-nv-pw-loader.php';
    NV_PW_Loader::init();
});
