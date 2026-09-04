<?php
/**
 * Plugin Name: Kadence Pattern Hub - Pages Addon
 * Plugin URI:  https://www.kadencewp.com/kadence-cloud/
 * Description: Extends the pattern hub to have a pages element.
 * Version:     1.1.0
 * Author:      Kadence WP
 * Author URI:  https://www.kadencewp.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: kadence-cloud-pages
 *
 * @package Kadence Cloud Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KADENCE_CLOUD_PAGES_PATH', realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'KADENCE_CLOUD_PAGES_URL', plugin_dir_url( __FILE__ ) );
define( 'KADENCE_CLOUD_PAGES_VERSION', '1.1.0' );

require_once KADENCE_CLOUD_PAGES_PATH . 'vendor/autoload.php';
require_once KADENCE_CLOUD_PAGES_PATH . 'vendor/vendor-prefixed/autoload.php';
require_once KADENCE_CLOUD_PAGES_PATH . 'inc/uplink/Helper.php';
require_once KADENCE_CLOUD_PAGES_PATH . 'inc/uplink/Connect.php';
require_once KADENCE_CLOUD_PAGES_PATH . 'inc/functions/app.php';
require_once KADENCE_CLOUD_PAGES_PATH . 'inc/cmb/init.php';
require_once KADENCE_CLOUD_PAGES_PATH . 'inc/cmb-post-search-field/cmb2_post_search_field.php';


/**
 * Boots the plugin.
 *
 * @since 0.1.0
 *
 * @return void
 */
add_action(
	'plugins_loaded',
	static function (): void {
		// Fully boot the plugin and its service providers.
		$core = kadence_cloud_pages_plugin();
		$core->init();
	}
);
