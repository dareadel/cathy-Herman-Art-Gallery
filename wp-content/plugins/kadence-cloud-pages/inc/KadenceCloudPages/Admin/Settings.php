<?php
/**
 * The provider hooking Admin class methods to WordPress events.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */

namespace KadenceWP\CloudPages\Admin;

use Kadence_Settings_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider for all Admin related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */
class Settings {
	const OPT_NAME = 'kadence_cloud';

	/**
	 * Add sections to settings.
	 */
	public function add_sections() {
		if ( ! class_exists( 'Kadence_Settings_Engine' ) ) {
			return;
		}

		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id'     => 'kc_pages',
				'title'  => __( 'Pages', 'kadence-insights' ),
				'long_title'  => __( 'Pages Settings', 'kadence-insights' ),
				'desc'   => '',
				'priority' => 40,
				'fields' => array(
					array(
						'id'       => 'enable_pages',
						'type'     => 'switch',
						'title'    => __( 'Enable Pages with your libraries', 'kadence-insights' ),
						'help'     => __( 'This will add a pages tab to your library', 'kadence-insights' ),
						'default'  => 0,
					),
				),
			)
		);
	}
}
