<?php
/**
 * Plugin Name: Kadence Galleries
 * Description: Plugin for managing galleries.
 * Author: Kadence WP
 * Author URI: https://www.kadencewp.com/
 * Plugin URI: https://www.kadencewp.com/product/kadence-galleries/
 * Version: 1.3.3
 * License: GPLv2 - http://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.4
 *
 * @package Kadence Galleries
 */

/**
 * Load Translation
 */
function kt_galleries_load_textdomain() {
	load_plugin_textdomain( 'kadence-galleries', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'kt_galleries_load_textdomain' );

if ( ! defined( 'KTG_PATH' ) ) {
	define( 'KTG_PATH', realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
}
if ( ! defined( 'KTG_URL' ) ) {
	define( 'KTG_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'KTG_VERSION' ) ) {
	define( 'KTG_VERSION', '1.3.2' );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/vendor-prefixed/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

require_once KTG_PATH . 'class-kadence-galleries.php';
require_once KTG_PATH . 'includes/vendor/Container.php';
require_once KTG_PATH . 'includes/uplink/Helper.php';
require_once KTG_PATH . 'includes/uplink/Connect.php';
require_once KTG_PATH . 'admin/cmb/init.php';
require_once KTG_PATH . 'admin/cmb2-conditionals/cmb2-conditionals.php';
require_once KTG_PATH . 'admin/cmb_select2/cmb_select2.php';
require_once KTG_PATH . 'includes/media-category/media-taxonomies.php';
require_once KTG_PATH . 'includes/media-links/media-custom-links.php';
require_once KTG_PATH . 'includes/class-kadence-galleries-settings.php';
require_once KTG_PATH . 'includes/class-kadence-galleries-options.php';
require_once KTG_PATH . 'includes/kt_image_functions/class-kadence-image-processing.php';
require_once KTG_PATH . 'includes/kt_image_functions/kt-image_functions.php';

add_action( 'plugins_loaded', 'kt_galleries_plugin_loaded', 1 );
function kt_galleries_plugin_loaded() {
	\KadenceWP\KadenceGalleries\Uplink\Connect::get_instance();
	new KadenceWP\KadenceGalleries\Plugin();
}

