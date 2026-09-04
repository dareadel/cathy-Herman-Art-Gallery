<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare( strict_types=1 );

namespace KadenceWP\KadencePro\LiquidWeb\Harbor\Portal\Clients;

use KadenceWP\KadencePro\LiquidWeb\Harbor\Portal\Catalog_Collection;
use WP_Error;

/**
 * Contract for the product catalog API client.
 *
 * @since 1.0.0
 */
interface Portal_Client {

	/**
	 * Fetch the full catalog for all products.
	 *
	 * @since 1.0.0
	 *
	 * @return Catalog_Collection|WP_Error
	 */
	public function get_catalog();
}
