<?php
/**
 * The provider hooking Admin class methods to WordPress events.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */

namespace KadenceWP\CreativeKit\Admin;

use KadenceWP\CreativeKit\Contracts\Service_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider for all Admin related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */
class Provider extends Service_Provider {

	/**
	 * {@inheritdoc}
	 */
	public function register(): void {
		// Always load translations.
		add_action( 'plugins_loaded', $this->container->callback( Translations::class, 'load_textdomain' ) );
		// Register the settings.
		add_action( 'kadence_blocks_admin_before_home', $this->container->callback( Settings::class, 'add_license_area_to_blocks_home' ), 20 );

		add_action( 'admin_print_styles-kadence_page_kadence-blocks-home',  $this->container->callback( Settings::class, 'scripts' ) );

        add_action( 'rest_api_init', $this->container->callback( Designs_REST_Controller::class, 'register_routes' ) );
    }
}
