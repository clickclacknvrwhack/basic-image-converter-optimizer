<?php
trait BICO_Admin{

public function add_admin_menu() {
        add_options_page(
            'Image Optimizer Settings',
            'Image Optimizer',
            'manage_options',
            'basic-image-optimizer',
            array($this, 'options_page')
        );
    }
    
    public function settings_init() {
        register_setting('bio_settings', 'bio_webp_quality', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality')
        ));
        register_setting('bio_settings', 'bio_avif_quality', array(
            'type' => 'integer', 
            'sanitize_callback' => array($this, 'sanitize_quality')
        ));
        register_setting('bio_settings', 'bio_enable_webp', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        register_setting('bio_settings', 'bio_enable_avif', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        add_settings_section(
            'bio_settings_section',
            'Optimization Settings',
            null,
            'bio_settings'
        );
        
        add_settings_field(
            'bio_enable_webp',
            'Enable WebP',
            array($this, 'checkbox_field'),
            'bio_settings',
            'bio_settings_section',
            array('option_name' => 'bio_enable_webp')
        );
        
        add_settings_field(
            'bio_webp_quality',
            'WebP Quality (1-100)',
            array($this, 'number_field'),
            'bio_settings',
            'bio_settings_section',
            array('option_name' => 'bio_webp_quality', 'min' => 1, 'max' => 100)
        );
        
        add_settings_field(
            'bio_enable_avif',
            'Enable AVIF',
            array($this, 'checkbox_field'),
            'bio_settings',
            'bio_settings_section',
            array('option_name' => 'bio_enable_avif')
        );
        
        add_settings_field(
            'bio_avif_quality',
            'AVIF Quality (1-100)',
            array($this, 'number_field'),
            'bio_settings',
            'bio_settings_section',
            array('option_name' => 'bio_avif_quality', 'min' => 1, 'max' => 100)
        );
    }
    
    public function sanitize_quality($value) {
        $value = intval($value);
        return ($value >= 1 && $value <= 100) ? $value : 85;
    }
    
    public function sanitize_checkbox($value) {
        return !empty($value) ? 1 : 0;
    }
    
    public function checkbox_field($args) {
        $value = get_option($args['option_name'], 1);
        $disabled = '';
        
        // Disable checkbox if format not supported
        if ($args['option_name'] == 'bio_enable_webp' && !$this->has_webp_support()) {
            $disabled = 'disabled';
        }
        if ($args['option_name'] == 'bio_enable_avif' && !$this->has_avif_support()) {
            $disabled = 'disabled';
        }
        
        printf(
            '<input type="checkbox" id="%s" name="%s" value="1" %s %s />',
            esc_attr($args['option_name']),
            esc_attr($args['option_name']),
            checked(1, $value, false),
            esc_attr($disabled)
        );
    }
    
    public function number_field($args) {
        $value = get_option($args['option_name'], $args['option_name'] == 'bio_webp_quality' ? 85 : 80);
        printf(
            '<input type="number" id="%s" name="%s" value="%d" min="%d" max="%d" />',
            esc_attr($args['option_name']),
            esc_attr($args['option_name']),
            esc_attr($value),
            esc_attr($args['min']),
            esc_attr($args['max'])
        );
    }
    
    public function options_page() {
        ?>
<div class="wrap">
    <h1>Image Optimizer Settings</h1>

    <div class="notice notice-info">
        <p><strong>How it works:</strong> When you upload JPEG or PNG images, this plugin automatically creates
            optimized WebP and AVIF versions using ImageMagick. The original files are kept unchanged.</p>
    </div>

    <form action="options.php" method="post">
        <?php
                settings_fields('bio_settings');
                do_settings_sections('bio_settings');
                submit_button();
                ?>
    </form>

    <h2>Server Capabilities</h2>
    <table class="widefat">
        <tr>
            <td><strong>PHP Version:</strong></td>
            <td><?php echo esc_html(PHP_VERSION); ?></td>
        </tr>
        <tr>
            <td><strong>ImageMagick Extension:</strong></td>
            <td><?php echo $this->has_imagemagick() ? '✅ Available' : '❌ Not Available'; ?></td>
        </tr>
        <?php if ($this->has_imagemagick()): ?>
        <tr>
            <td><strong>ImageMagick Version:</strong></td>
            <td><?php 
                        $imagick = new Imagick();
                        $version = $imagick->getVersion();
                        echo esc_html(isset($version['versionString']) ? $version['versionString'] : 'Unknown');
                    ?></td>
        </tr>
        <tr>
            <td><strong>WebP Support:</strong></td>
            <td><?php echo $this->has_webp_support() ? '✅ Available' : '❌ Not Available'; ?></td>
        </tr>
        <tr>
            <td><strong>AVIF Support:</strong></td>
            <td><?php echo $this->has_avif_support() ? '✅ Available' : '❌ Not Available'; ?></td>
        </tr>
        <tr>
            <td><strong>Supported Formats:</strong></td>
            <td><?php 
                        $imagick = new Imagick();
                        $formats = $imagick->queryFormats();
                        $image_formats = array_filter($formats, function($format) {
                            return in_array($format, ['JPEG', 'PNG', 'WEBP', 'AVIF', 'GIF', 'TIFF']);
                        });
                        echo esc_html(implode(', ', $image_formats));
                    ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if (!$this->has_imagemagick()): ?>
    <div class="notice notice-error">
        <p><strong>ImageMagick Not Available:</strong></p>
        <p>This plugin requires the ImageMagick PHP extension. Please contact your hosting provider to install it, or
            use a hosting service that includes ImageMagick support.</p>
        <p>Most modern shared hosting providers (like SiteGround, Bluehost, etc.) include ImageMagick by default.</p>
    </div>
    <?php elseif (!$this->has_webp_support() && !$this->has_avif_support()): ?>
    <div class="notice notice-warning">
        <p><strong>Limited Format Support:</strong></p>
        <p>ImageMagick is installed but doesn't support WebP or AVIF formats. Your hosting provider may need to update
            their ImageMagick installation.</p>
    </div>
    <?php endif; ?>
    <h3>Testing & Debug</h3>
    <p>Upload a JPEG or PNG image to test if optimization is working. The optimized files are saved alongside the
        original and should appear in the Media Library after a refresh.</p>

    <div style="background: #f1f1f1; padding: 15px; margin: 15px 0;">
        <h4>Check Your Files:</h4>
        <p>Based on your last upload, check this folder:</p>
        <code>/wp-content/uploads/<?php echo esc_html(gmdate('Y/m')); ?>/</code>
        <p>You should see files like:</p>
        <ul>
            <li><code>your-image.jpg</code> (original)</li>
            <li><code>your-image.webp</code> (optimized)</li>
            <li><code>your-image.avif</code> (optimized)</li>
        </ul>
        <p><strong>Note:</strong> WordPress Media Library only shows the original files. The optimized versions are
            created for your website to serve automatically.</p>
    </div>

    <div style="background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107;">
        <h4>Recent Activity:</h4>
        <p>Check your debug log to see if images are being processed. Latest entries should show "Bio Plugin:" messages.
        </p>
    </div>

    <?php if ($this->has_imagemagick()): ?>
    <p><strong>Quick Test:</strong> Your ImageMagick supports these formats:
        <?php 
                $imagick = new Imagick();
                $formats = $imagick->queryFormats();
                $test_formats = array('JPEG', 'PNG', 'WEBP', 'AVIF');
                $format_output = array();
                foreach ($test_formats as $format) {
                    $color = in_array($format, $formats) ? 'green' : 'red';
                    $format_output[] = sprintf('<span style="color: %s;">%s</span>', esc_attr($color), esc_html($format));
                }
                echo wp_kses_post(implode(' ', $format_output));
            ?>
    </p>
    <?php endif; ?>
</div>
<?php
    }

}