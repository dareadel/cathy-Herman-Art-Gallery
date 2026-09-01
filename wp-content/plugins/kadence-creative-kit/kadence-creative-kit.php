<?php
/**
 * Plugin Name: Kadence Creative Kit
 * Description: Premium Designs, AI Page Generator.
 * Author: Kadence WP
 * Author URI: https://kadencewp.com
 * Version: 1.1.4
 * Text Domain: kadence-creative-kit
 * Domain Path: /languages
 * License: GPLv2-or-later
 * Requires at least: 6.4
 * Requires PHP: 7.4
 *
 * @package KadenceWP\CreativeKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'KADENCE_CREATIVE_KIT_PATH', realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'KADENCE_CREATIVE_KIT_URL', plugin_dir_url( __FILE__ ) );
define( 'KADENCE_CREATIVE_KIT_VERSION', '1.1.4' );
define( 'KADENCE_CREATIVE_KIT_FILE', __FILE__ );

require_once KADENCE_CREATIVE_KIT_PATH . 'vendor/autoload.php';
require_once KADENCE_CREATIVE_KIT_PATH . 'vendor/vendor-prefixed/autoload.php';
require_once KADENCE_CREATIVE_KIT_PATH . 'inc/uplink/Helper.php';
require_once KADENCE_CREATIVE_KIT_PATH . 'inc/uplink/Connect.php';
require_once KADENCE_CREATIVE_KIT_PATH . 'inc/functions/app.php';

/**
 * Boots the plugin.
 *
 * @since 0.1.0
 *
 * @return void
 */
add_action(
	'plugins_loaded',
	static function (): void {
		if ( defined( 'KADENCE_BLOCKS_VERSION' ) ) {
			// Fully boot the plugin and its service providers.
			$core = kadence_creative_kit_plugin();
			$core->init();
		} else {
			add_action( 'admin_notices', 'kadence_creative_kit_admin_notice_need_kadence_blocks' );
			add_action( 'admin_enqueue_scripts', 'kadence_creative_kit_admin_need_kadence_blocks_scripts' );
		}
	}
);

/**
 * Function to output admin scripts.
 *
 * @param object $hook page hook.
 */
function kadence_creative_kit_admin_need_kadence_blocks_scripts() {
		wp_register_script( 'kadence-blocks-install', KADENCE_CREATIVE_KIT_URL . 'assets/js/admin-activate.min.js', ['jquery'], KADENCE_CREATIVE_KIT_VERSION );
		wp_enqueue_style( 'kadence-blocks-install', KADENCE_CREATIVE_KIT_URL . 'assets/css/admin-activate.min.css', false, KADENCE_CREATIVE_KIT_VERSION );
	}
/**
 * Admin Notice
 */
function kadence_creative_kit_admin_notice_need_kadence_blocks() {
	if ( get_transient( 'kadence_blocks_pro_free_plugin_notice' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$installed_plugins = get_plugins();
	if ( ! isset( $installed_plugins['kadence-blocks/kadence-blocks.php'] ) ) {
		$button_label = esc_html__( 'Install Kadence Blocks', 'kadence-creative-kit' );
		$data_action  = 'install';
	} else {
		$button_label = esc_html__( 'Activate Kadence Blocks', 'kadence-creative-kit' );
		$data_action  = 'activate';
	}
	$install_link    = wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'install-plugin',
				'plugin' => 'kadence-blocks',
			),
			network_admin_url( 'update.php' )
		),
		'install-plugin_kadence-blocks'
	);
	$activate_nonce  = wp_create_nonce( 'activate-plugin_kadence-blocks/kadence-blocks.php' );
	$activation_link = self_admin_url( 'plugins.php?_wpnonce=' . $activate_nonce . '&action=activate&plugin=kadence-blocks%2Fkadence-blocks.php' );
	echo '<div class="notice notice-error is-dismissible kt-blocks-pro-notice-wrapper">';
	// translators: %s is a link to kadence block plugin.
	echo '<p>' . sprintf( esc_html__( 'Kadence Creative Kit requires %s to be active for all functions to work.', 'kadence-creative-kit' ) . '</p>', '<a target="_blank" href="https://wordpress.org/plugins/kadence-blocks/">Kadence Blocks</a>' );
	echo '<p class="submit">';
	echo '<a class="button button-primary kt-creative-install-blocks-btn" data-redirect-url="' . esc_url( admin_url( 'admin.php?page=kadence-blocks-home' ) ) . '" data-activating-label="' . esc_attr__( 'Activating...', 'kadence-creative-kit' ) . '" data-activated-label="' . esc_attr__( 'Activated', 'kadence-creative-kit' ) . '" data-installing-label="' . esc_attr__( 'Installing...', 'kadence-creative-kit' ) . '" data-installed-label="' . esc_attr__( 'Installed', 'kadence-creative-kit' ) . '" data-action="' . esc_attr( $data_action ) . '" data-install-url="' . esc_attr( $install_link ) . '" data-activate-url="' . esc_attr( $activation_link ) . '">' . esc_html( $button_label ) . '</a>';
	echo '</p>';
	echo '</div>';
	wp_enqueue_script( 'kadence-blocks-install' );
}
