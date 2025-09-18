<?php

if (!defined('ABSPATH')) exit;

require_once dirname(__DIR__) . '/admin/class-bico-admin.php';


class BasicImageOptimizer {
    use BICO_Admin;

    /**
     * Debug logging helper - only logs when WP_DEBUG is enabled
     */
    private static function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log($message);
        }
    }
    
    /**
     * Instance debug logging helper
     */
    private function log_debug($message) {
        self::debug_log($message);
    }
    
    /**
     * Static activation method - called by WordPress
     */
    public static function on_activation() {
        if (!current_user_can('activate_plugins'))
            return;
        
        // Set default options
        add_option('bio_webp_quality', 85);
        add_option('bio_avif_quality', 80);
        add_option('bio_enable_webp', 1);
        add_option('bio_enable_avif', 1);
        add_option('bio_plugin_version', '1.0.0');
        
        // Log activation only if WP_DEBUG is true
        self::debug_log('[Basic Image Optimizer] Plugin activated');
    }
    
    /**
     * Static deactivation method - called by WordPress
     */
    public static function on_deactivation() {
        if (!current_user_can('activate_plugins'))
            return;
        
        // Clear any scheduled tasks (if you add them later)
        wp_clear_scheduled_hook('bio_cleanup_task');
        
        // Clear any cached data
        wp_cache_flush();
        
        // Log deactivation only if WP_DEBUG is true
        self::debug_log('[Basic Image Optimizer] Plugin deactivated');
    }

    public function __construct() {
        //hook into file upload
        add_action('wp_handle_upload', array($this, 'optimize_uploaded_image'), 10, 2);
        //admin menu stuff
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Add media library columns
        add_filter('manage_media_columns', array($this, 'add_media_columns'));
        add_action('manage_media_custom_column', array($this, 'display_media_columns'), 10, 2);
    }
    
    
    public function add_media_columns($columns) {
        $columns['bio_format'] = 'Optimized Format';
        $columns['bio_savings'] = 'File Size';
        return $columns;
    }
    
    public function display_media_columns($column_name, $post_id) {
        if ($column_name == 'bio_format') {
            $format = get_post_meta($post_id, '_bio_optimized_format', true);
            if ($format) {
                echo '<span style="background: #2271b1; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html(strtoupper($format)) . '</span>';
            }
        }
        
        if ($column_name == 'bio_savings') {
            $optimized_size = get_post_meta($post_id, '_bio_original_size', true);
            if ($optimized_size) {
                echo esc_html(size_format($optimized_size));
            }
        }
    }
    
    
    public function admin_notices() {
        if (!$this->has_imagemagick()) {
            echo '<div class="notice notice-error"><p><strong>Image Optimizer:</strong> ImageMagick is not available on your server. Please contact your hosting provider to install the ImageMagick extension for PHP.</p></div>';
        }
    }
    
    protected function has_imagemagick() {
        return extension_loaded('imagick') && class_exists('Imagick');
    }
    
    protected function has_webp_support() {
        if (!$this->has_imagemagick()) return false;
        $imagick = new Imagick();
        return in_array('WEBP', $imagick->queryFormats());
    }
    
    protected function has_avif_support() {
        if (!$this->has_imagemagick()) return false;
        $imagick = new Imagick();
        return in_array('AVIF', $imagick->queryFormats());
    }
    
    public function optimize_uploaded_image($upload, $context) {
        // Debug logging only if WP_DEBUG is true
        $this->log_debug('Bio Plugin: Upload hook triggered');
        $this->log_debug('Bio Plugin: Upload data: ' . print_r($upload, true));
        
        if (!isset($upload['file']) || !$this->has_imagemagick()) {
            $this->log_debug('Bio Plugin: Missing file or ImageMagick not available');
            return $upload;
        }
        
        $file_path = $upload['file'];
        $file_info = pathinfo($file_path);
        
        $this->log_debug('Bio Plugin: Processing file: ' . $file_path);
        $this->log_debug('Bio Plugin: File extension: ' . $file_info['extension']);
        
        // Only process images
        if (!in_array(strtolower($file_info['extension']), array('jpg', 'jpeg', 'png'))) {
            $this->log_debug('Bio Plugin: File extension not supported');
            return $upload;
        }
        
        $webp_quality = get_option('bio_webp_quality', 85);
        $avif_quality = get_option('bio_avif_quality', 80);
        $enable_webp = get_option('bio_enable_webp', 1);
        $enable_avif = get_option('bio_enable_avif', 1);
        
        $this->log_debug('Bio Plugin: WebP enabled: ' . ($enable_webp ? 'yes' : 'no'));
        $this->log_debug('Bio Plugin: AVIF enabled: ' . ($enable_avif ? 'yes' : 'no'));
        
        // Create WebP version if supported and enabled
        if ($enable_webp && $this->has_webp_support()) {
            $webp_result = $this->create_webp($file_path, $webp_quality);
            $this->log_debug('Bio Plugin: WebP creation result: ' . ($webp_result ? 'success' : 'failed'));
            
            if ($webp_result) {
                $this->add_to_media_library($file_path, 'webp', $upload);
            }
        }
        
        // Create AVIF version if supported and enabled
        if ($enable_avif && $this->has_avif_support()) {
            $avif_result = $this->create_avif($file_path, $avif_quality);
            $this->log_debug('Bio Plugin: AVIF creation result: ' . ($avif_result ? 'success' : 'failed'));
            
            if ($avif_result) {
                $this->add_to_media_library($file_path, 'avif', $upload);
            }
        }
        
        return $upload;
    }
    
    protected function add_to_media_library($original_path, $format, $original_upload) {
        $optimized_path = preg_replace('/\.(jpe?g|png)$/i', '.' . $format, $original_path);
        
        if (!file_exists($optimized_path)) {
            return false;
        }
        
        // Get file info
        $file_info = pathinfo($optimized_path);
        $uploads_dir = wp_upload_dir();
        
        // Calculate relative path for URL
        $relative_path = str_replace($uploads_dir['basedir'], '', $optimized_path);
        $file_url = $uploads_dir['baseurl'] . $relative_path;
        
        // Get file size
        $file_size = filesize($optimized_path);
        
        // Prepare attachment data
        $attachment = array(
            'guid' => $file_url,
            'post_mime_type' => 'image/' . $format,
            'post_title' => $file_info['filename'] . ' (' . strtoupper($format) . ')',
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $optimized_path);
        
        if ($attachment_id) {
            // Generate metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $optimized_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            // Add custom meta to track this as an optimized version
            update_post_meta($attachment_id, '_bio_optimized_format', $format);
            update_post_meta($attachment_id, '_bio_original_size', $file_size);
            
            $this->log_debug('Bio Plugin: Added ' . $format . ' to media library with ID: ' . $attachment_id);
            return $attachment_id;
        }
        
        return false;
    }
    
    protected function create_webp($source_path, $quality) {
        try {
            $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source_path);
            
            $imagick = new Imagick();
            $imagick->readImage($source_path);
            
            // Set format and quality
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality($quality);
            
            // Write the WebP file
            $result = $imagick->writeImage($webp_path);
            $imagick->clear();
            $imagick->destroy();
            
            return $result;
            
        } catch (Exception $e) {
            $this->log_debug('WebP creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    protected function create_avif($source_path, $quality) {
        try {
            $avif_path = preg_replace('/\.(jpe?g|png)$/i', '.avif', $source_path);
            
            $imagick = new Imagick();
            $imagick->readImage($source_path);
            
            // Set format and quality
            $imagick->setImageFormat('avif');
            $imagick->setImageCompressionQuality($quality);
            
            // Write the AVIF file
            $result = $imagick->writeImage($avif_path);
            $imagick->clear();
            $imagick->destroy();
            
            return $result;
            
        } catch (Exception $e) {
            $this->log_debug('AVIF creation failed: ' . $e->getMessage());
            return false;
        }
    }
   
}