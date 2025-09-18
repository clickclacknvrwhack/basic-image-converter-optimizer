<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('bio_webp_quality');
delete_option('bio_avif_quality');
delete_option('bio_enable_webp');
delete_option('bio_enable_avif');
delete_option('bio_plugin_version');

// Clean up post meta (optional - you might want to keep this)
// global $wpdb;
// $wpdb->delete($wpdb->postmeta, array('meta_key' => '_bio_original_size'));
// $wpdb->delete($wpdb->postmeta, array('meta_key' => '_bio_optimized_format'));

// Note: This doesn't delete the actual optimized image files
// You might want to add that functionality based on user preference