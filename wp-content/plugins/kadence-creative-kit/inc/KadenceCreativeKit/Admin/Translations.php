<?php
/**
 * Handles all functionality related to the Database Events.
 *
 * @since 0.1.1
 *
 * @package KadenceWP\CreativeKit
 */

declare( strict_types=1 );

namespace KadenceWP\CreativeKit\Admin;

/**
 * Handles all functionality related to the settings page.
 *
 * @since 0.1.1
 *
 * @package KadenceWP\CreativeKit
 */
class Translations {

	/**
	 * Plugin specific text-domain loader.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// Load the default language files.
		load_plugin_textdomain( 'kadence-creative-kit', false, KADENCE_CREATIVE_KIT_PATH . 'languages/' );
	}
}
