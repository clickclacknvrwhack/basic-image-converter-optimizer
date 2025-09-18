<?php
/**
 * Plugin Name: Basic Image Converter and Optimizer
 * Description: Automatically creates WebP and AVIF versions of uploaded images using ImageMagick
 * Version: 1.0.0
 * Author: Christian Sanchez
 * Author URI: https://clickfoundry.co
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: basic-image-converter-optimizer
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('BASIC_IMAGE_OPTIMIZER_VERSION', '1.0.0');
define('BASIC_OPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load the main plugin class
require_once BASIC_OPTIMIZER_PLUGIN_DIR . 'includes/class-basic-image-converter-optimizer.php';

register_activation_hook(__FILE__, array('BasicImageOptimizer', 'on_activation'));
register_deactivation_hook(__FILE__, array('BasicImageOptimizer', 'on_deactivation'));
// Initialize the plugin

new BasicImageOptimizer();