<?php
/**
 * Application/Plugin specific functions.
 */

declare( strict_types=1 );

use KadenceWP\CloudPages\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the plugin singleton and ensure we only have a single
 * instance of our container.
 *
 * @return Core
 */
function kadence_cloud_pages_plugin(): Core {
	// This singleton will not use a new container if one is already set.
	return Core::instance();
}

/**
 * The Kadence Cloud Pages Application Container.
 *
 * @see kadence_cloud_pages_plugin()
 *
 * @note kadence_cloud_pages_plugin() must be called before this one.
 *
 * @return Container
 * @throws InvalidArgumentException
 */
function kadence_cloud_pages() {
	return Core::instance()->container();
}

/**
 * Log to the error log if WP_DEBUG is set to true.
 *
 * @param string|mixed[] $data The data to log.
 *
 * @return void
 */
function kadence_cloud_pages_log( $data ): void {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! function_exists( 'error_log' ) ) {
		return;
	}

	if ( is_array( $data ) ) {
		$data = print_r( $data, true );
	}

	error_log( $data );
}
