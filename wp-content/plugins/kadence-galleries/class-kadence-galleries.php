<?php
/**
 * The main plugin file.
 *
 * @package Kadence Galleries
 */

namespace KadenceWP\KadenceGalleries;
use WP_Query;

use function is_admin;
use function add_action;
use function add_shortcode;
use function add_filter;
use function paginate_links;
use function get_query_var;
use function get_pagenum_link;
use function is_singular;
use function get_the_title;
use function kt_gal_get_full_intrinsic_image_output;
use function get_post_meta;
use function wp_get_attachment_image;
use function has_post_thumbnail;
use function esc_url;
use function get_permalink;
use function esc_html;
use function get_the_term_list;
use function is_string;
use function is_tax;
use function get_post_types;
use function in_array;
use function cmb2_utils;
use function new_cmb2_box;
use function wp_reset_postdata;
use function wp_parse_args;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kadence Galleries
 */
class Plugin {
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'kt_gallery_post_init' ), 1 );
		if ( is_admin() ) {
			add_action( 'do_meta_boxes', array( $this, 'kt_galleries_remove_revolution_slider_meta_boxes' ), 10 );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'kt_galleries_admin_enqueue_scripts' ) );
	}

	public function init() {
		global $kt_galleries;
		add_shortcode( 'kadence_gallery', array( $this, 'kt_shortcode_gallery_handler' ) );
		add_shortcode( 'kadence_album', array( $this, 'kt_shortcode_gallery_album_handler' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'kt_galleries_enqueue_scripts' ) );
		add_filter( 'cmb2_render_kt_galleries', array( $this, 'kt_gallery_field' ), 10, 5 );
		add_filter( 'cmb2_sanitize_kt_galleries', array( $this, 'kt_gallery_field_sanitize' ), 10, 2 );
		add_filter( 'cmb2_render_kt_gallery_useage', array( $this, 'kt_gallery_shortcode_field' ), 10, 5 );
		add_filter( 'cmb2_admin_init', array( $this, 'kt_galleries_metaboxes' ) );
		add_filter( 'manage_kt_gallery_posts_columns', array( $this, 'kt_add_kt_gallery_columns' ) );
		add_action( 'manage_kt_gallery_posts_custom_column', array( $this, 'kt_custom_kt_gallery_column' ), 10, 2 );
		add_action( 'cmb2_render_kt_gal_text_number', array( $this, 'kt_gal_small_render_text_number' ), 10, 5 );
		add_filter( 'single_template', array( $this, 'kt_gal_custom_template' ), 20 );
		add_filter( 'archive_template', array( $this, 'kt_gal_custom_album_template' ), 20 );
		add_filter( 'kadence_display_sidebar', array( $this, 'kt_gallery_sidebar' ) );
		add_action( 'kadence_gallery_loop', array( $this, 'kt_gal_loop_template' ), 20 );
		add_action( 'kadence_gallery_post_header', array( $this, 'kt_gal_title' ), 20 );
		add_filter( 'kadence_pagetitle_behindheader', array( $this, 'kt_gallery_header' ), 20 );
		add_action( 'kadence_gallery_album_content_after', array( $this, 'kt_gallery_wp_pagenav' ) );
		add_action( 'pre_get_posts', array( $this, 'posts_on_gallery' ) );
		add_action( 'pre_get_posts', array( $this, 'remove_gallery_type_from_search_results' ) );
	}
	/**
	 * Remove Gallery Type from search
	 *
	 * @param object $query the pre query.
	 */
	public function remove_gallery_type_from_search_results( $query ) {
		/* check is front end main loop content */
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		/* check is search result query */
		if ( $query->is_search() ) {

			$post_type_to_remove = 'kt_gallery';
			/* get all searchable post types */
			$searchable_post_types = get_post_types( array( 'exclude_from_search' => false ) );
			$searchable_types = $query->get( 'post_type' );

			/* make sure you got the proper results, and that your post type is in the results */
			if ( empty( $searchable_types ) && is_array( $searchable_post_types ) && in_array( $post_type_to_remove, $searchable_post_types ) ) {
				/* remove the post type from the array */
				unset( $searchable_post_types[ $post_type_to_remove ] );
				/* set the query to the remaining searchable post types */
				$query->set( 'post_type', $searchable_post_types );
			}
		}
	}
	public function kt_galleries_admin_enqueue_scripts() {
		global $pagenow;
		if ( is_admin() && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' || 'edit-tags.php' ) ) {
			wp_enqueue_style( 'kadence_admin_galleries_css', KTG_URL . 'assets/css/kadence-admin-galleries.css', false, KTG_VERSION );
			wp_register_script( 'kadence_admin_galleries', KTG_URL . 'assets/js/kt-admin-galleries.js', array( 'jquery' ), KTG_VERSION, true );
			wp_enqueue_script( 'kadence_admin_galleries' );
		}
	}
	public function kt_gallery_sidebar( $sidebar ) {
		if ( is_singular( 'kt_gallery' ) || is_tax( 'kt_album' ) ) {
			return false;
		}
		return $sidebar;
	}
	public function kt_gal_title() {
		echo '<h1 class="kt-gal-title">' . get_the_title() . '</h1>';
	}
	public function kt_gallery_header( $head ) {
		if ( is_singular( 'kt_gallery' ) || is_tax( 'kt_album' ) ) {
			return false;
		}
		return $head;
	}
	public function kt_gal_custom_template( $single ) {
		global $wp_query, $post;

		/* Checks for single template by post type */
		if ( $post->post_type == 'kt_gallery' ) {
			if ( file_exists( KTG_PATH . 'templates/single-kt_gallery.php' ) ) {
				return KTG_PATH . 'templates/single-kt_gallery.php';
			}
		}
		return $single;
	}
	function posts_on_gallery( $query ) {
		if ( is_tax( 'kt_album' ) && $query->is_main_query() ) {
			global $kt_galleries;
			$query->set( 'posts_per_page', ( isset( $kt_galleries['album_post_per_page'] ) && ! empty( $kt_galleries['album_post_per_page'] ) ? $kt_galleries['album_post_per_page'] : '10' ) );
		}
	}
	/**
	 * Page Navigation
	 */
	function kt_gallery_wp_pagenav() {
		global $wp_query;
		if ( $wp_query->max_num_pages > 1 ) :
			$pages = '';
			$big = 999999999; // need an unlikely integer
			$max = $wp_query->max_num_pages;
			if ( ! $current = get_query_var( 'paged' ) ) {
				$current = 1;
			}
			$args['base'] = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
			$args['total'] = $max;
			$args['current'] = $current;
			$args['add_args'] = false;

			$total = 1;
			$args['mid_size'] = 3;
			$args['end_size'] = 1;
			$args['prev_text'] = '<svg style="display:inline-block;vertical-align:middle" class="k-galleries-pagination-left-svg" viewBox="0 0 320 512" height="14" width="8" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg>';
			$args['next_text'] = '<svg style="display:inline-block;vertical-align:middle" class="k-galleries-pagination-right-svg" viewBox="0 0 320 512" height="14" width="8" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>';

			if ( $max > 1 ) {
				echo '<div class="kadence-galleries-pagenav wp-pagenavi">';
			}
			if ( $total == 1 && $max > 1 ) {
				echo paginate_links( $args );
			}
			if ( $max > 1 ) {
				echo '</div>';
			}
		endif;
	}
	public function kt_gal_loop_template() {
		global $post;
		if ( has_post_thumbnail( $post->ID ) ) {
			$image = wp_get_attachment_image( get_post_thumbnail_id( $post->ID ), 'large', array( 'class' => 'gallery-featured-image' ) );
		} else {
			$images = get_post_meta( $post->ID, '_kt_gal_images', true );
			if ( ! empty( $images ) ) {
				$attachments = array_filter( explode( ',', $images ) );
				$image = wp_get_attachment_image( $attachments['0'], 'large', false, array( 'class' => 'gallery-featured-image' ) );
			} else {
				$image = '';
			}
		}
		echo '<div class="kt-gallery-item kt-has-caption">';
			echo '<a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="kt-no-lightbox kt-gal-fade-in">';
				echo wp_kses_post( $image );
				echo '<div class="kt-gallery-item-overlay"><div class="kt-overlay-border"></div><div class="kt-gallery-align-vertical"><i class="kt-gallery-item-icon"></i></div></div>';
				echo '<div class="kt-gallery-caption-container">';
					echo '<div class="kt-gallery-caption">';
						echo '<div class="kt-gallery-caption-title">';
						echo '<h5>' . esc_html( get_the_title() ) . '</h5>';
						echo '</div>';
					echo '</div>';
				echo '</div>';
			echo '</a>';
		echo '</div>';
	}
	public function kt_gal_custom_album_template( $archive_template ) {
		if ( is_tax( 'kt_album' ) ) {
			if ( file_exists( KTG_PATH . 'templates/archive-kt_album.php' ) ) {
				return KTG_PATH . 'templates/archive-kt_album.php';
			}
		}
		return $archive_template;
	}
	public function kt_gallery_post_init() {
		$gallerylabels = array(
			'name' => __( 'Galleries', 'kadence-galleries' ),
			'singular_name' => __( 'Gallery', 'kadence-galleries' ),
			'add_new' => __( 'Add New Gallery', 'kadence-galleries' ),
			'add_new_item' => __( 'Add New Gallery', 'kadence-galleries' ),
			'edit_item' => __( 'Edit Gallery', 'kadence-galleries' ),
			'new_item' => __( 'New Gallery', 'kadence-galleries' ),
			'all_items' => __( 'All Galleries', 'kadence-galleries' ),
			'view_item' => __( 'View Gallery', 'kadence-galleries' ),
			'search_items' => __( 'Search Galleries', 'kadence-galleries' ),
			'not_found' => __( 'No Gallery found', 'kadence-galleries' ),
			'not_found_in_trash' => __( 'No Gallery found in Trash', 'kadence-galleries' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'K Galleries', 'kadence-galleries' ),
		);
		$gallery_post_slug = apply_filters( 'kadence_gallery_post_slug', 'gallery' );
		$galleryargs = array(
			'labels' => $gallerylabels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'exclude_from_search' => false,
			'query_var' => true,
			'rewrite' => array(
				'slug' => $gallery_post_slug,
				'pages' => true,
			),
			'has_archive' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'show_in_menu' => true,
			'menu_position' => null,
			'menu_icon' => 'dashicons-images-alt',
			'show_in_rest' => true,
			'supports' => array( 'title', 'thumbnail', 'comments' ),
		);

		register_post_type( 'kt_gallery', $galleryargs );
		// Initialize Taxonomy Labels
		$albumlabels = array(
			'name' => __( 'Gallery Albums', 'pinnacle' ),
			'singular_name' => __( 'Album', 'pinnacle' ),
			'search_items' => __( 'Search Albums', 'pinnacle' ),
			'all_items' => __( 'All Albums', 'pinnacle' ),
			'parent_item' => __( 'Parent Album', 'pinnacle' ),
			'parent_item_colon' => __( 'Parent Album:', 'pinnacle' ),
			'edit_item' => __( 'Edit Album', 'pinnacle' ),
			'update_item' => __( 'Update Album', 'pinnacle' ),
			'add_new_item' => __( 'Add New Album', 'pinnacle' ),
			'new_item_name' => __( 'New Album Name', 'pinnacle' ),
		);
		$gallery_album_slug = apply_filters( 'kadence_galleries_album_slug', 'gallery-album' );
		// Register Custom Taxonomy
		register_taxonomy(
			'kt_album',
			array( 'kt_gallery' ),
			array(
				'hierarchical' => true, // define whether to use a system like tags or categories
				'labels' => $albumlabels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite'  => array( 'slug' => $gallery_album_slug ),
			)
		);
	}

	public function kt_add_kt_gallery_columns( $columns ) {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title', 'kadence-galleries' ),
			'shortcode' => __( 'Shortcode', 'kadence-galleries' ),
			'image' => __( 'Featured Image', 'kadence-galleries' ),
			'album' => __( 'Album', 'kadence-galleries' ),
		);
	}
	public function kt_custom_kt_gallery_column( $column, $post_id ) {
		switch ( $column ) {

			case 'shortcode':
				echo '<code>';
				echo '[kadence_gallery id="' . esc_attr( $post_id ) . '"]';
				echo '</code>';
				break;

			case 'image':
				if ( has_post_thumbnail( $post_id ) ) {
					echo the_post_thumbnail( 'thumbnail', array( 'class' => 'gallery-featured-image' ) );
				} else {
					$images = get_post_meta( $post_id, '_kt_gal_images', true );
					if ( ! empty( $images ) ) {
						$attachments = array_filter( explode( ',', $images ) );
						echo wp_get_attachment_image( $attachments['0'], 'thumbnail', false, array( 'class' => 'gallery-featured-image' ) );
					} else {
						_e( 'None set', 'kadence-galleries' );
					}
				}
				break;
			case 'album':
				$terms = get_the_term_list( $post_id, 'kt_album', '', ',', '' );
				if ( is_string( $terms ) ) {
					echo $terms;
				} else {
					_e( 'No album set', 'kadence-galleries' );
				}
				break;
		}
	}
	public function mosaic_sizes( $icount, $size = 'normal', $i = 1 ) {
		if ( $icount == '2' ) {
			if ( $i == 2 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-8 ktg-mosaic-8 ktg-sm-mosaic-8 ktg-ss-mosaic-12';
			} else {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-4 ktg-mosaic-4 ktg-sm-mosaic-4 ktg-ss-mosaic-12 mosaic-grid-size';
			}
			$reset = 2;
		} else if ( $icount == '3' ) {
			if ( $i == 2 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-6 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-12 mosaic-grid-size';
			}
			$reset = 3;
		} else if ( $icount == '4' ) {
			if ( $i == 2 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-6 ktg-sm-mosaic-12 ktg-ss-mosaic-12 mosaic-large-square-grid-size';
			} elseif ( $i == 1 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-6 ktg-sm-mosaic-12 ktg-ss-mosaic-12';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-6 ktg-ss-mosaic-12 mosaic-grid-size';
			}
			$reset = 8;
		} else if ( $icount == '5' ) {
			if ( $i == 2 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8';
			} elseif ( $i == 5 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-6 ktg-sm-mosaic-12 ktg-ss-mosaic-12';
			} elseif ( $i == 1 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-tall-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-grid-size';
			}
			$reset = 5;
		} else if ( $icount == '6' || $icount == '12' || $icount == '24' || $icount == '30' ) {
			if ( $i == 1 || $i == 5 || $i == 8 || $i == 12 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8';
			} elseif ( $i == 3 || $i == 9 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4';
			} elseif ( $i == 2 || $i == 10 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8 mosaic-large-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-grid-size';
			}
			$reset = 12;
		} else if ( $icount == '7' || $icount == '14' || $icount == '21' || $icount == '28' ) {
			if ( $i == 7 || $i == 8 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-12';
			} elseif ( $i == 3 || $i == 11 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-6';
			} elseif ( $i == 1 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-12 mosaic-large-grid-size';
			} elseif ( $i == 13 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-6 mosaic-large-wide-grid-size mosaic-ss-tall-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-6 mosaic-grid-size';
			}
			$reset = 14;
		} else if ( $icount == '8' || $icount == '16' ) {
			if ( $i == 1 || $i == 16 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8';
			} elseif ( $i == 3 || $i == 13 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4';
			} elseif ( $i == 2 || $i == 5 || $i == 10 || $i == 14 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8 mosaic-sm-wide-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-grid-size';
			}
			$reset = 16;
		} else if ( $icount == '9' || $icount == '18' ) {
			if ( $i == 2 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-6 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8';
			} elseif ( $i == 5 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-6 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-sm-square-grid-size';
			} elseif ( $i == 6 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-4';
			} elseif ( $i == 1 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4';
			} elseif ( $i == 7 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-tall-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-3 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-grid-size';
			}
			$reset = 9;
		} else if ( $icount == '10' || $icount == '20' ) {
			if ( $i == 2 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8';
			} elseif ( $i == 6 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-12';
			} elseif ( $i == 5 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-12';
			} elseif ( $i == 8 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-6 ktg-sm-mosaic-8 ktg-ss-mosaic-8 mosaic-sm-wide-grid-size';
			} elseif ( $i == 1 || $i == 7 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-tall-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-4 mosaic-grid-size';
			}
			$reset = 10;
		} else if ( $icount == '11' ) {
			if ( $i == 5 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-12 mosaic-sm-square-grid-size mosaic-ss-inherit-wide';
			} elseif ( $i == 3 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-6';
			} elseif ( $i == 8 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-6 mosaic-tall-grid-size mosaic-tall-sm-square-grid-size';
			} elseif ( $i == 1 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-12 mosaic-large-wide-grid-size';
			} elseif ( $i == 10 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-4 ktg-ss-mosaic-6 mosaic-large-wide-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-4 ktg-ss-mosaic-6 mosaic-grid-size';
			}
			$reset = 11;
		} else if ( $icount == '13' ) {
			if ( $i == 6 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12 mosaic-sm-square-grid-size mosaic-ss-inherit-wide';
			} else if ( $i == 9 || $i == 13 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12';
			} elseif ( $i == 4 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-6';
			} elseif ( $i == 11 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-6 mosaic-tall-grid-size';
			} elseif ( $i == 2 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-6';
			} elseif ( $i == 8 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-6';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-6 mosaic-grid-size';
			}
			$reset = 13;
		} else if ( $icount == '15' ) {
			if ( $i == 2 || $i == 8 || $i == 14 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-8';
			} else if ( $i == 12 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-4 mosaic-sm-square-grid-size';
			} elseif ( $i == 6 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12';
			} elseif ( $i == 5 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12';
			} elseif ( $i == 7 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-8';
			} elseif ( $i == 1 || $i == 10 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-4';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-4 mosaic-grid-size';
			}
			$reset = 15;
		} else {
			if ( $i == 5 || $i == 16 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12 mosaic-sm-square-grid-size mosaic-ss-inherit-wide';
			} else if ( $i == 17 ) {
				$image_width = 800;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12';
			} elseif ( $i == 3 || $i == 13 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-6';
			} elseif ( $i == 8 ) {
				$image_width = 400;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-6 mosaic-tall-grid-size mosaic-tall-sm-square-grid-size';
			} elseif ( $i == 1 || $i == 12 || $i == 18 || $i == 19 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-12 mosaic-large-wide-grid-size';
			} elseif ( $i == 10 ) {
				$image_width = 800;
				$image_height = 800;
				$itemsize = 'ktg-xxl-mosaic-40 ktg-mosaic-6 ktg-sm-mosaic-6 ktg-ss-mosaic-6 mosaic-large-wide-grid-size';
			} else {
				$image_width = 400;
				$image_height = 400;
				$itemsize = 'ktg-xxl-mosaic-25 ktg-mosaic-3 ktg-sm-mosaic-3 ktg-ss-mosaic-6 mosaic-grid-size';
			}
			$reset = 19;
		}
		return array(
			'itemsize' => $itemsize,
			'width' => $image_width,
			'height' => $image_height,
			'reset' => $reset,
		);
	}
	public function kt_galleries_remove_revolution_slider_meta_boxes() {
		remove_meta_box( 'mymetabox_revslider_0', 'kt_gallery', 'normal' );
	}
	public function kt_shortcode_gallery_album_handler( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'id'                => null,
					'slug'              => null,
					'imgwidth'          => null,
					'imgheight'         => null,
					'posts_per_page'    => '10',
					'orderby'           => 'menu_order',
					'order'             => 'ASC',
				),
				$atts
			)
		);
		if ( empty( $id ) ) {
			if ( empty( $slug ) ) {
				return;
			} else {
				$id = $slug;
			}
		} else {
			$term = get_term( $id, 'kt_album' );
			$slug = $term->slug;
		}
		$columns = array(
			'lg' => '3',
			'md' => '3',
			'sm' => '2',
		);
		$columns = apply_filters( 'kadence_album_columns', $columns, $slug );
		wp_enqueue_script( 'kadence-galleries' );
		ob_start();
		echo '<div class="kt-album-gallery">';
			do_action( 'kadence_gallery_album_before_content' );
		?>
				<div class="gallery-albumn-content clearfix">
				<div class="kt-gal-outer">
					<div class="kt-galleries-loading kt-loadeding">
						<div class="kt-load-cube-grid">
							<div class="kt-load-cube kt-load-cube1"></div>
							<div class="kt-load-cube kt-load-cube2"></div>
							<div class="kt-load-cube kt-load-cube3"></div>
							<div class="kt-load-cube kt-load-cube4"></div>
							<div class="kt-load-cube kt-load-cube5"></div>
							<div class="kt-load-cube kt-load-cube6"></div>
							<div class="kt-load-cube kt-load-cube7"></div>
							<div class="kt-load-cube kt-load-cube8"></div>
							<div class="kt-load-cube kt-load-cube9"></div>
							<div class="kt-loading-text"><?php echo __( 'Loading Images', 'kadence-galleries' ); ?></div>
						</div>
					</div>
					<div class="kt-galleries-container kt-local-gallery kt-loadeding kt-gallery-<?php echo esc_attr( $slug ); ?> kt-galleries-show-caption-bottom kt-ga-columns-lg-<?php echo esc_attr( $columns['lg'] ); ?> kt-ga-columns-md-<?php echo esc_attr( $columns['md'] ); ?> kt-ga-columns-sm-<?php echo esc_attr( $columns['sm'] ); ?>" data-gallery-source="local" data-gallery-lightbox="none" data-gallery-id="<?php echo esc_attr( $slug ); ?>" data-ratio="notcropped" data-gallery-name="<?php echo esc_attr( $slug ); ?>" data-gallery-filter="false" data-gallery-type="masonry">
				<?php
				do_action( 'kadence_gallery_album_content_before' );
				global $wp_query;
				if ( isset( $wp_query ) ) {
					$temp = $wp_query;
				} else {
					$temp = null;
				}
				if ( get_query_var( 'paged' ) ) {
					$paged = get_query_var( 'paged' );
				} else if ( get_query_var( 'page' ) ) {
					$paged = get_query_var( 'page' );
				} else {
					$paged = 1;
				}
				$args = array(
					'paged'          => $paged,
					'posts_per_page' => $posts_per_page,
					'orderby'        => $orderby,
					'order'          => $order,
					'post_type'      => 'kt_gallery',
					'kt_album'       => $slug,
				);
				$args = apply_filters( 'kadence_gallery_album_args', $args );
				$wp_query = new WP_Query();
				$wp_query->query( $args );
				if ( $wp_query ) {
					while ( $wp_query->have_posts() ) :
						$wp_query->the_post();
						do_action( 'kadence_gallery_loop' );
					endwhile;
				}
				?>
					</div>
				</div>
				</div>
		<?php
		do_action( 'kadence_gallery_album_content_after' );
		echo '</div>';
		$wp_query = null;
		$wp_query = $temp;
		wp_reset_postdata();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	public function kt_shortcode_gallery_handler( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'id'                => null,
					'imgwidth'          => null,
					'imgheight'         => null,
				),
				$atts
			)
		);
		if ( empty( $id ) ) {
			return;
		}
		global $kt_galleries;
		if ( isset( $kt_galleries['gallery_lightbox'] ) && $kt_galleries['gallery_lightbox'] == 'magnific' ) {
			$lightbox = 'magnific';
		} else {
			$lightbox = 'photoswipe';
		}
		$class = array();
		$source = get_post_meta( $id, '_kt_gal_source', true );
		$filter = 'false';
		$filter_cats = '';
		$images_link = 'lightbox';
		$images = get_post_meta( $id, '_kt_gal_images', true );
		if ( ! empty( $images ) ) {
			$attachments = array_filter( explode( ',', $images ) );
		}
		$icount = ( isset( $attachments ) && is_array( $attachments ) ? count( $attachments ) : 0 );
		$galleryid = $id;
		$galleryname = get_the_title( $id );
		$class[] = 'kt-local-gallery';
		$api = 'local';
		$filter = get_post_meta( $id, '_kt_gal_filter', true );
		$filter_cats = get_post_meta( $id, '_kt_gal_filter_categories', true );
		$caption_title = get_post_meta( $id, '_kt_gal_caption_title', true );
		$caption_category = get_post_meta( $id, '_kt_gal_caption_category', true );
		$type = get_post_meta( $id, '_kt_gal_type', true );
		$pinbtn = get_post_meta( $id, '_kt_gal_pinbtn', true );
		$pagination = get_post_meta( $id, '_kt_gal_pagination', true );
		if ( isset( $pagination ) && 'true' === $pagination ) {
			if ( isset( $_GET['gallery-page'] ) ) {
				$gpaged = (int) $_GET['gallery-page'];
			} else {
				$gpaged = 1;
			}
		}
		$per_page = get_post_meta( $id, '_kt_gal_pagination_per_page', true );
		$per_page = ( ! empty( $per_page ) ? (int)  $per_page : 10 );
		if ( isset( $pinbtn ) && 'true' == $pinbtn ) {
			$pinbtn = true;
		} else {
			$pinbtn = false;
		}
		$image_caption = get_post_meta( $id, '_kt_gal_caption_grid', true );
		if ( ! empty( $image_caption ) && $image_caption == 'bottom' ) {
			$class[] = 'kt-galleries-show-caption-bottom';
		} elseif ( ! empty( $image_caption ) && $image_caption == 'center' ) {
			$class[] = 'kt-galleries-show-caption-center';
		} else {
			$class[] = 'kt-galleries-hide-caption';
		}
		$columns = '4';
		$tabcolumns = '3';
		$mobilecolumns = '2';
		$theight = '300';
		$script_type = 'masonry';
		$lazy = true;
		if ( $type != 'mosaic' && $type != 'tiles' ) {
			$columns = get_post_meta( $id, '_kt_gal_columns', true );
			$tabcolumns = get_post_meta( $id, '_kt_gal_tab_columns', true );
			$mobilecolumns = get_post_meta( $id, '_kt_gal_mobile_columns', true );
			$ratio = 'cropped';
			$crop = true;
			if ( $columns == '6' || $columns == '5' || $columns == '4' ) {
				$width = '300';
			} else if ( $columns == '1' ) {
				$width = '1200';
			} else if ( $columns == '2' ) {
				$width = '800';
			} else {
				$width = '400';
			}
			if ( $type == 'portrait' ) {
				$tempheight = $width * 1.35;
				$height = floor( $tempheight );
			} else if ( $type == 'landscape' ) {
				$tempheight = $width / 1.35;
				$height = floor( $tempheight );
			} else if ( $type == 'wide_landscape' ) {
				$tempheight = $width / 2;
				$height = floor( $tempheight );
			} else if ( $type == 'masonry' ) {
				$height = null;
				$ratio = 'notcropped';
				$crop = false;
			} else {
					$height = $width;
			}
			$outerclass = 'kt-gal-outer-basic';
		} else if ( $type == 'tiles' ) {
			$ratio = 'notcropped';
			$lazy = false;
			$crop = false;
			$script_type = 'tiles';
			$class[] = 'kt-gallery-tiles';
			$tile_height = get_post_meta( $id, '_kt_gal_tile_height', true );
			if ( ! empty( $tile_height ) ) {
				$theight = $tile_height;
			}
			$height = $theight + 200;
			$width = null;
			$outerclass = 'kt-gal-outer-tiles';
			wp_enqueue_script( 'kadence-tiles' );
		} else if ( $type == 'mosaic' ) {
			$ratio = 'cropped';
			$crop = true;
			$script_type = 'packery';
			$class[] = 'kt-gallery-mosaic';
			$size = 'normal';
			$columns = 'mosaic';
			$tabcolumns = 'mosaic';
			$mobilecolumns = 'mosaic';
			$height = 'mosaic';
			$width = 'mosaic';
			$outerclass = 'kt-gal-outer-mosaic';
			wp_enqueue_script( 'kadence-packery' );
		}
		if ( ! empty( $imgwidth ) ) {
			$width = $imgwidth;
		}
		if ( ! empty( $imgheight ) ) {
			$height = $imgheight;
		}
		wp_enqueue_script( 'kadence-galleries' );
		ob_start();
		?>
			<div class="kt-gal-outer <?php echo esc_attr( $outerclass ); ?>">
			<div class="kt-galleries-loading kt-loadeding">
			<?php if ( $filter == 'true' && ! empty( $filter_cats ) && $type != 'tiles' ) { ?>
					<div class="kt-filters clearfix">
				<?php
				echo '<ul class="clearfix kt-option-set">';
				echo '<li><a href="#" data-filter="*" title="All" class="selected"><span>' . __( 'All', 'kadence-galleries' ) . '</span></a></li>';
				foreach ( $filter_cats as $cat_id ) {
					$filter_term = get_term( $cat_id, 'kt-media-category' );
					echo '<li class="postclass"><a href="#" data-filter=".' . esc_attr( $filter_term->slug ) . '" title="" rel="' . esc_attr( $filter_term->slug ) . '"><span>' . esc_html( $filter_term->name ) . '</span></a></li>';
				}
				echo '</ul>';
				?>
				</div>
			<?php } ?>
				<div class="kt-load-cube-grid">
					<div class="kt-load-cube kt-load-cube1"></div>
					<div class="kt-load-cube kt-load-cube2"></div>
					<div class="kt-load-cube kt-load-cube3"></div>
					<div class="kt-load-cube kt-load-cube4"></div>
					<div class="kt-load-cube kt-load-cube5"></div>
					<div class="kt-load-cube kt-load-cube6"></div>
					<div class="kt-load-cube kt-load-cube7"></div>
					<div class="kt-load-cube kt-load-cube8"></div>
					<div class="kt-load-cube kt-load-cube9"></div>
					<div class="kt-loading-text"><?php echo __( 'Loading Images', 'kadence-galleries' ); ?></div>
				</div>
		</div>
		<div class="kt-galleries-container kt-loadeding kt-gallery-<?php echo esc_attr( $galleryid ); ?> <?php echo esc_attr( implode( ' ', $class ) ); ?> kt-ga-columns-lg-<?php echo esc_attr( $columns ); ?> kt-ga-columns-md-<?php echo esc_attr( $tabcolumns ); ?> kt-ga-columns-sm-<?php echo esc_attr( $mobilecolumns ); ?>" data-gallery-source="<?php echo esc_attr( $source ); ?>" data-gallery-lightbox="<?php echo esc_attr( $lightbox ); ?>" data-image-width="<?php echo esc_attr( $width ); ?>" data-image-height="<?php echo esc_attr( $height ); ?>" data-api="<?php echo esc_attr( $api ); ?>" data-images-link="<?php echo esc_attr( $images_link ); ?>" data-gallery-id="<?php echo esc_attr( $galleryid ); ?>" data-ratio="<?php echo esc_attr( $ratio ); ?>" data-gallery-name="<?php echo esc_attr( $galleryname ); ?>" data-gallery-height="<?php echo esc_attr( $theight ); ?>" data-gallery-filter="<?php echo esc_attr( $filter ); ?>" data-gallery-type="<?php echo esc_attr( $script_type ); ?>" data-gallery-lastrow="nojustify" data-gallery-margins="5">
		<?php
		$i = 1;
		$fullattachments = $attachments;
		if ( ! empty( $attachments ) ) {
			if ( isset( $pagination ) && 'true' === $pagination ) {
				if ( $gpaged > 1 ) {
					$count = (int) ( $per_page * ( $gpaged - 1 ) );
					$b = 1;
					foreach ( $fullattachments as $key ) {
						array_shift( $attachments );
						if ( $count === $b ) {
							break;
						}
						$b++;
					}
				}
			}
			foreach ( $attachments as $attachment ) {
				if ( $type == 'mosaic' ) {
					$imosaic = $this->mosaic_sizes( $icount, $size, $i );
					$width = apply_filters( 'kadence_galleries_mosaic_image_width', $imosaic['width'], $i );
					$height = apply_filters( 'kadence_galleries_mosaic_image_height', $imosaic['height'], $i );
					$image_grid_class = apply_filters( 'kadence_galleries_mosaic_size', $imosaic['itemsize'], $i );
				} else {
					$image_grid_class = '';
				}
					$img_meta = kt_gal_get_attachment_meta( $attachment );
					$terms = get_the_terms( $attachment, 'kt-media-category' );
					$custom_url = get_post_meta( $attachment, '_gallery_link_url', true );

				if ( ! empty( $custom_url ) && $custom_url != 'none' ) {
					$image_link = $custom_url;
					$image_link_class = 'kt-gal-external';
					$link_target = get_post_meta( $attachment, '_gallery_link_target', true );
					if ( ! empty( $link_target ) && $link_target != 'none' ) {
						$linktarget = 'target="' . esc_attr( $link_target ) . '"';
					} else {
						$linktarget = 'target="_self"';
					}
				} else {
					$image_link = wp_get_attachment_url( $attachment );
					$image_link_class = '';
					$linktarget = '';
				}
				if ( $terms && ! is_wp_error( $terms ) ) {
					$links = array();
					foreach ( $terms as $term ) {
						$links[] = $term->slug;
					}
					$tax = join( ' ', $links );
				} else {
					$tax = '';
				}
					$item_title = false;
				if ( $caption_title == 'true' ) {
					if ( ! empty( $img_meta['title'] ) ) {
						$item_title = true;
					}
				}
					$item_cat = false;
				if ( $caption_category == 'true' ) {
					if ( $terms && ! is_wp_error( $terms ) ) {
						$item_cat = true;
					}
				}
					$item_cap = false;
				if ( ! empty( $img_meta['caption'] ) ) {
					$item_cap = true;
				}
				if ( $item_cap || $item_cat || $item_title ) {
					$caption_class = 'kt-has-caption';
				} else {
					$caption_class = 'kt-no-caption';
				}
					echo '<div class="kt-gallery-item ' . esc_attr( $caption_class ) . ' ' . esc_attr( $tax ) . ' ' . esc_attr( $image_link_class ) . ' ' . esc_attr( $image_grid_class ) . '">';
						echo '<a href="' . esc_url( $image_link ) . '" class="kt-no-lightbox kt-gal-fade-in" ' . $linktarget . ' data-size="' . esc_attr( $img_meta['width'] ) . 'x' . esc_attr( $img_meta['height'] ) . '">';
							echo kt_gal_get_full_intrinsic_image_output( $width, $height, $crop, null, $img_meta['alt'], $attachment, false, false, false );

							echo '<div class="kt-gallery-item-overlay"><div class="kt-overlay-border"></div><div class="kt-gallery-align-vertical"><i class="kt-gallery-item-icon"></i></div></div>';
							echo '<div class="kt-gallery-caption-container">';
								echo '<div class="kt-gallery-caption">';
				if ( $item_cat ) {
					echo '<div class="kt-gallery-categories">';
					$tags = array();
					foreach ( $terms as $term ) {
						$tags[] = $term->name;
					} echo wp_kses_post( implode( ', ', $tags ) );
					echo '</div>';
				}
				if ( $item_title ) {
					echo '<div class="kt-gallery-caption-title">';
					echo '<h5>' . esc_html( $img_meta['title'] ) . '</h5>';
					echo '</div>';
				}
				if ( $item_cap ) {
					echo '<div class="kt-gallery-caption-text">';
					echo wp_kses_post( $img_meta['caption'] );
					echo '</div>';
				}
								echo '</div>';
							echo '</div>';
						echo '</a>';
				if ( $pinbtn ) {
					echo '<a class="kadence-galleries-pinterest-btn" targe="_blank" href="' . 'https://pinterest.com/pin/create/button/?url=' . rawurlencode( get_permalink() ) . '&media=' . rawurlencode( wp_get_attachment_url( $attachment ) ) . '&description=' . rawurlencode( esc_html( $img_meta['caption'] ) ) . '"><svg style="display:inline-block;vertical-align:middle" viewBox="0 0 384 512" height="20" width="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><title>' . __( 'Pin to Pinterest', 'kadence-galleries' ) . '</title><path d="M204 6.5C101.4 6.5 0 74.9 0 185.6 0 256 39.6 296 63.6 296c9.9 0 15.6-27.6 15.6-35.4 0-9.3-23.7-29.1-23.7-67.8 0-80.4 61.2-137.4 140.4-137.4 68.1 0 118.5 38.7 118.5 109.8 0 53.1-21.3 152.7-90.3 152.7-24.9 0-46.2-18-46.2-43.8 0-37.8 26.4-74.4 26.4-113.4 0-66.2-93.9-54.2-93.9 25.8 0 16.8 2.1 35.4 9.6 50.7-13.8 59.4-42 147.9-42 209.1 0 18.9 2.7 37.5 4.5 56.4 3.4 3.8 1.7 3.4 6.9 1.5 50.4-69 48.6-82.5 71.4-172.8 12.3 23.4 44.1 36 69.3 36 106.2 0 153.9-103.5 153.9-196.8C384 71.3 298.2 6.5 204 6.5z"></path></svg></a>';
				}
					echo '</div>';
				if ( $type == 'mosaic' ) {
					if ( $imosaic['reset'] == $i ) {
						$i = 0;
					}
				}
				if ( isset( $pagination ) && 'true' === $pagination ) {
					if ( $per_page === $i ) {
						break;
					}
				}
				$i ++;
			}
		}
		?>
		</div>
		</div>
		<?php
		if ( isset( $pagination ) && 'true' === $pagination && ! empty( $fullattachments ) ) {
			if ( $per_page < count( $fullattachments ) ) {
				$total = ceil( count( $fullattachments ) / $per_page );
				$args = array(
					'base'               => get_permalink() . '%_%', // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
					'format'             => '?gallery-page=%#%', // ?page=%#% : %#% is replaced by the page number
					'total'              => $total,
					'current'            => $gpaged,
					'end_size'           => 1,
					'mid_size'           => 2,
					'type'               => 'plain',
				);
				$args['mid_size'] = 3;
				$args['end_size'] = 1;
				$args['prev_text'] = '<svg style="display:inline-block;vertical-align:middle" class="k-galleries-pagination-left-svg" viewBox="0 0 320 512" height="14" width="8" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg>';
				$args['next_text'] = '<svg style="display:inline-block;vertical-align:middle" class="k-galleries-pagination-right-svg" viewBox="0 0 320 512" height="14" width="8" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>';

				echo '<div class="k-galleries-page-nav">';
				echo '<nav class="navigation pagination nav-links" role="navigation" aria-label="Galleries">';
				echo '<h2 class="screen-reader-text">' . __( 'Gallery Page Navigation', 'kadence-galleries' ) . '</h2>';
					echo paginate_links( $args );
				echo '</nav>';
				echo '</div>';
			}
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	public function kt_galleries_enqueue_scripts() {
		global $kt_galleries;
		$depen = array( 'jquery', 'kadence-isotope', 'kadence-packery' );
		if ( isset( $kt_galleries['gallery_lightbox'] ) && 'magnific' == $kt_galleries['gallery_lightbox'] ) {
			if ( isset( $kt_galleries['gallery_lightbox_skin'] ) && $kt_galleries['gallery_lightbox_skin'] == 'dark' ) {
				wp_enqueue_style( 'magnific_pop_css', KTG_URL . 'assets/magnific/magnific-popup-dark.css', false, KTG_VERSION );
			} else {
				wp_enqueue_style( 'magnific_pop_css', KTG_URL . 'assets/magnific/magnific-popup-light.css', false, KTG_VERSION );
			}
			// Check for Classic Theme.
			if ( ! class_exists( 'Kadence_API_Manager' ) ) {
				wp_register_script( 'magnific-popup', KTG_URL . 'assets/magnific/min/magnific-popup-min.js', array( 'jquery' ), KTG_VERSION, true );
				$depen[] = 'magnific-popup';
			}
		} else {
			if ( isset( $kt_galleries['gallery_lightbox_skin'] ) && $kt_galleries['gallery_lightbox_skin'] == 'dark' ) {
				wp_enqueue_style( 'photoswipe-dark-skin', KTG_URL . 'assets/photoswipe/dark-skin.css', false, KTG_VERSION );
			} else {
				wp_enqueue_style( 'photoswipe-light-skin', KTG_URL . 'assets/photoswipe/light-skin.css', false, KTG_VERSION );
			}
			wp_register_script( 'photoswipe', KTG_URL . 'assets/photoswipe/photoswipe.min.js', array(), KTG_VERSION, true );
			wp_register_script( 'photoswipe-ui', KTG_URL . 'assets/photoswipe/photoswipe-ui-default.min.js', array( 'photoswipe' ), KTG_VERSION, true );
			$depen[] = 'photoswipe';
			$depen[] = 'photoswipe-ui';
		}
		wp_enqueue_style( 'kt-galleries-css', KTG_URL . 'assets/css/kadence-galleries.css', false, KTG_VERSION );
		wp_register_script( 'kadence-tiles', KTG_URL . 'assets/js/min/kt-tiles-min.js', array( 'jquery' ), KTG_VERSION, true );
		wp_register_script( 'kadence-isotope', KTG_URL . 'assets/js/kadence-isotope.js', array( 'jquery' ), KTG_VERSION, true );
		wp_register_script( 'kadence-packery', KTG_URL . 'assets/js/kadence-packery.js', array( 'jquery', 'kadence-isotope' ), KTG_VERSION, true );
		wp_register_script( 'kadence-galleries', KTG_URL . 'assets/js/kt-galleries.js', $depen, KTG_VERSION, true );
		$gallery_translation_array = array(
			'close' => __( 'Close', 'kadence-galleries' ),
			'share' => __( 'Share', 'kadence-galleries' ),
			'togglefull' => __( 'Toggle fullscreen', 'kadence-galleries' ),
			'zoom' => __( 'Zoom in/out', 'kadence-galleries' ),
			'prev' => __( 'Previous', 'kadence-galleries' ),
			'next' => __( 'Next', 'kadence-galleries' ),
			'fullcaption' => __( 'Show full caption', 'kadence-galleries' ),
			'mincaption' => __( 'Minimize caption', 'kadence-galleries' ),
		);
		wp_localize_script( 'kadence-galleries', 'kadenceGallery', $gallery_translation_array );
	}
	function kt_gallery_field( $field, $meta, $object_id, $object_type, $field_type_object ) {
		echo '<div class="kt-galleries kt_meta_image_gallery">';
		echo '<div class="kt_gallery_images">';
		$attachments = array_filter( explode( ',', $meta ) );
		if ( $attachments ) :
			foreach ( $attachments as $attachment_id ) {
				$img = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
				$imgfull = wp_get_attachment_image_src( $attachment_id, 'full' );
				echo '<a class="of-uploaded-image edit-meta" data-attachment-id="' . esc_attr( $attachment_id ) . '" href="#">';
				echo '<img class="kt-gallery-image" id="gallery_widget_image_' . esc_attr( $attachment_id ) . '" src="' . esc_url( $img[0] ) . '" width="' . esc_attr( $img[1] ) . '" height="' . esc_attr( $img[2] ) . '" />';
				echo '</a>';
			}
		endif;
		echo '</div>';
		echo $field_type_object->input(
			array(
				'class' => 'gallery_values',
				'type' => 'hidden',
			)
		);
		echo '<a href="#" onclick="return false;" id="kt-edit-gallery" class="kt-gal-gallery-attachments button button-primary">' . __( 'Add/Edit Gallery', 'kadence-galleries' ) . '</a>';
		echo '<a href="#" onclick="return false;" id="kt-clear-gallery" class="kt-gal-gallery-attachments button">' . __( 'Clear Gallery', 'kadence-galleries' ) . '</a>';
		echo '</div>';
	}
	public function kt_gallery_field_sanitize( $override_value, $value ) {
		return $value;
	}
	public function kt_gal_small_render_text_number( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		echo $field_type_object->input(
			array(
				'class' => 'cmb2-text-small',
				'type' => 'number',
				'step' => 'any',
			)
		);
	}
	public function kt_gallery_shortcode_field( $field, $value, $object_id, $object_type, $field_type_object ) {
		global $post;
		echo '<code>';
		echo '[kadence_gallery id="' . esc_attr( $post->ID ) . '"]';
		echo '</code>';
	}
	public function kt_galleries_metaboxes() {
		global $kt_galleries;
		$prefix = '_kt_gal_';
		$kt_meta_galleries = new_cmb2_box(
			array(
				'id'            => $prefix . 'settings',
				'title'         => __( 'Gallery Settings', 'kadence-galleries' ),
				'object_types'  => array( 'kt_gallery' ), // Post type
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name' => __( 'Gallery Shortcode', 'kadence-slider' ),
				'desc' => '',
				'id'   => $prefix . 'gallery_useage',
				'type' => 'kt_gallery_useage',
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name' => __( 'Gallery Images', 'kadence-galleries' ),
				'desc' => __( 'Add images for gallery here', 'kadence-galleries' ),
				'id'   => $prefix . 'images',
				'type' => 'kt_galleries',
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Gallery Image Style', 'kadence-galleries' ),
				'id'            => $prefix . 'type',
				'type'          => 'select',
				'default'          => 'masonry',
				'options'          => array(
					'masonry'           => __( 'Masonry (Image defined ratio)', 'kadence-galleries' ),
					'square'            => __( 'Square', 'kadence-galleries' ),
					'portrait'          => __( 'Portrait', 'kadence-galleries' ),
					'landscape'         => __( 'Landscape', 'kadence-galleries' ),
					'wide_landscape'    => __( 'Wide Landscape', 'kadence-galleries' ),
					'tiles'             => __( 'Tiles (Image defined ratio)', 'kadence-galleries' ),
					'mosaic'            => __( 'Mosaic', 'kadence-galleries' ),
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Columns', 'kadence-galleries' ),
				'id'            => $prefix . 'columns',
				'type'          => 'select',
				'default'       => '4',
				'options'          => array(
					'3'     => __( 'Three Columns (3)', 'kadence-galleries' ),
					'4'     => __( 'Four Columns (4)', 'kadence-galleries' ),
					'5'     => __( 'Five Columns (5)', 'kadence-galleries' ),
					'6'     => __( 'Six Columns (6)', 'kadence-galleries' ),
					'2'     => __( 'Two Columns (2)', 'kadence-galleries' ),
					'1'     => __( 'One Columns (1)', 'kadence-galleries' ),
				),
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'type',
					'data-kadence-condition-value' => 'square,portrait,landscape,wide_landscape,masonry',
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Columns for Tablet screen sizes', 'kadence-galleries' ),
				'id'            => $prefix . 'tab_columns',
				'type'          => 'select',
				'default'       => '3',
				'options'          => array(
					'3'     => __( 'Three Columns (3)', 'kadence-galleries' ),
					'4'     => __( 'Four Columns (4)', 'kadence-galleries' ),
					'5'     => __( 'Five Columns (5)', 'kadence-galleries' ),
					'6'     => __( 'Six Columns (6)', 'kadence-galleries' ),
					'2'     => __( 'Two Columns (2)', 'kadence-galleries' ),
					'1'     => __( 'One Columns (1)', 'kadence-galleries' ),
				),
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'type',
					'data-kadence-condition-value' => 'square,portrait,landscape,wide_landscape,masonry',
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Columns for Mobile screen sizes', 'kadence-galleries' ),
				'id'            => $prefix . 'mobile_columns',
				'type'          => 'select',
				'default'       => '2',
				'options'          => array(
					'3'     => __( 'Three Columns (3)', 'kadence-galleries' ),
					'4'     => __( 'Four Columns (4)', 'kadence-galleries' ),
					'5'     => __( 'Five Columns (5)', 'kadence-galleries' ),
					'6'     => __( 'Six Columns (6)', 'kadence-galleries' ),
					'2'     => __( 'Two Columns (2)', 'kadence-galleries' ),
					'1'     => __( 'One Columns (1)', 'kadence-galleries' ),
				),
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'type',
					'data-kadence-condition-value' => 'square,portrait,landscape,wide_landscape,masonry',
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Height of tiles row', 'kadence-galleries' ),
				'id'            => $prefix . 'tile_height',
				'type'          => 'kt_gal_text_number',
				'default'       => '300',
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'type',
					'data-kadence-condition-value' => 'tiles',
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Caption in gallery grid', 'kadence-galleries' ),
				'id'            => $prefix . 'caption_grid',
				'type'          => 'select',
				'default'       => 'false',
				'options'          => array(
					'false'     => __( 'Do not Show', 'kadence-galleries' ),
					'bottom'    => __( 'Show on image hover (bottom)', 'kadence-galleries' ),
					'center'    => __( 'Show on image hover (center)', 'kadence-galleries' ),
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Add Image Title to Caption', 'kadence-galleries' ),
				'id'            => $prefix . 'caption_title',
				'type'          => 'select',
				'default'       => 'false',
				'options'          => array(
					'false'     => __( 'False', 'kadence-galleries' ),
					'true'      => __( 'True', 'kadence-galleries' ),
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Add Image Category Name(s) to Caption', 'kadence-galleries' ),
				'id'            => $prefix . 'caption_category',
				'type'          => 'select',
				'default'       => 'false',
				'options'          => array(
					'false'     => __( 'False', 'kadence-galleries' ),
					'true'      => __( 'True', 'kadence-galleries' ),
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Add image category filter to gallery', 'kadence-galleries' ),
				'desc'          => __( 'All Images must have an assigned category', 'kadence-galleries' ),
				'id'            => $prefix . 'filter',
				'type'          => 'select',
				'default'       => 'false',
				'options'          => array(
					'false'     => __( 'False', 'kadence-galleries' ),
					'true'      => __( 'True', 'kadence-galleries' ),
				),
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'type',
					'data-kadence-condition-value' => 'square,portrait,landscape,wide_landscape,masonry',
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'      => __( 'Choose which categories to filter', 'kadence-galleries' ),
				'id'        => $prefix . 'filter_categories',
				'type'      => 'pw_multiselect',
				'options'     => $this->kt_gal_get_term_options(
					array(
						'taxonomy' => 'kt-media-category',
						'hide_empty' => false,
					)
				),
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'type',
					'data-kadence-condition-value' => 'square,portrait,landscape,wide_landscape,masonry',
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Include Pinterest Button on Images', 'kadence-galleries' ),
				'id'            => $prefix . 'pinbtn',
				'type'          => 'select',
				'default'       => 'false',
				'options'          => array(
					'false'     => __( 'False', 'kadence-galleries' ),
					'true'      => __( 'True', 'kadence-galleries' ),
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'          => __( 'Turn on Pagination', 'kadence-galleries' ),
				'id'            => $prefix . 'pagination',
				'type'          => 'select',
				'default'       => 'false',
				'options'          => array(
					'false'     => __( 'False', 'kadence-galleries' ),
					'true'      => __( 'True', 'kadence-galleries' ),
				),
			)
		);
		$kt_meta_galleries->add_field(
			array(
				'name'      => __( 'Items Per Page', 'kadence-galleries' ),
				'id'        => $prefix . 'pagination_per_page',
				'type'          => 'select',
				'default'       => '10',
				'options'          => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
					'9' => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
					'13' => '13',
					'14' => '14',
					'15' => '15',
					'16' => '16',
					'17' => '17',
					'18' => '18',
					'19' => '19',
					'20' => '20',
				),
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'pagination',
					'data-kadence-condition-value' => 'true',
				),
			)
		);

	}
	public function kt_gal_get_term_options( $args ) {
		$args = is_array( $args ) ? $args : array();

		$args = wp_parse_args( $args, array( 'taxonomy' => 'category' ) );

		$taxonomy = $args['taxonomy'];

		$terms = (array) cmb2_utils()->wp_at_least( '4.5.0' )
			? get_terms( $args )
			: get_terms( $taxonomy, $args );

		// Initate an empty array
		$term_options = array();
		if ( ! empty( $terms ) && ! is_a( $terms, 'WP_Error' ) ) {
			foreach ( $terms as $term ) {
				$term_options[ $term->term_id ] = $term->name;
			}
		}

		return $term_options;
	}
	
}