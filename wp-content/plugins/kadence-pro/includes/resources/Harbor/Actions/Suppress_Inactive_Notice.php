<?php declare( strict_types=1 );

namespace KadenceWP\KadencePro\Harbor\Actions;

use KadenceWP\KadencePro\Uplink\Connect;

/**
 * Removes the legacy "not activated" admin notice from Connect.
 *
 * @since 1.2.0
 */
final class Suppress_Inactive_Notice {

	/**
	 * Removes the legacy "not activated" admin notice from Connect.
	 *
	 * Connect registers its inactive_notice callback during plugins_loaded,
	 * which runs before Harbor_Provider. Hooking to admin_init ensures the
	 * callback is already registered before we remove it.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function __invoke(): void {
		remove_action( 'admin_notices', [ Connect::get_instance(), 'inactive_notice' ] );
	}

}
