=== Basic Image Converter and Optimizer ===
Contributors: clickfoundry
Tags: images, webp, avif, optimization, imagemagick
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically creates WebP and AVIF versions of uploaded images using ImageMagick for better performance and smaller file sizes.

== Description ==

Basic Image Converter and Optimizer automatically creates optimized WebP and AVIF versions of your uploaded JPEG and PNG images using ImageMagick. This helps reduce bandwidth usage and improves page load times while keeping your original images unchanged.

= Features =

* Automatic conversion of JPEG and PNG uploads to WebP and AVIF formats
* Configurable quality settings for WebP and AVIF
* Server capability detection and reporting
* Enable/disable formats based on server support
* Debug logging for troubleshooting
* Media library integration showing optimized formats

= Requirements =

* PHP 7.4 or higher
* ImageMagick PHP extension
* WordPress 5.0 or higher

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/basic-image-converter-optimizer/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Image Optimizer to configure the plugin
4. Upload images to test the optimization

== Frequently Asked Questions ==

= What image formats are supported? =

The plugin processes JPEG and PNG uploads and can create WebP and AVIF versions if your server supports them.

= Will this replace my original images? =

No, the original images are kept unchanged. The optimized versions are created as separate files.

= What if my server doesn't support WebP or AVIF? =

The plugin will detect your server capabilities and only create formats that are supported. You can see the server capabilities in the plugin settings.

== Screenshots ==

1. Plugin settings page showing format options and server capabilities
2. Media library showing optimized format indicators

== Changelog ==

= 1.0.0 =
* Initial release
* WebP and AVIF conversion support
* Configurable quality settings
* Server capability detection

== Upgrade Notice ==

= 1.0.0 =
Initial release of Basic Image Converter and Optimizer.