<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by kadencewp on 08-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\KadenceGalleries\StellarWP\Uplink\Messages;

class Unlicensed extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
        $message  = '<div class="notice notice-warning"><p>';
        $message .= esc_html__( 'No license entered.', '%TEXTDOMAIN%' );
        $message .= '</p></div>';

		return $message;
	}
}
