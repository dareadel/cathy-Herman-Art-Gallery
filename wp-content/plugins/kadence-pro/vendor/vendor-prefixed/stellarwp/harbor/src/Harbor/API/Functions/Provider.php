<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare( strict_types=1 );

namespace KadenceWP\KadencePro\LiquidWeb\Harbor\API\Functions;

use KadenceWP\KadencePro\LiquidWeb\Harbor\Contracts\Abstract_Provider;
use KadenceWP\KadencePro\LiquidWeb\Harbor\Harbor;

/**
 * Registers global (non-namespaced) Harbor helper functions.
 *
 * @since 1.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		require_once dirname( __DIR__, 2 ) . '/global-functions.php';
		Global_Function_Registry::register( Harbor::VERSION );
	}
}
