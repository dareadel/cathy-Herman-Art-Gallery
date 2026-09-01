<?php

namespace KadenceWP\CreativeKit\Uplink;

use KadenceWP\KadenceBlocks\StellarWP\Uplink\Register;
use KadenceWP\KadenceBlocks\StellarWP\Uplink\Config;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_resource;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\set_license_key;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_license_key;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_authorization_token;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_license_domain;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\is_authorized;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\validate_license;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_license_field;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Connect
 * @package KadenceWP\KadenceShopKit\Uplink
 */
class Connect {

	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;
	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Class Constructor.
	 */
	public function __construct() {
		// Load licensing.
		add_action( 'kadence_blocks_uplink_loaded', [ $this, 'load_licensing' ] );
	}
	/**
	 * Plugin specific text-domain loader.
	 *
	 * @return void
	 */
	public function load_licensing() {
		if ( ! class_exists( '\\KadenceWP\\KadenceBlocks\\StellarWP\\Uplink\\Register' ) ) {
			return;
		}

		$plugin_slug    = 'kadence-creative-kit';
		$plugin_name    = 'Kadence Creative Kit';
		$plugin_version = KADENCE_CREATIVE_KIT_VERSION;
		$plugin_path    = 'kadence-creative-kit/kadence-creative-kit.php';
		$plugin_class   = Helper::class;
		$license_class  = Helper::class;
		Register::plugin(
			$plugin_slug,
			$plugin_name,
			$plugin_version,
			$plugin_path,
			$plugin_class,
			$license_class
		);
		add_filter(
			'stellarwp/uplink/kadence-blocks/api_get_base_url',
			function( $url ) {
				return 'https://licensing.kadencewp.com';
			},
		);
		add_filter(
			'stellarwp/uplink/kadence-blocks/messages/valid_key',
			function ( $message, $expiration ) {
				return esc_html__( 'Your license key is valid', 'kadence-creative-kit' );
			},
			9,
			2
		);
		add_filter(
			'stellarwp/uplink/kadence-blocks/admin_js_source',
			function ( $url ) {
				return KADENCE_CREATIVE_KIT_URL . 'inc/uplink/admin-views/license-admin.js';
			},
			9
		);
		add_filter(
			'stellarwp/uplink/kadence-blocks/admin_css_source',
			function ( $url ) {
				return KADENCE_CREATIVE_KIT_URL . 'inc/uplink/admin-views/license-admin.css';
			},
			9
		);
		add_filter(
			'stellarwp/uplink/kadence-blocks/field-template_path',
			function ( $path, $uplink_path ) {
				return KADENCE_CREATIVE_KIT_PATH . 'inc/uplink/admin-views/field.php';
			},
			10,
			2
		);
		add_filter( 'stellarwp/uplink/kadence-blocks/license_field_html_render', array( $this, 'get_license_field_html' ), 12, 2 );
		add_action( 'admin_notices', array( $this, 'inactive_notice' ) );
		// Pass in script args if the license is valid or not.
		add_filter( 'kadence_admin_creative_kit_enqueue_args', array( $this, 'register_license_validation' ), 10 );
		add_action( 'kadence_creative_kit_render_license_input', array( $this, 'render_license_field' ) );
	}
	/**
	 * Register settings
	 */
	public function render_license_field() {
		?>
		<div class="license-section sidebar-section components-panel">
			<div class="components-panel__body is-opened">
				<?php
				get_license_field()->render_single( 'kadence-creative-kit' );
				?>
			</div>
		</div>
		<?php
	}
	/**
	 * Register settings.
	 */
	public function register_license_validation( $args ) {
		$key          = get_license_key( 'kadence-creative-kit' );
		$license_data = validate_license( 'kadence-creative-kit', $key );
		if ( isset( $license_data ) && is_object( $license_data ) && method_exists( $license_data, 'is_valid' ) && $license_data->is_valid() ) {
			$license_status = true;
		} else {
			$license_status = false;
		}
		$args['licenseActive'] = $license_status;
		return $args;
	}
	/**
	 * Get license field html.
	 */
	public function get_license_field_html( $field, $args ) {
		if ( isset( $args['plugin_slug'] ) && 'kadence-creative-kit' === $args['plugin_slug'] ) {
			$field = sprintf(
				'<div class="%6$s" id="%2$s" data-slug="%2$s" data-plugin="%9$s" data-plugin-slug="%10$s" data-action="%11$s">
					<fieldset class="stellarwp-uplink__settings-group">
						<div class="stellarwp-uplink__settings-group-inline">
						%12$s
						%13$s
						</div>
						<input type="%1$s" name="%3$s" value="%4$s" placeholder="%5$s" class="regular-text stellarwp-uplink__settings-field" />
						%7$s
					</fieldset>
					%8$s
				</div>',
				! empty( $args['value'] ) ? 'hidden' : 'text',
				esc_attr( $args['path'] ),
				esc_attr( $args['id'] ),
				esc_attr( $args['value'] ),
				esc_attr( __( 'License Key', 'kadence-creative-kit' ) ),
				esc_attr( $args['html_classes'] ?: '' ),
				$args['html'],
				'<input type="hidden" value="' . wp_create_nonce( 'stellarwp_uplink_group_' ) . '" class="wp-nonce" />',
				esc_attr( $args['plugin'] ),
				esc_attr( $args['plugin_slug'] ),
				esc_attr( Config::get_hook_prefix_underscored() ),
				! empty( $args['value'] ) ? '<input type="text" name="obfuscated-key" disabled value="' . $this->obfuscate_key( $args['value'] ) . '" class="regular-text stellarwp-uplink__settings-field-obfuscated" />' : '',
				! empty( $args['value'] ) ? '<button type="submit" class="button button-secondary stellarwp-uplink-license-key-field-clear">' . esc_html__( 'Clear', 'kadence-creative-kit' ) . '</button>' : ''
			);
		}
		return $field;
	}
	/**
	 * Obfuscate license key.
	 */
	public function obfuscate_key( $key ) {
		$start = 3;
		$length = mb_strlen( $key ) - $start - 3;
		$mask_string = preg_replace( '/\S/', 'X', $key );
		$mask_string = mb_substr( $mask_string, $start, $length );
		$input_string = substr_replace( $key, $mask_string, $start, $length );
		return $input_string;
	}
	/**
	 * Displays an inactive notice when the software is inactive.
	 */
	public function inactive_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['page'] ) && ( 'kadence-blocks-home' == $_GET['page'] ) ) {
			// For Now, clear when on the settings page.
			set_transient( 'kadence_creative_kit_license_status_check', false );
			return;
		}
		$valid_license   = false;
		// Add below once we've given time for everyones cache to update.
		// $plugin          = get_resource( 'kadence-creative-kit' );
		// if ( $plugin ) {
		// 	$valid_license = $plugin->has_valid_license();
		// }
		$key = get_license_key( 'kadence-creative-kit' );
		if ( ! empty( $key ) ) {
			// Check with transient first, if not then check with server.
			$status = get_transient( 'kadence_creative_kit_license_status_check' );
			if ( false === $status || ( strpos( $status, $key ) === false ) ) {
				$license_data = validate_license( 'kadence-creative-kit', $key );
				if ( isset( $license_data ) && is_object( $license_data ) && method_exists( $license_data, 'is_valid' ) && $license_data->is_valid() ) {
					$status = 'valid';
				} else {
					$status = 'invalid';
				}
				$status = $key . '_' . $status;
				set_transient( 'kadence_creative_kit_license_status_check', $status, WEEK_IN_SECONDS );
			}
			if ( strpos( $status, $key ) !== false ) {
				$valid_check = str_replace( $key . '_', '', $status );
				if ( 'valid' === $valid_check ) {
					$valid_license = true;
				}
			}
		}
		if ( ! $valid_license ) {
			echo '<div class="error">';
			echo '<p>' . __( 'Kadence Creative Kit has not been activated.', 'kadence-creative-kit' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=kadence-blocks-home&license=show' ) ) . '">' . __( 'Click here to activate.', 'kadence-creative-kit' ) . '</a></p>';
			echo '</div>';
		}
	}
}
Connect::get_instance();
