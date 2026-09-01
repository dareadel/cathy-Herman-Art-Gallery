<?php
/**
 * Kadence_Galleries_Settings Class
 *
 * @package Kadence Galleries
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Kadence_Galleries_Settings class
 */
class Kadence_Galleries_Settings {
	const OPT_NAME = 'kt_galleries';

	/**
	 * Action on init.
	 */
	public function __construct() {
		require_once KTG_PATH . 'includes/settings/load.php';
		// Need to load this with priority higher then 10 so class is loaded.
		add_action( 'after_setup_theme', array( $this, 'add_sections' ), 20 );
	}
	/**
	 * Add sections to settings.
	 */
	public function add_sections() {
		if ( ! class_exists( 'Kadence_Settings_Engine' ) ) {
			return;
		}
		$args = array(
			'opt_name'                         => self::OPT_NAME,
			'menu_icon'                        => 'dashicons-cart',
			'menu_title'                       => __( 'Settings', 'kadence-galleries' ),
			'page_title'                       => __( 'Kadence Galleries', 'kadence-galleries' ),
			'page_slug'                        => 'kadence-galleries-settings',
			'page_permissions'                 => 'manage_options',
			'menu_type'                        => 'submenu',
			'page_parent'                      => ( apply_filters( 'kadence_galleries_network', false ) ? 'settings.php' : 'edit.php?post_type=kt_gallery' ),
			'page_priority'                    => null,
			'footer_credit'                    => '',
			'class'                            => '',
			'admin_bar'                        => false,
			'admin_bar_priority'               => 999,
			'admin_bar_icon'                   => '',
			'show_import_export'               => false,
			'version'                          => KTG_VERSION,
			'logo'                             => KTG_URL . 'assets/kadence-logo.png',
			'network_admin'                    => apply_filters( 'kadence_galleries_network', false ),
			'database'                         => ( apply_filters( 'kadence_galleries_network', false ) ? 'network' : '' ),
		);
		$args['sidebar'] = array(
			'facebook' => array(
				'title' => __( 'Web Creators Community', 'kadence-galleries' ),
				'description' => __( 'Join our community of fellow kadence users creating effective websites! Share your site, ask a question and help others.', 'kadence-galleries' ),
				'link' => 'https://www.facebook.com/groups/webcreatorcommunity',
				'link_text' => __( 'Join our Facebook Group', 'kadence-galleries' ),
			),
			'support' => array(
				'title' => __( 'Support', 'kadence-galleries' ),
				'description' => __( 'Have a question, we are happy to help! Get in touch with our support team.', 'kadence-galleries' ),
				'link' => 'https://www.kadencewp.com/premium-support-tickets/',
				'link_text' => __( 'Submit a Ticket', 'kadence-galleries' ),
			),
		);
		Kadence_Settings_Engine::set_args( self::OPT_NAME, $args );
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_gallery_settings',
				'title' => __( 'Gallery Settings', 'kadence-galleries' ),
				'desc' => '',
				'fields' => array(
					array(
						'id' => 'gallery_lightbox',
						'type' => 'select',
						'title' => __( 'Gallery Lightbox', 'kadence-galleries' ),
						'options' => array(
							'photoswipe' => __( 'Photoswipe', 'kadence-galleries' ),
							'magnific' => __( 'Magnific Popup', 'kadence-galleries' )
						),
						'default' => 'photoswipe',
					),
					array(
						'id' => 'gallery_lightbox_skin',
						'type' => 'select',
						'title' => __( 'Gallery Lightbox Skin', 'kadence-galleries' ),
						'options' => array(
							'light' => __( 'Light', 'kadence-galleries' ),
							'dark' => __( 'Dark', 'kadence-galleries' )
						),
						'default' => 'light',
					),
					array(
						'id' => 'album_post_per_page',
						'type' => 'range',
						'title' => __( 'Gallery Albums, items per page', 'kadence-galleries' ),
						'default'   => '10',
						'min'       => '1',
						'step'      => '1',
						'max'       => '100',
					),
				),
			)
		);
	}
}
new Kadence_Galleries_Settings();
