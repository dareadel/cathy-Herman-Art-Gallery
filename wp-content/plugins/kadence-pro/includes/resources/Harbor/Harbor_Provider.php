<?php declare( strict_types=1 );

namespace KadenceWP\KadencePro\Harbor;

use KadenceWP\KadencePro\Container;
use KadenceWP\KadencePro\Harbor\Actions\Render_Harbor_License_Notice;
use KadenceWP\KadencePro\Harbor\Actions\Report_Legacy_Licenses;
use KadenceWP\KadencePro\Harbor\Actions\Suppress_Inactive_Notice;
use KadenceWP\KadencePro\LiquidWeb\Harbor\Config as HarborConfig;
use KadenceWP\KadencePro\LiquidWeb\Harbor\Harbor;

/**
 * Wires the Harbor (Liquid Web Software Manager) integration into Kadence Pro.
 *
 * @since 1.2.0
 */
final class Harbor_Provider {

	/**
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register(): void {
		HarborConfig::set_plugin_basename( KTP_PLUGIN_BASENAME );
		HarborConfig::set_container( new Container() );

		// Kadence Theme Kit Pro is itself a premium plugin, so always declare a premium plugin exists.
		// Priority 5 to run before a free plugin callback.
		add_filter( 'lw_harbor/premium_plugin_exists', '__return_true', 5 );

		Harbor::init();

		add_filter( 'lw-harbor/legacy_licenses', new Report_Legacy_Licenses() );

		add_action(
			'stellarwp/uplink/kadence-theme-pro/license_field_after_form',
			new Render_Harbor_License_Notice()
		);

		add_action( 'admin_init', new Suppress_Inactive_Notice() );
	}

}
