<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by kadencewp on 08-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\KadenceGalleries\StellarWP\Uplink\Messages;

class Unreachable extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		$message = esc_html__( 'Sorry, key validation server is not available.', '%TEXTDOMAIN%' );

		return $message;
	}
}
