<?php

namespace KadenceWP\CloudPages\Uplink;

use KadenceWP\CloudPages\Container;
use KadenceWP\CloudPages\StellarWP\Uplink\Register;
use KadenceWP\CloudPages\StellarWP\Uplink\Config;
use KadenceWP\CloudPages\StellarWP\Uplink\Uplink;
use KadenceWP\CloudPages\StellarWP\Uplink\Resources\Collection;
use KadenceWP\CloudPages\StellarWP\Uplink\Admin\License_Field;
use function KadenceWP\CloudPages\StellarWP\Uplink\get_resource;
use function KadenceWP\CloudPages\StellarWP\Uplink\set_license_key;
use function KadenceWP\CloudPages\StellarWP\Uplink\get_license_key;
use function KadenceWP\CloudPages\StellarWP\Uplink\validate_license;
use function KadenceWP\CloudPages\StellarWP\Uplink\get_license_field;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Connect
 * @package KadenceWP\CloudPages\Uplink
 */
class Connect {

	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;
	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Class Constructor.
	 */
	public function __construct() {
		// Load licensing.
		add_action( 'plugins_loaded', [ $this, 'load_licensing' ], 2 );
	}
	/**
	 * Plugin specific text-domain loader.
	 *
	 * @return void
	 */
	public function load_licensing() {
		$container = new Container();
		Config::set_container( $container );
		Config::set_hook_prefix( 'kadence-cloud-pages' );
		Uplink::init();

		$plugin_slug    = 'kadence-cloud-pages';
		$plugin_name    = 'Kadence Pattern Hub - Pages';
		$plugin_version = KADENCE_CLOUD_PAGES_VERSION;
		$plugin_path    = 'kadence-cloud-pages/kadence-cloud-pages.php';
		$plugin_class   = Helper::class;
		$license_class  = Helper::class;

		Register::plugin(
			$plugin_slug,
			$plugin_name,
			$plugin_version,
			$plugin_path,
			$plugin_class,
			$license_class
		);
		add_filter(
			'stellarwp/uplink/kadence-cloud-pages/api_get_base_url',
			function( $url ) {
				return 'https://licensing.kadencewp.com';
			}
		);
		add_filter(
			'stellarwp/uplink/kadence-cloud-pages/messages/valid_key',
			function ( $message, $expiration ) {
				return esc_html__( 'Your license key is valid', 'kadence-cloud-pages' );
			},
			10,
			2
		);
	}
	
}
Connect::get_instance();
