<?php
/**
 * The provider hooking Admin class methods to WordPress events.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */

namespace KadenceWP\CreativeKit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider for all Admin related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */
class Settings {

	/**
	 * Plugin specific text-domain loader.
	 *
	 * @return void
	 */
	public function add_license_area_to_blocks_home() {
		echo '<div class="kadence-creative-kit-license-container">';
			echo '<div class="kadence-creative-kit-license-controls"></div>';
			echo '<div id="kadence-creative-kit-license-controls" class="kadence-creative-kit-license-wrap hidden-creative-license-panel">';
				// Render the license input.
				do_action( 'kadence_creative_kit_render_license_input' );
			echo '</div>';
		echo '</div>';
	}
	/**
	 * Get the asset file produced by wp scripts.
	 *
	 * @param string $filepath the file path.
	 * @return array
	 */
	public function get_asset_file( $filepath ) {
		$asset_path = KADENCE_CREATIVE_KIT_PATH . $filepath . '.asset.php';

		return file_exists( $asset_path )
			? include $asset_path
			: array(
				'dependencies' => array( 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ),
				'version'      => KADENCE_CREATIVE_KIT_VERSION,
			);
	}
	/**
	 * Add analytics scripts.
	 */
	public function scripts() {
		$plugin_asset_meta = $this->get_asset_file( 'build/admin-dash' );
		// Register the script.
		wp_enqueue_script(
			'kadence-creative-admin',
			KADENCE_CREATIVE_KIT_URL . 'build/admin-dash.js',
			$plugin_asset_meta['dependencies'],
			$plugin_asset_meta['version'],
			true
		);
		wp_enqueue_style(
			'kadence-creative-admin',
			KADENCE_CREATIVE_KIT_URL . 'build/admin-dash.css',
			['wp-components'],
			$plugin_asset_meta['version']
		);
		$data = array(
			'title' => esc_html__( 'Kadence Creative Kit', 'kadence-creative-kit' ),
			'subtitle' => esc_html__( '- Premium Design Library', 'kadence-creative-kit' ),
			'license' => 'hidden-toggle',
		);
		$data['help'] = array(
			'facebook' => array(
				'title' => __( 'Web Creators Community', 'kadence-creative-kit' ),
				'description' => __( 'Join our community of fellow kadence users creating effective websites! Share your site, ask a question and help others.', 'kadence-creative-kit' ),
				'link' => 'https://www.facebook.com/groups/webcreatorcommunity',
				'link_text' => __( 'Join our Facebook Group', 'kadence-creative-kit' ),
			),
			'docs' => array(
				'title' => __( 'Documentation', 'kadence-creative-kit' ),
				'description' => __( 'Need help? We have a knowledge base full of articles to get you started.', 'kadence-creative-kit' ),
				'link' => 'https://www.kadencewp.com/help-center/',
				'link_text' => __( 'Browse Docs', 'kadence-creative-kit' ),
			),
			'support' => array(
				'title' => __( 'Support', 'kadence-creative-kit' ),
				'description' => __( 'Have a question, we are happy to help! Get in touch with our support team.', 'kadence-creative-kit' ),
				'link' => 'https://www.kadencewp.com/premium-support-tickets/',
				'link_text' => __( 'Submit a Ticket', 'kadence-creative-kit' ),
			),
		);
		$data['changelog'] = $this->get_changelog( KADENCE_CREATIVE_KIT_PATH . 'changelog.txt' );
		wp_localize_script(
			'kadence-creative-admin',
			'kadenceCreativeParams',
			[
				'args' => wp_json_encode( apply_filters( 'kadence_admin_creative_kit_enqueue_args', $data ) ),
			]
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'kadence-creative-admin', 'kadence-creative-kit' );
		}
	}
	/**
	 * Get Changelog
	 *
	 * @param $changelog_path the path to the change log.
	 */
	public function get_changelog( $changelog_path ) {
		$changelog = array();
		if ( empty( $changelog_path ) ) {
			return $changelog;
		}
		if ( ! is_file( $changelog_path ) ) {
			return $changelog;
		}
		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$changelog_string = $wp_filesystem->get_contents( $changelog_path );
		if ( is_wp_error( $changelog_string ) ) {
			return $changelog;
		}
		$changelog = explode( PHP_EOL, $changelog_string );
		$releases  = [];
		foreach ( $changelog as $changelog_line ) {
			if ( empty( $changelog_line ) ) {
				continue;
			}
			if ( substr( ltrim( $changelog_line ), 0, 2 ) === '==' ) {
				if ( isset( $release ) ) {
					$releases[] = $release;
				}
				$changelog_line = trim( str_replace( '=', '', $changelog_line ) );
				$release = array(
					'head'    => $changelog_line,
				);
			} else {
				if ( preg_match( '/[*|-]?\s?(\[fix]|\[Fix]|fix|Fix)[:]?\s?\b/', $changelog_line ) ) {
					//$changelog_line     = preg_replace( '/[*|-]?\s?(\[fix]|\[Fix]|fix|Fix)[:]?\s?\b/', '', $changelog_line );
					$changelog_line = trim( str_replace( [ '*', '-' ], '', $changelog_line ) );
					$release['fix'][] = $changelog_line;
					continue;
				}

				if ( preg_match( '/[*|-]?\s?(\[add]|\[Add]|add|Add)[:]?\s?\b/', $changelog_line ) ) {
					//$changelog_line        = preg_replace( '/[*|-]?\s?(\[add]|\[Add]|add|Add)[:]?\s?\b/', '', $changelog_line );
					$changelog_line = trim( str_replace( [ '*', '-' ], '', $changelog_line ) );
					$release['add'][] = $changelog_line;
					continue;
				}
				$changelog_line = trim( str_replace( [ '*', '-' ], '', $changelog_line ) );
				$release['update'][] = $changelog_line;
			}
		}
		return $releases;
	}
	
}
