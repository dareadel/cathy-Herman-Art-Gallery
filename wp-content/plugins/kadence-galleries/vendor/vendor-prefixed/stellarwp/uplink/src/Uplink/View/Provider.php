<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by kadencewp on 08-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare( strict_types=1 );

namespace KadenceWP\KadenceGalleries\StellarWP\Uplink\View;

use KadenceWP\KadenceGalleries\StellarWP\Uplink\Contracts\Abstract_Provider;
use KadenceWP\KadenceGalleries\StellarWP\Uplink\View\Contracts\View;

final class Provider extends Abstract_Provider {

	/**
	 * Configure the View Renderer.
	 */
	public function register() {
		$this->container->singleton(
			WordPress_View::class,
			new WordPress_View( __DIR__ . '/../../views' )
		);

		$this->container->bind( View::class, $this->container->get( WordPress_View::class ) );
	}
}
