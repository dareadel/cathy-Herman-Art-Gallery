<?php
/**
 * The provider hooking Admin class methods to WordPress events.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */

namespace KadenceWP\CloudPages\Posts;

use function get_field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all functionality related to the AB Post.
 *
 * @since 0.1.1
 *
 * @package KadenceWP\CloudPages
 */
class Cloud_Pages {
	const SLUG = 'page_library';
	/**
	 * Allow us to enable merged defaults on blocks individually.
	 * Considered setting this as a property within each block, but it's easier to see an exhaustive list here.
	 * Eventually all blocks will be supported.
	 *
	 * @var array
	 */
	public static $is_cpt_block = [
		'kadence/navigation',
		'kadence/header',
		'kadence/advanced-form',
	];
	/**
	 * Add the columns to the post type.
	 */
	public function add_post_type_columns() {
		$slug = self::SLUG;
		add_filter(
			"manage_{$slug}_posts_columns",
			function ( array $columns ): array {
				return $this->filter_post_type_columns( $columns );
			}
		);
		add_action(
			"manage_{$slug}_posts_custom_column",
			function ( string $column_name, int $post_id ) {
				$this->render_post_type_column( $column_name, $post_id );
			},
			10,
			2
		);
	}
	/**
	 * Maybe flush permalinks.
	 */
	public function maybe_flush_permalinks() {
		if ( ! get_option( 'kadence_cloud_pages_has_flushed_permalinks' ) ) {
			flush_rewrite_rules();
			update_option( 'kadence_cloud_pages_has_flushed_permalinks', true );
		}
	}
	/**
	 * Filters the block area post type columns in the admin list table.
	 *
	 * @param array $columns Columns to display.
	 * @return array Filtered $columns.
	 */
	private function filter_post_type_columns( array $columns ): array {

		$add      = [
			'image' => esc_html__( 'Image', 'kadence-cloud-pages' ),
		];
		$settings = json_decode( get_option( 'kadence_cloud' ), true );
		if ( isset( $settings['enable_analytics'] ) && $settings['enable_analytics'] ) {
			$add['count'] = esc_html__( 'Import Count', 'kadence-cloud-pages' );
		}
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'taxonomy-page_categories' == $key ) {
				$new_columns = array_merge( $new_columns, $add );
			}
		}

		return $new_columns;
	}
	/**
	 * Enqueues a script that adds sticky for single products
	 */
	public function action_post_enqueue_admin_scripts( $hook ) {
		$screen = get_current_screen();
		if (
			( $screen && $screen->post_type === 'page_library' ) &&
			( $hook === 'post.php' || $hook === 'post-new.php' )
		) {
			// Enqueue CSS
			wp_enqueue_style('kadence-cloud-pages-admin', KADENCE_CLOUD_PAGES_URL . 'assets/admin-post-styles.css', [], KADENCE_CLOUD_PAGES_VERSION);
		}
	}
	/**
	 * Enqueues a script that adds sticky for single products
	 */
	public function action_enqueue_admin_scripts() {
		$current_page = get_current_screen();
		if ( defined( 'KADENCE_CLOUD_URL' ) && 'edit-' . self::SLUG === $current_page->id ) {
			// Enqueue the post styles.
			wp_enqueue_script( 'kadence-cloud-admin', KADENCE_CLOUD_URL . 'assets/cloud-post-admin.js', [ 'jquery' ], KADENCE_CLOUD_VERSION, true );
			wp_localize_script(
				'kadence-cloud-admin',
				'kb_admin_cloud_params',
				[
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'kadence-cloud-ajax-verification' ),
				]
			);
		}
	}
	/**
	 * Enqueues a script that adds sticky for single products
	 */
	public function action_enqueue_scripts() {

		if ( defined( 'KADENCE_CLOUD_URL' ) && is_singular( self::SLUG ) ) {
			// Enqueue the post styles.
			if ( isset( $_GET['screenshot'] ) ) {
				wp_enqueue_style( 'kadence-cloud-post-styles', KADENCE_CLOUD_URL . 'assets/cloud-post-styles.css', [], KADENCE_CLOUD_VERSION );
			}
		}
	}
	/**
	 * Renders column content for the block area post type list table.
	 *
	 * @param string $column_name Column name to render.
	 * @param int    $post_id     Post ID.
	 */
	public function render_post_type_column( string $column_name, int $post_id ) {
		if ( 'image' !== $column_name && 'count' !== $column_name ) {
			return;
		}
		if ( 'count' === $column_name ) {
			$block_count = get_post_meta( $post_id, '_kc_import_count', true );
			if ( isset( $block_count ) && is_numeric( $block_count ) ) {
				echo esc_html( $block_count );
			} else {
				echo '0';
				if ( apply_filters( 'kadence_cloud_order_by_popular', false ) ) {
					echo '<div style="height:1px"></div>';
					echo '<div class="generate_popular">';
					echo '<button class="kadence-cloud-generate_popular_order" style="
						background: transparent;
						border: 0;
						display: inline-block;
						text-decoration: underline;
						color: #2271b1;
						text-align: left;
						cursor: pointer;
						padding: 0;
						line-height: 26px;"
					>' . esc_html__( 'Generate for Popular Ordering' ) . '<span class="spinner"></span></button>';
					echo '</div>';
				}
			}
		}
		if ( 'image' === $column_name ) {
			$has_image = false;
			if ( has_post_thumbnail( $post_id ) ) {
				$has_image = true;
				echo '<div style="max-height:200px; display: flex;">';
				echo get_the_post_thumbnail( $post_id, 'medium-large', [ 'style' => 'object-fit: contain; max-width:200px; width:100%; border: 2px solid #eee; height:auto; max-height:200px;' ] );
				echo '</div>';
			} else {
				$image_meta = get_post_meta( $post_id, '_kc_preview_image', true );
				if ( ! empty( $image_meta['url'] ) ) {
					$has_image = true;
					echo '<div style="max-height:200px; display: flex;">';
					echo '<img src="' . esc_url( $image_meta['url'] ) . '" style="max-width:100%; width:200px; border: 2px solid #eee; height:auto" />';
					echo '</div>';
				}
			}
			echo '<div style="height:1px"></div>';
			$settings = json_decode( get_option( 'kadence_cloud' ), true );
			if ( isset( $settings ) && is_array( $settings ) && isset( $settings['enable_flash'] ) && $settings['enable_flash'] ) {
				echo '<button class="kadence-cloud-rebuild-thumbnail" data-post-id="' . esc_attr( $post_id ) . '" style="
				background: transparent;
				border: 0;
				display: inline-block;
				text-decoration: underline;
				color: #2271b1;
				cursor: pointer;
				line-height: 26px;"
			>' . ( $has_image ? esc_html__( 'Rebuild thumbnail' ) : esc_html__( 'Generate thumbnail' ) ) . '<span class="spinner"></span></button>';
			}
		}
	}
	/**
	 * Registers the Conversion post type.
	 */
	public function register_post_type() {
		$labels  = [
			'name'                  => __( 'Page Layouts', 'kadence-cloud-pages' ),
			'singular_name'         => __( 'Page Layout Item', 'kadence-cloud-pages' ),
			'menu_name'             => _x( 'Page Layouts', 'Admin Menu text', 'kadence-cloud-pages' ),
			'add_new'               => _x( 'Add New', 'Page Item', 'kadence-cloud-pages' ),
			'add_new_item'          => __( 'Add New Page Item', 'kadence-cloud-pages' ),
			'new_item'              => __( 'New Page Item', 'kadence-cloud-pages' ),
			'edit_item'             => __( 'Edit Page Item', 'kadence-cloud-pages' ),
			'view_item'             => __( 'View Page Item', 'kadence-cloud-pages' ),
			'all_items'             => __( 'All Page Items', 'kadence-cloud-pages' ),
			'search_items'          => __( 'Search Page Library', 'kadence-cloud-pages' ),
			'parent_item_colon'     => __( 'Parent Page Item:', 'kadence-cloud-pages' ),
			'not_found'             => __( 'No Page Items found.', 'kadence-cloud-pages' ),
			'not_found_in_trash'    => __( 'No Page Items found in Trash.', 'kadence-cloud-pages' ),
			'archives'              => __( 'Page Item archives', 'kadence-cloud-pages' ),
			'insert_into_item'      => __( 'Insert into Page Item', 'kadence-cloud-pages' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Page Item', 'kadence-cloud-pages' ),
			'filter_items_list'     => __( 'Filter Page Items list', 'kadence-cloud-pages' ),
			'items_list_navigation' => __( 'Page Items list navigation', 'kadence-cloud-pages' ),
			'items_list'            => __( 'Page Items list', 'kadence-cloud-pages' ),
		];
		$rewrite = apply_filters( 'page_library_post_type_url_rewrite', [ 'slug' => 'cloud-pages' ] );
		$args    = [
			'labels'              => $labels,
			'description'         => __( 'Build pages layouts from existing patterns.', 'kadence-cloud-pages' ),
			'public'              => false,
			'publicly_queryable'  => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=kadence_cloud',
			'menu_icon'           => 'dashicons-layout',
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'can_export'          => true,
			'show_in_rest'        => false,
			'rewrite'             => $rewrite,
			'rest_base'           => self::SLUG,
			'capabilities'        => [
				'edit_others_posts'      => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'create_posts'           => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'edit_private_posts'     => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
			],
			'map_meta_cap'        => true,
			'supports'            => [
				'title',
				'author',
				'custom-fields',
				'revisions',
				'thumbnail',
			],
		];
		register_post_type( self::SLUG, $args );

		$cat_rewrite = apply_filters(
			'kadence_page_library_category_url_rewrite',
			[
				'slug'       => 'cloud-page-categories',
				'with_front' => true,
			]
		);
		// Register Category Tax.
		$categories_labels   = [
			'name'                       => _x( 'Page Categories', 'taxonomy general name', 'kadence-cloud-pages' ),
			'singular_name'              => _x( 'Page Category', 'taxonomy singular name', 'kadence-cloud-pages' ),
			'search_items'               => __( 'Search Page Categories', 'kadence-cloud-pages' ),
			'popular_items'              => __( 'Popular Page Categories', 'kadence-cloud-pages' ),
			'all_items'                  => __( 'All Page Categories', 'kadence-cloud-pages' ),
			'edit_item'                  => __( 'Edit Page Category', 'kadence-cloud-pages' ),
			'update_item'                => __( 'Update Page Category', 'kadence-cloud-pages' ),
			'add_new_item'               => __( 'Add New Page Category', 'kadence-cloud-pages' ),
			'new_item_name'              => __( 'New Page Category', 'kadence-cloud-pages' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'kadence-cloud-pages' ),
			'add_or_remove_items'        => __( 'Add or remove page categories', 'kadence-cloud-pages' ),
			'choose_from_most_used'      => __( 'Choose from the most popular page categories', 'kadence-cloud-pages' ),
		];
		$cloud_category_args = apply_filters(
			'kadence_page_library_category_args',
			[
				'hierarchical'       => true, 
				'labels'             => $categories_labels,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
				'publicly_queryable' => false,
				'rewrite'            => $cat_rewrite,
			]
		);
		register_taxonomy(
			'page_categories',
			[ self::SLUG ],
			$cloud_category_args,
		);
		register_post_meta(
			self::SLUG,
			'_kc_import_count',
			[
				'show_in_rest' => false,
				'single'       => true,
				'type'         => 'number',
			]
		);
		register_post_meta(
			self::SLUG,
			'_kc_preview_image',
			[
				'show_in_rest' => false,
				'single'       => true,
				'type'         => 'array',
				'default'      => [],
			]
		);
	}
	/**
	 * Add the "Chapters" submenu under "Books"
	 */
	public function add_pages_submenu() {
		add_submenu_page(
			'edit.php?post_type=kadence_cloud',            // Parent menu
			__( 'Page Layouts', 'textdomain' ),        // Page title
			__( 'Page Layouts', 'textdomain' ),        // Menu title
			'manage_options',                      // Capability
			'edit.php?post_type=' . self::SLUG,         // Menu slug
			'',                                    // Callback (not needed for CPT listing)
			15                                     // Position (example)
		);
	}
	/**
	 * Filter the cloud library info args.
	 *
	 * @param array $args The args.
	 * @return array The filtered args.
	 */
	public function filter_cloud_library_info_args( $args ) {
		$settings = json_decode( get_option( 'kadence_cloud' ), true );
		if ( isset( $settings['enable_pages'] ) && $settings['enable_pages'] ) {
			$args['pages'] = 'enabled';
		}
		return $args;
	}
	/**
	 * Check that user can edit these.
	 */
	public function add_page_category_submenu() {
		add_submenu_page(
			'edit.php?post_type=kadence_cloud', 
			__( 'Page Categories', 'kadence-cloud-pages' ),  // Page title
			__( 'Page Categories', 'kadence-cloud-pages' ),  // Menu title
			'manage_options',                          // Capability
			'edit-tags.php?taxonomy=page_categories&post_type=' . self::SLUG, // Menu slug
			'',                                        // Callback (not needed for CPT listing)
			9
		);
	}

	/**
	 * Check that user can edit these.
	 */
	public function register_cmb2_pattern_metabox() {
		if ( apply_filters( 'kadence_cloud_page_use_acf', false ) ) {
			return;
		}
		/**
		 * Create a new metabox
		 * Adjust 'id', 'title', and 'object_types' as needed
		 */
		$cmb = new_cmb2_box(
			[
				'id'           => 'pattern_box',
				'title'        => __( 'Page Pattern Layout', 'kadence-cloud-pages' ),
				'object_types' => [ self::SLUG ], // or any post type(s) you want
			] 
		);
	
		/**
		 * Add a group field: repeatable set of sub-fields
		 */
		$group_field_id = $cmb->add_field(
			[
				'id'      => 'sections',
				'type'    => 'group',
				'options' => [
					'group_title'   => __( 'Pattern {#}', 'kadence-cloud-pages' ), 
					'add_button'    => __( 'Add Another Pattern', 'kadence-cloud-pages' ),
					'remove_button' => __( 'Remove Pattern', 'kadence-cloud-pages' ),
					'sortable'      => true, // drag/drop to reorder
				],
			] 
		);
	
		/**
		 * Add a sub-field to the group:
		 * A searchable select (via Select2) that lists all kadence_cloud posts
		 */
		$cmb->add_group_field(
			$group_field_id,
			[
				'name'            => __( 'Selected Pattern', 'kadence-cloud-pages' ),
				'id'              => 'selected_pattern',
				'type'            => 'post_search_text', // This field type
			// post type also as array
				'post_type'       => 'kadence_cloud',
				// Default is 'checkbox', used in the modal view to select the post type
				'select_type'     => 'radio',
				// Will replace any selection with selection from modal. Default is 'add'
				'select_behavior' => 'replace',
				'text'            => [
					'select_text' => __( 'Select Pattern', 'kadence-cloud-pages' ),
					'find_text'   => __( 'Find Patterns', 'kadence-cloud-pages' ),
				],
				'select_text'     => __( 'Select Pattern', 'kadence-cloud-pages' ),
				'find_text'       => __( 'Find Patterns', 'kadence-cloud-pages' ),
			] 
		);
	}


	/**
	 * Highlight the Books menu as "active" when viewing Chapter Categories
	 */
	public function set_page_categories_parent_menu( $parent_file ) {
		global $current_screen;
		// If we're on the edit-tags page for the "chapter_category" taxonomy
		if ( 'edit-tags' === $current_screen->base && 'page_categories' === $current_screen->taxonomy ) {
			// Highlight the Books CPT's top-level menu
			$parent_file = 'edit.php?post_type=kadence_cloud';
		}
		return $parent_file;
	}

	/**
	 * Highlight the "Chapter Categories" submenu item itself
	 */
	public function set_page_categories_submenu_file( $submenu_file ) {
		global $current_screen;
		if ( 'edit-tags' === $current_screen->base && 'page_categories' === $current_screen->taxonomy ) {
			// Make sure the submenu item for "Chapter Categories" is also selected
			$submenu_file = 'edit-tags.php?taxonomy=page_categories&post_type=page_library';
		}

		return $submenu_file;
	}
	/**
	 * Check that user can edit these.
	 */
	public function meta_auth_callback() {
		return current_user_can( 'edit_others_pages' );
	}
	/**
	 * Sanitize the block usage locations meta field.
	 *
	 * @param mixed $meta_value The meta value to sanitize.
	 * @return array The sanitized meta value.
	 */
	public function sanitize_block_usage_locations( $meta_value ) {
		if ( ! is_array( $meta_value ) ) {
			return [];
		}

		// Ensure all values in the array are integers.
		return array_map( 'intval', $meta_value );
	}
	/**
	 * Filters the capabilities of a user to conditionally grant them capabilities for managing ab tests.
	 *
	 * Any user who can 'edit_others_pages' will have access to manage ab tests.
	 *
	 * @param array $allcaps A user's capabilities.
	 * @return array Filtered $allcaps.
	 */
	public function filter_post_type_user_caps( $allcaps ) {
		if ( isset( $allcaps['edit_others_pages'] ) ) {
			$allcaps['edit_page_library']             = $allcaps['edit_others_pages'];
			$allcaps['edit_others_page_library']      = $allcaps['edit_others_pages'];
			$allcaps['edit_published_page_library']   = $allcaps['edit_others_pages'];
			$allcaps['edit_private_page_library']     = $allcaps['edit_others_pages'];
			$allcaps['delete_page_library']           = $allcaps['edit_others_pages'];
			$allcaps['delete_others_page_library']    = $allcaps['edit_others_pages'];
			$allcaps['delete_published_page_library'] = $allcaps['edit_others_pages'];
			$allcaps['delete_private_page_library']   = $allcaps['edit_others_pages'];
			$allcaps['publish_page_library']          = $allcaps['edit_others_pages'];
			$allcaps['read_private_page_library']     = $allcaps['edit_others_pages'];
		}
		return $allcaps;
	}
	/**
	 * Renders the single template on the front end.
	 *
	 * @param array $layout the layout array.
	 */
	public function kadence_editor_single_layout( $layout ) {
		global $post;
		if ( is_singular( self::SLUG ) || ( is_admin() && is_object( $post ) && self::SLUG === $post->post_type ) ) {
			$layout = wp_parse_args(
				[
					'layout'           => 'fullwidth',
					'boxed'            => 'unboxed',
					'feature'          => 'hide',
					'feature_position' => 'above',
					'comments'         => 'hide',
					'navigation'       => 'hide',
					'title'            => 'hide',
					'transparent'      => 'disable',
					'sidebar'          => 'disable',
					'vpadding'         => 'hide',
					'footer'           => 'disable',
					'header'           => 'disable',
					'content'          => 'enable',
				],
				$layout
			);
		}

		return $layout;
	}
	/**
	 * Override the single pattern page output.
	 */
	public function kadence_custom_content_page_override() {
		if ( is_singular( 'page_library' ) ) {
			remove_action( 'kadence_single', 'Kadence\single_markup' );
			add_action( 'kadence_single', [ $this, 'custom_output_page_content' ] );
		}
	}
	/**
	 * Custom output for the page library content.
	 */
	public function custom_output_page_content() {
		$page_id = get_the_ID();
		if ( apply_filters( 'kadence_cloud_page_use_acf', false ) ) {
			if ( function_exists( 'get_field' ) ) {
				$rows = get_field( 'pattern' );
			} else {
				$rows = false;
			}
		} else {
			$rows = get_post_meta( $page_id, 'sections', true );
			$rows = maybe_unserialize( $rows );
		}
		if ( ! $rows ) {
			return;
		}
		echo '<div id="primary" class="content-area">
		<div class="content-container site-container">
		<main id="main" class="site-main" role="main">
		<div class="content-wrap">
		<article class="entry content-bg single-entry">
		<div class="entry-content-wrap">
		<div class="entry-content single-content">';
		if ( $rows && is_array( $rows ) ) {
			foreach ( $rows as $row_item ) {
				$pattern_id = $row_item['selected_pattern'];
				if ( ! empty( $pattern_id ) ) {
					$pattern_object = get_post( $pattern_id );
					
					if ( $pattern_object ) {
						$style   = ( isset( $row_item['color_style'] ) ) ? $row_item['color_style'] : 'light';
						$content = $pattern_object->post_content;
						if ( $style === 'dark' ) {
							$content = self::row_patterns_custom_switch_color( $content, 'dark' );
						} elseif ( $style === 'highlight' ) {
							$content = self::row_patterns_custom_switch_color( $content, 'highlight' );
						}
						echo apply_filters( 'kadence_patterns_custom_the_content', $content );
					}
				}
			}
		}
		echo '</div></div></div></div></div></div></div>';
	}
	/**
	 * Setup the content filter for the custom content.
	 */
	public function custom_content_filter() {
		global $wp_embed;
		add_filter( 'kadence_patterns_custom_the_content', [ $wp_embed, 'run_shortcode' ], 8 );
		add_filter( 'kadence_patterns_custom_the_content', [ $wp_embed, 'autoembed' ], 8 );
		add_filter( 'kadence_patterns_custom_the_content', 'do_blocks' );
		add_filter( 'kadence_patterns_custom_the_content', 'wptexturize' );
		add_filter( 'kadence_patterns_custom_the_content', 'convert_chars' );
		// Don't use this unless classic editor add_filter( 'ktp_the_content', 'wpautop' );
		add_filter( 'kadence_patterns_custom_the_content', 'shortcode_unautop' );
		add_filter( 'kadence_patterns_custom_the_content', 'wp_filter_content_tags' );
		add_filter( 'kadence_patterns_custom_the_content', 'do_shortcode', 11 );
		add_filter( 'kadence_patterns_custom_the_content', 'convert_smilies', 20 );
	}
	/**
	 * Setup the post select API endpoint.
	 *
	 * @return void
	 */
	public function register_api_endpoints() {
		$posts_controller = new Cloud_Pages_REST_Controller();
		$posts_controller->register_routes();
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param string $string The string to search.
	 * @param string $start The start of the string.
	 * @param string $end The end of the string.
	 * @param string $verify The string to verify.
	 * @return string The string in between the start and end.
	 */
	public static function get_string_inbetween( $string, $start, $end, $verify ) {
		if ( strpos( $string, $verify ) == 0 ) {
			return '';
		}
		$ini = strpos( $string, $start );
		if ( $ini == 0 ) {
			return '';
		}
		$ini += strlen( $start );
		$len  = strpos( $string, $end, $ini ) - $ini;
		return substr( $string, $ini, $len );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param string $string The string to search.
	 * @param string $start The start of the string.
	 * @param string $end The end of the string.
	 * @param string $verify The string to verify.
	 * @param int    $from The position to start searching from.
	 * @return string The string in between the start and end.
	 */
	public static function get_string_inbetween_when( $string, $start, $end, $verify, $from ) {
		$ini = strpos( $string, $start, $from );
		if ( $ini == 0 ) {
			return '';
		}
		$ini       += strlen( $start );
		$len        = strpos( $string, $end, $ini ) - $ini;
		$sub_string = substr( $string, $ini, $len );
		if ( strpos( $sub_string, $verify ) == 0 ) {
			return self::get_string_inbetween_when( $string, $start, $end, $verify, $ini );
		}
		return $sub_string;
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param string $content The content to search.
	 * @param string $style The style to search for.
	 * @return string The content with the style replaced.
	 */
	public static function row_patterns_custom_switch_color( $content, $style ) {
		$content = str_replace( 'Logo-ploaceholder.png', 'Logo-ploaceholder-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-1.png', 'Logo-ploaceholder-1-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-2.png', 'Logo-ploaceholder-2-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-3.png', 'Logo-ploaceholder-3-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-4.png', 'Logo-ploaceholder-4-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-5.png', 'Logo-ploaceholder-5-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-6.png', 'Logo-ploaceholder-6-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-7.png', 'Logo-ploaceholder-7-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-8.png', 'Logo-ploaceholder-8-white.png', $content );

		$content = str_replace( 'logo-placeholder.png', 'logo-placeholder-white.png', $content );
		$content = str_replace( 'logo-placeholder-1.png', 'logo-placeholder-1-white.png', $content );
		$content = str_replace( 'logo-placeholder-2.png', 'logo-placeholder-2-white.png', $content );
		$content = str_replace( 'logo-placeholder-3.png', 'logo-placeholder-3-white.png', $content );
		$content = str_replace( 'logo-placeholder-4.png', 'logo-placeholder-4-white.png', $content );
		$content = str_replace( 'logo-placeholder-5.png', 'logo-placeholder-5-white.png', $content );
		$content = str_replace( 'logo-placeholder-6.png', 'logo-placeholder-6-white.png', $content );
		$content = str_replace( 'logo-placeholder-7.png', 'logo-placeholder-7-white.png', $content );
		$content = str_replace( 'logo-placeholder-8.png', 'logo-placeholder-8-white.png', $content );
		$content = str_replace( 'logo-placeholder-9.png', 'logo-placeholder-9-white.png', $content );
		$content = str_replace( 'logo-placeholder-10.png', 'logo-placeholder-10-white.png', $content );
		
		if ( $style === 'highlight' ) {
			$form_content = self::get_string_inbetween( $content, '"submit":[{', ']}', 'wp:kadence/form' );
			if ( $form_content ) {
				$form_content_org = $form_content;
				$form_content     = str_replace( '"color":""', '"color":"placeholder-kb-pal9"', $form_content );
				$form_content     = str_replace( '"background":""', '"background":"placeholder-kb-pal3"', $form_content );
				$form_content     = str_replace( '"colorHover":""', '"colorHover":"placeholder-kb-pal9"', $form_content );
				$form_content     = str_replace( '"backgroundHover":""', '"backgroundHover":"placeholder-kb-pal4"', $form_content );
				$content          = str_replace( $form_content_org, $form_content, $content );
			}
			$content = str_replace( '"inheritStyles":"inherit"', '"color":"placeholder-kb-pal9","background":"placeholder-kb-pal3","colorHover":"placeholder-kb-pal9","backgroundHover":"placeholder-kb-pal4","inheritStyles":"inherit"', $content );

			$content = str_replace( 'has-theme-palette-1', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-2', 'placeholder-kb-class8', $content );
			$content = str_replace( 'has-theme-palette-3', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-4', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-5', 'placeholder-kb-class8', $content );
			$content = str_replace( 'has-theme-palette-6', 'placeholder-kb-class7', $content );
			$content = str_replace( 'has-theme-palette-7', 'placeholder-kb-class2', $content );
			$content = str_replace( 'has-theme-palette-8', 'placeholder-kb-class2', $content );
			$content = str_replace( 'has-theme-palette-9', 'placeholder-kb-class1', $content );

			$content = str_replace( 'theme-palette1', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette2', 'placeholder-class-pal8', $content );
			$content = str_replace( 'theme-palette3', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette4', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette5', 'placeholder-class-pal8', $content );
			$content = str_replace( 'theme-palette6', 'placeholder-class-pal7', $content );
			$content = str_replace( 'theme-palette7', 'placeholder-class-pal2', $content );
			$content = str_replace( 'theme-palette8', 'placeholder-class-pal2', $content );
			$content = str_replace( 'theme-palette9', 'placeholder-class-pal1', $content );

			$content = str_replace( 'palette1', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette2', 'placeholder-kb-pal8', $content );
			$content = str_replace( 'palette3', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette4', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette5', 'placeholder-kb-pal8', $content );
			$content = str_replace( 'palette6', 'placeholder-kb-pal7', $content );
			$content = str_replace( 'palette7', 'placeholder-kb-pal2', $content );
			$content = str_replace( 'palette8', 'placeholder-kb-pal2', $content );
			$content = str_replace( 'palette9', 'placeholder-kb-pal1', $content );

		} else {
			$white_text_content = self::get_string_inbetween_when( $content, '<!-- wp:kadence/column', 'kt-inside-inner-col', 'kb-pattern-light-color', 0 );
			if ( $white_text_content ) {
				$white_text_content_org = $white_text_content;
				$white_text_content     = str_replace( '"textColor":"palette9"', '"textColor":"placeholder-kb-pal9"', $white_text_content );
				$white_text_content     = str_replace( '"linkColor":"palette9"', '"linkColor":"placeholder-kb-pal9"', $white_text_content );
				$white_text_content     = str_replace( '"linkHoverColor":"palette9"', '"linkHoverColor":"placeholder-kb-pal9"', $white_text_content );
				$content                = str_replace( $white_text_content_org, $white_text_content, $content );
			}
			$content = str_replace( 'has-theme-palette-3', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-4', 'placeholder-kb-class8', $content );
			$content = str_replace( 'has-theme-palette-5', 'placeholder-kb-class7', $content );
			$content = str_replace( 'has-theme-palette-6', 'placeholder-kb-class7', $content );
			$content = str_replace( 'has-theme-palette-7', 'placeholder-kb-class3', $content );
			$content = str_replace( 'has-theme-palette-8', 'placeholder-kb-class3', $content );
			$content = str_replace( 'has-theme-palette-9', 'placeholder-kb-class4', $content );

			$content = str_replace( 'theme-palette3', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette4', 'placeholder-class-pal8', $content );
			$content = str_replace( 'theme-palette5', 'placeholder-class-pal7', $content );
			$content = str_replace( 'theme-palette6', 'placeholder-class-pal7', $content );
			$content = str_replace( 'theme-palette7', 'placeholder-class-pal3', $content );
			$content = str_replace( 'theme-palette8', 'placeholder-class-pal3', $content );
			$content = str_replace( 'theme-palette9', 'placeholder-class-pal4', $content );

			$content = str_replace( 'palette3', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette4', 'placeholder-kb-pal8', $content );
			$content = str_replace( 'palette5', 'placeholder-kb-pal7', $content );
			$content = str_replace( 'palette6', 'placeholder-kb-pal7', $content );
			$content = str_replace( 'palette7', 'placeholder-kb-pal3', $content );
			$content = str_replace( 'palette8', 'placeholder-kb-pal3', $content );
			$content = str_replace( 'palette9', 'placeholder-kb-pal4', $content );
		}
		$content = str_replace( 'placeholder-kb-class1', 'has-theme-palette-1', $content );
		$content = str_replace( 'placeholder-kb-class2', 'has-theme-palette-2', $content );
		$content = str_replace( 'placeholder-kb-class3', 'has-theme-palette-3', $content );
		$content = str_replace( 'placeholder-kb-class4', 'has-theme-palette-4', $content );
		$content = str_replace( 'placeholder-kb-class5', 'has-theme-palette-5', $content );
		$content = str_replace( 'placeholder-kb-class6', 'has-theme-palette-6', $content );
		$content = str_replace( 'placeholder-kb-class7', 'has-theme-palette-7', $content );
		$content = str_replace( 'placeholder-kb-class8', 'has-theme-palette-8', $content );
		$content = str_replace( 'placeholder-kb-class9', 'has-theme-palette-9', $content );

		$content = str_replace( 'placeholder-class-pal1', 'theme-palette1', $content );
		$content = str_replace( 'placeholder-class-pal2', 'theme-palette2', $content );
		$content = str_replace( 'placeholder-class-pal3', 'theme-palette3', $content );
		$content = str_replace( 'placeholder-class-pal4', 'theme-palette4', $content );
		$content = str_replace( 'placeholder-class-pal5', 'theme-palette5', $content );
		$content = str_replace( 'placeholder-class-pal6', 'theme-palette6', $content );
		$content = str_replace( 'placeholder-class-pal7', 'theme-palette7', $content );
		$content = str_replace( 'placeholder-class-pal8', 'theme-palette8', $content );
		$content = str_replace( 'placeholder-class-pal9', 'theme-palette9', $content );

		$content = str_replace( 'placeholder-kb-pal1', 'palette1', $content );
		$content = str_replace( 'placeholder-kb-pal2', 'palette2', $content );
		$content = str_replace( 'placeholder-kb-pal3', 'palette3', $content );
		$content = str_replace( 'placeholder-kb-pal4', 'palette4', $content );
		$content = str_replace( 'placeholder-kb-pal5', 'palette5', $content );
		$content = str_replace( 'placeholder-kb-pal6', 'palette6', $content );
		$content = str_replace( 'placeholder-kb-pal7', 'palette7', $content );
		$content = str_replace( 'placeholder-kb-pal8', 'palette8', $content );
		$content = str_replace( 'placeholder-kb-pal9', 'palette9', $content );
		$content = self::render_cpt_blocks( $content, $style );
		return $content;
	}
	/**
	 * Get the CPT content.
	 *
	 * @param array $block The block to get the content from.
	 * @param array $cpt_blocks The array of CPT blocks.
	 * @return array The array of CPT blocks.
	 */
	public static function get_cpt_content( $block, $cpt_blocks ) {
		switch ( $block['blockName'] ) {
			case 'kadence/advanced-form':
				if ( ! empty( $block['attrs']['id'] ) ) {
					$content = self::get_post_id_for_class( $block['attrs']['id'] );
					if ( ! empty( $content ) ) {
						$cpt_blocks[] = $content;
					}
				}
				break;
			case 'kadence/header':
				if ( ! empty( $block['attrs']['id'] ) ) {
					$content = self::get_post_id_for_class( $block['attrs']['id'] );
					if ( ! empty( $content ) ) {
						$cpt_blocks[] = $content;
					}
				}
				break;
			case 'kadence/navigation':
				if ( ! empty( $block['attrs']['id'] ) ) {
					$content = self::get_post_id_for_class( $block['attrs']['id'] );
					if ( ! empty( $content ) ) {
						$cpt_blocks[] = $content;
					}
				}
				break;
		}
		return $cpt_blocks;
	}
	/**
	 * Prepare post for export
	 */
	private static function get_post_id_for_class( $post_id ) {
		if ( empty( $post_id ) ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		
		return [
			'ID'        => $post->ID,
			'post_type' => $post->post_type,
		];
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public static function blocks_cycle_through( $blocks, $cpt_blocks ) {
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], self::$is_cpt_block, true ) ) {
				$cpt_blocks = self::get_cpt_content( $block, $cpt_blocks );
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$cpt_blocks = self::blocks_cycle_through( $block['innerBlocks'], $cpt_blocks );
			}
		}
		return $cpt_blocks;
	}
	/**
	 * Render the CPT blocks.
	 *
	 * @param string $content The content to render.
	 * @return array The array of CPT blocks.
	 */
	public static function get_cpt_blocks_in_content( $content ) {
		$blocks     = parse_blocks( $content );
		$cpt_blocks = [];
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], self::$is_cpt_block, true ) ) {
				$cpt_blocks = self::get_cpt_content( $block, $cpt_blocks );
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$cpt_blocks = self::blocks_cycle_through( $block['innerBlocks'], $cpt_blocks );
			}
		}
		return $cpt_blocks;
	}
	/**
	 * Render the CPT blocks.
	 *
	 * @param string $content The content to render.
	 * @return string The rendered content.
	 */
	public static function render_cpt_blocks( $content, $style ) {
		$dark_outer_css      = 'color-scheme: dark;
		--global-temp-palette1: var(--global-palette1);
		--global-temp-palette2: var(--global-palette2);
		--global-temp-palette3: var(--global-palette3);
		--global-temp-palette4: var(--global-palette4);
		--global-temp-palette5: var(--global-palette5);
		--global-temp-palette6: var(--global-palette6);
		--global-temp-palette7: var(--global-palette7);
		--global-temp-palette8: var(--global-palette8);
		--global-temp-palette9: var(--global-palette9);';
		$dark_inner_css      = '--global-palette1: var(--global-temp-palette1);
		--global-palette2: var(--global-temp-palette2);
		--global-palette3: var(--global-temp-palette9);
		--global-palette4: var(--global-temp-palette8);
		--global-palette5: var(--global-temp-palette7);
		--global-palette6: var(--global-temp-palette7);
		--global-palette7: var(--global-temp-palette3);
		--global-palette8: var(--global-temp-palette4);
		--global-palette9: var(--global-temp-palette5);
		--global-palette-highlight: var(--global-palette1);
		--global-palette-highlight-alt: var(--global-palette2);
		--global-palette-highlight-alt2: var(--global-palette9);
		--kb-form-text-color: var(--global-temp-palette9);
		--kb-form-background-color:var(--global-temp-palette4);
		--kb-form-border-color:color-mix(in srgb, var(--global-temp-palette6) 20%, transparent);';
		$highlight_outer_css = '--global-temp-palette1: var(--global-palette1);
		--global-temp-palette2: var(--global-palette2);
		--global-temp-palette3: var(--global-palette3);
		--global-temp-palette4: var(--global-palette4);
		--global-temp-palette5: var(--global-palette5);
		--global-temp-palette6: var(--global-palette6);
		--global-temp-palette7: var(--global-palette7);
		--global-temp-palette8: var(--global-palette8);
		--global-temp-palette9: var(--global-palette9);';
		$highlight_inner_css = '--global-palette1: var(--global-temp-palette9);
		--global-palette2: var(--global-temp-palette8);
		--global-palette3: var(--global-temp-palette9);
		--global-palette4: var(--global-temp-palette9);
		--global-palette5: var(--global-temp-palette8);
		--global-palette6: var(--global-temp-palette7);
		--global-palette7: var(--global-temp-palette2);
		--global-palette8: var(--global-temp-palette2);
		--global-palette9: var(--global-temp-palette1);
		--global-palette-highlight: var(--global-palette1);
		--global-palette-highlight-alt: var(--global-palette2);
		--global-palette-highlight-alt2: var(--global-palette9);
		--global-palette-btn-bg: var(--global-temp-palette3);
		--global-palette-btn: var(--global-temp-palette9);
		--global-palette-btn-hover: var(--global-temp-palette9);
		--global-palette-btn-bg-hover: var(--global-temp-palette4);
		--kb-form-text-color: var(--global-temp-palette3);
		--kb-form-background-color:var(--global-temp-palette8);
		--kb-form-border-color:var(--global-temp-palette7);';
		$blocks              = self::get_cpt_blocks_in_content( $content );
		if ( ! empty( $blocks ) ) {
			foreach ( $blocks as $block ) {
				switch ( $block['post_type'] ) {
					case 'kadence_form':
						$form_content = self::get_string_inbetween( $content, '<!-- wp:kadence/advanced-form', '/-->', 0 );
						if ( $form_content ) {
							if ( $style === 'highlight' ) {
								$inner_css = $highlight_inner_css;
								$outer_css = $highlight_outer_css;
							} else {
								$inner_css = $dark_inner_css;
								$outer_css = $dark_outer_css;
							}
							$form_string = '<!-- wp:kadence/advanced-form' . $form_content . '/-->';
							$content     = str_replace( $form_string, $form_string . '<style>.wp-block-kadence-advanced-form' . $block['ID'] . '-cpt-id{' . $outer_css . '}.wp-block-kadence-advanced-form' . $block['ID'] . '-cpt-id .kb-advanced-form{' . $inner_css . '}</style>', $content );
						}
						break;
				}
			}
		}
		return $content;
	}
}
