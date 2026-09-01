<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by kadencewp on 08-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\KadenceGalleries\StellarWP\Uplink\Resources;

class Service extends Resource {
	/**
	 * @inheritDoc
	 */
	protected $type = 'service';

	/**
	 * @inheritDoc
	 */
	public static function register( $slug, $name, $version, $path, $class, string $license_class = null ) {
		return parent::register_resource( static::class, $slug, $name, $version, $path, $class, $license_class );
	}
}
