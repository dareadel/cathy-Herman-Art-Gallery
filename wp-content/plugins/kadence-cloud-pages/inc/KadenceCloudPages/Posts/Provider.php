<?php
/**
 * The provider hooking Admin class methods to WordPress events.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */

namespace KadenceWP\CloudPages\Posts;

use KadenceWP\CloudPages\Contracts\Service_Provider;

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
class Provider extends Service_Provider {

	/**
	 * {@inheritdoc}
	 */
	public function register(): void {
		// Register the post type.
		add_action( 'init', $this->container->callback( Cloud_Pages::class, 'register_post_type' ), 2 );

		add_action( 'init', $this->container->callback( Cloud_Pages::class, 'maybe_flush_permalinks' ), 11 );

		add_action( 'admin_menu', $this->container->callback( Cloud_Pages::class, 'add_page_category_submenu' ), 10 );

		// add_action( 'admin_menu', $this->container->callback( Cloud_Pages::class, 'add_pages_submenu' ), 10 );

		add_action( 'cmb2_admin_init', $this->container->callback( Cloud_Pages::class, 'register_cmb2_pattern_metabox' ) );

		add_filter( 'parent_file', $this->container->callback( Cloud_Pages::class, 'set_page_categories_parent_menu' ) );

		add_filter( 'submenu_file', $this->container->callback( Cloud_Pages::class, 'set_page_categories_submenu_file' ) );
		// Build user permissions settings.
		add_filter( 'user_has_cap', $this->container->callback( Cloud_Pages::class, 'filter_post_type_user_caps' ) );

		add_filter( 'kadence_post_layout', $this->container->callback( Cloud_Pages::class, 'kadence_editor_single_layout' ), 99 );

		add_action( 'kadence_single', $this->container->callback( Cloud_Pages::class, 'kadence_custom_content_page_override' ), 1 );

		add_action( 'init', $this->container->callback( Cloud_Pages::class, 'custom_content_filter' ), 9 );

		add_action( 'rest_api_init', $this->container->callback( Cloud_Pages::class, 'register_api_endpoints' ) );

		add_filter( 'kadence_cloud_library_info_args', $this->container->callback( Cloud_Pages::class, 'filter_cloud_library_info_args' ) );

		add_action( 'admin_init', $this->container->callback( Cloud_Pages::class, 'add_post_type_columns' ), 9 );
		add_action( 'wp_enqueue_scripts', $this->container->callback( Cloud_Pages::class, 'action_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Cloud_Pages::class, 'action_enqueue_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Cloud_Pages::class, 'action_post_enqueue_admin_scripts' ) );
	}
}
