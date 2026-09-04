<?php
/**
 * CMB2 Post Search field
 *
 * Custom field for CMB2 which adds a post-search dialog for
 * searching/attaching other post IDs
 *
 * @category WordPressLibrary
 * @package  CMB2_Post_Search_field
 * @author   WebDevstudios <contact@webdevstudios.com>
 * @license  GPL-2.0+
 * @version  0.2.5
 * @link     https://github.com/WebDevStudios/CMB2-Post-Search-field
 * @since    0.2.4
 */
class CMB2_Post_Search_field {

	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.2.4
	 * @return CMB2_Post_Search_field A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	protected function __construct() {
		add_action( 'cmb2_render_post_search_text', [ $this, 'render_field' ], 10, 5 );
		add_action( 'cmb2_after_form', [ $this, 'render_js' ], 10, 4 );
		add_action( 'cmb2_post_search_field_add_find_posts_div', [ $this, 'add_find_posts_div' ] );
		// add_action( 'admin_init', [ $this, 'ajax_find_posts' ] );
		add_action( 'wp_ajax_pattern_find_posts', [ $this, 'wp_ajax_pattern_find_posts' ] );
	}

	public function render_field( $field, $escaped_value, $object_id, $object_type, $field_type ) {
		$extras = '';
		$extras .= '<div class="cmb-type-post-search-text-title-collections"></div>';
		$extras .= '<div class="cmb-type-post-search-text-title-categories"></div>';
		$extras .= '<div class="cmb-type-post-image-preview"></div>';
		if ( ! empty( $escaped_value ) ) {
			$extras = '';
			$collections_array              = [];
			if ( get_the_terms( $escaped_value, 'kadence-cloud-collections' ) ) {
				$terms = get_the_terms( $escaped_value, 'kadence-cloud-collections' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $key => $value ) {
						$collections_array[] = $value->name;
					}
				}
			}
			$categories_array              = [];
			if ( get_the_terms( $escaped_value, 'kadence-cloud-categories' ) ) {
				$terms = get_the_terms( $escaped_value, 'kadence-cloud-categories' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $key => $value ) {
						$categories_array[] = $value->name;
					}
				}
			}
			$extras .= '<div class="cmb-type-post-search-text-title-collections"><b>Collections:</b> ' . esc_html( implode( ', ', $collections_array ) ) . '</div>';
			$extras .= '<div class="cmb-type-post-search-text-title-categories"><b>Categories:</b> ' . esc_html( implode( ', ', $categories_array ) ) . '</div>';
			if ( has_post_thumbnail($escaped_value ) ) {
				$extras .= '<div class="cmb-type-post-image-preview" style="max-height:50px; display: flex;">' . get_the_post_thumbnail( $escaped_value, 'medium-large', [ 'style' => 'object-fit: contain; max-width:200px; width:100%; border: 2px solid #eee; height:auto; max-height:50px;' ] ) . '</div>';
			} else {
				$image_meta = get_post_meta( $escaped_value, '_kc_preview_image', true );
				if ( ! empty( $image_meta['url'] ) ) {
					$extras .= '<div class="cmb-type-post-image-preview" style="max-height:50px; display: flex;"><img src="' . esc_url( $image_meta['url'] ) . '" style="max-height:50px; max-width:200px; width:100%; border: 2px solid #eee; height:auto" /></div>';
				}
			}
		}
		echo $field_type->input(
			[
				'desc'  => '<div class="cmb-type-post-search-text-title' . ( empty( $escaped_value ) ? ' cmb-empty-value' : '' ) . '">' . ( ! empty( $escaped_value ) ? get_the_title( $escaped_value ) : $field_type->_text( 'select_text', __( 'Select Posts or Pages' ) ) ) . '</div>' . $extras,
				'class' => 'cmb-type-post-search-text-input',
				'data-search' => json_encode(
					[
						'posttype'       => $field->args( 'post_type' ),
						'selecttype'     => 'radio' == $field->args( 'select_type' ) ? 'radio' : 'checkbox',
						'selectbehavior' => 'replace' == $field->args( 'select_behavior' ) ? 'replace' : 'add',
						'errortxt'       => esc_attr( $field_type->_text( 'error_text', __( 'An error has occurred. Please reload the page and try again.' ) ) ),
						'findtxt'        => esc_attr( $field_type->_text( 'find_text', __( 'Find Posts or Pages' ) ) ),
					] 
				),
			]
		);
	}

	public function render_js( $cmb_id, $object_id, $object_type, $cmb ) {
		static $rendered;

		if ( $rendered ) {
			return;
		}

		$fields = $cmb->prop( 'fields' );

		if ( ! is_array( $fields ) ) {
			return;
		}

		$has_post_search_field = $this->has_post_search_text_field( $fields );

		if ( ! $has_post_search_field ) {
			return;
		}

		// JS needed for modal
		// wp_enqueue_media();
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'wp-backbone' );

		if ( ! is_admin() ) {
			// Will need custom styling!
			// @todo add styles for front-end
			require_once ABSPATH . 'wp-admin/includes/template.php';
			do_action( 'cmb2_post_search_field_add_find_posts_div' );
		}

		// markup needed for modal
		add_action( 'admin_footer', 'find_posts_div' );

		// @TODO this should really be in its own JS file.
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			'use strict';

			var SearchView = window.Backbone.View.extend({
				el         : '#find-posts',
				overlaySet : false,
				$overlay   : false,
				$idInput   : false,
				$checked   : false,
				$itemsTitle: false,
				$title     : false,
				$image     : false,
				$collections : false,
				$categories : false,
				$itemsCollections : false,
				$itemsCategories : false,
				$itemsImage : false,

				events : {
					'keypress .find-box-search :input' : 'maybeStartSearch',
					'keyup #find-posts-input'  : 'escClose',
					'click #find-posts-submit' : 'selectPost',
					'click #find-posts-search' : 'send',
					'click #find-posts-close'  : 'close',
				},

				initialize: function() {
					this.$spinner  = this.$el.find( '.find-box-search .spinner' );
					this.$input    = this.$el.find( '#find-posts-input' );
					this.$response = this.$el.find( '#find-posts-response' );
					this.$overlay  = $( '.ui-find-overlay' );

					this.listenTo( this, 'open', this.open );
					this.listenTo( this, 'close', this.close );
				},

				escClose: function( evt ) {
					if ( evt.which && 27 === evt.which ) {
						this.close();
					}
				},

				close: function() {
					this.$overlay.hide();
					this.$el.hide();
				},

				open: function() {
					this.$response.html('');

					// WP, why you so dumb? (why isn't text in its own dom node?)
					this.$el.show().find( '#find-posts-head' ).html( this.findtxt + '<div id="find-posts-close"></div>' );

					this.$input.focus();

					if ( ! this.$overlay.length ) {
						$( 'body' ).append( '<div class="ui-find-overlay"></div>' );
						this.$overlay  = $( '.ui-find-overlay' );
					}

					this.$overlay.show();

					// Pull some results up by default
					this.send();

					return false;
				},

				maybeStartSearch: function( evt ) {
					if ( 13 == evt.which ) {
						this.send();
						return false;
					}
				},

				send: function() {

					var search = this;
					search.$spinner.addClass('is-active');

					$.ajax( ajaxurl, {
						type     : 'POST',
						dataType : 'json',
						data     : {
							ps               : search.$input.val(),
							action           : 'pattern_find_posts',
							cmb2_post_search : true,
							post_search_cpt  : search.posttype,
							_ajax_nonce      : $('#find-posts #_ajax_nonce').val()
						}
					}).always( function() {

						search.$spinner.removeClass('is-active');

					}).done( function( response ) {

						if ( ! response.success ) {
							search.$response.text( search.errortxt );
						}

						var data = response.data;
						//console.log( data );
						if ( 'checkbox' === search.selecttype ) {
							data = data.replace( /type="radio"/gi, 'type="checkbox"' );
						}

						search.$response.html( data );

					}).fail( function() {
						search.$response.text( search.errortxt );
					});
				},

				selectPost: function( evt ) {
					evt.preventDefault();

					this.$checked = $( '#find-posts-response input[type="' + this.selecttype + '"]:checked' );
					this.$itemsTitle = $( '#find-posts-response input[type="' + this.selecttype + '"]:checked ' ).closest( '.found-posts' ).find( 'td label' );
					this.$itemsCollections = $( '#find-posts-response input[type="' + this.selecttype + '"]:checked ' ).closest( '.found-posts' ).find( 'td.pattern-collections' ).html();
					this.$itemsCategories = $( '#find-posts-response input[type="' + this.selecttype + '"]:checked ' ).closest( '.found-posts' ).find( 'td.pattern-categories' ).html();
					this.$itemsImage = $( '#find-posts-response input[type="' + this.selecttype + '"]:checked ' ).closest( '.found-posts' ).find( 'td.pattern-image' ).html();
					var checked = this.$checked.map(function() { return this.value; }).get();

					var names = this.$itemsTitle.map(function() {
						console.log( this );
						return this.innerText;
					}).get();

					var collections = this.$itemsCollections;
					var categories = this.$itemsCategories;
					var images = this.$itemsImage;

					if ( ! checked.length ) {
						this.close();
						return;
					}

					this.handleSelected( checked, names, collections, categories, images );
				},

				handleSelected: function( checked, names, collections, categories, images ) {
					checked = checked.join( ', ' );
					names = names.join( ', ' );
					if ( 'add' === this.selectbehavior ) {
						var existing = this.$idInput.val();
						if ( existing ) {
							checked = existing + ', ' + checked;
						}
					}
					this.$title.removeClass( 'cmb-empty-value' );
					this.$title.html( names );
					this.$collections.html( '<b>Collections:</b> ' + collections );
					this.$categories.html( '<b>Categories:</b> ' + categories );	
					this.$image.html( images );
					this.$idInput.val( checked ).trigger( 'change' );
					this.close();
				}

			});

			window.cmb2_post_search = new SearchView();

			window.cmb2_post_search.closeSearch = function() {
				window.cmb2_post_search.trigger( 'close' );
			};

			window.cmb2_post_search.openSearch = function( evt ) {
				var search = window.cmb2_post_search;

				search.$idInput = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td input[type="text"]' );
				search.$title = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td .cmb-type-post-search-text-title' );
				search.$collections = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td .cmb-type-post-search-text-title-collections' );
				search.$categories = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td .cmb-type-post-search-text-title-categories' );
				search.$image = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td .cmb-type-post-image-preview' );
				// Setup our variables from the field data
				$.extend( search, search.$idInput.data( 'search' ) );

				search.trigger( 'open' );
			};

			window.cmb2_post_search.addSearchButtons = function() {
				var $this = $( this );
				var data = $this.data( 'search' );
				$this.after( '<div title="'+ data.findtxt +'" class="dashicons dashicons-search cmb2-post-search-button"></div>');
			};

			$( '.cmb-type-post-search-text .cmb-td input[type="text"]' ).each( window.cmb2_post_search.addSearchButtons );

			$( '.cmb2-wrap' ).on( 'click', '.cmb-type-post-search-text .cmb-td .dashicons-search', window.cmb2_post_search.openSearch );
			$( 'body' ).on( 'click', '.ui-find-overlay', window.cmb2_post_search.closeSearch );
			window.CMB2 = window.CMB2 || {};
			(function(window, document, $, cmb, undefined){
			'use strict';

			// We'll keep it in the CMB2 object namespace
			cmb.initClearOnAdd = function(){
				cmb.metabox().find('.cmb-repeatable-group').on( 'cmb2_add_row', cmb.clearGroup );
			};

			cmb.clearGroup = function( evt, row ) {
				var $focus = $(row).find('.cmb-type-post-search-text .cmb-type-post-search-text-title');
				if ( $focus.length ) {
					$focus.html( 'Select Pattern' ).addClass( 'cmb-empty-value' );
				}
				var $collections = $(row).find('.cmb-type-post-search-text-title-collections');
				if ( $collections.length ) {
					$collections.html( '' );
				}
				var $categories = $(row).find('.cmb-type-post-search-text-title-categories');
				if ( $categories.length ) {
					$categories.html( '' );
				}
				var $image = $(row).find('.cmb-type-post-image-preview');
				if ( $image.length ) {
					$image.html( '' );
				}
			};

			$(document).ready( cmb.initClearOnAdd );

		})(window, document, jQuery, CMB2);
		});
		</script>
		<style type="text/css" media="screen">
			.cmb2-post-search-button {
				color: #333;
				margin: 0;
				cursor: pointer;
				height: 32px;
				width: 330px;
				text-align: left;
				vertical-align: middle;
				position: relative;
				z-index: 10;
				margin-right: -310px;
			}
			.cmb2-post-search-button:before {
				line-height: 32px;
			}
			.cmb-type-post-search-text-title {
				padding: 8px;
				background: #fff;
				border: 1px solid #bbb;
				border-radius: 3px;
				width: 300px;
				overflow: hidden;
				box-sizing: border-box;
			}
			.cmb-type-post-search-text-title.cmb-empty-value {
				background: #f2f2f2;
				border: 1px dashed #aaa;
			}
			.cmb-type-post-search-text .cmb-td .cmb-type-post-search-text-input {
				visibility: hidden;
				width: 0;
				overflow: hidden;
				height: 0px;
				padding: 0px;
			}
			.cmb-type-post-search-text .cmb-td {
				display: flex;
				align-items: center;
				gap:10px;
			}
		</style>
		<?php

		$rendered = true;
	}

	public function has_post_search_text_field( $fields ) {
		
		foreach ( $fields as $field ) {
			if ( isset( $field['fields'] ) ) {
				if ( $this->has_post_search_text_field( $field['fields'] ) ) {
					return true;
				}
			}
			if ( 'post_search_text' == $field['type'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add the find posts div via a hook so we can relocate it manually
	 */
	public function add_find_posts_div() {
		add_action( 'wp_footer', 'find_posts_div' );
	}


	/**
	 * Check to see if we have a post type set and, if so, add the
	 * pre_get_posts action to set the queried post type
	 */
	public function ajax_find_posts() {
		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_POST['cmb2_post_search'], $_POST['action'], $_POST['post_search_cpt'] )
			&& 'find_posts' == $_POST['action']
			&& ! empty( $_POST['post_search_cpt'] )
		) {
			add_action( 'pre_get_posts', [ $this, 'set_post_type' ] );
		}
	}
	/**
	 * Handles querying posts for the Find Posts modal via AJAX.
	 *
	 * @see window.findPosts
	 *
	 * @since 3.1.0
	 */
	public function wp_ajax_pattern_find_posts() {
		check_ajax_referer( 'find-posts' );
		$types = isset( $_POST['post_search_cpt'] ) ? $_POST['post_search_cpt'] : '';
		$types = is_array( $types ) ? array_map( 'esc_attr', $types ) : esc_attr( $types );


		$args = array(
			'post_type'      => $types,
			'post_status'    => 'any',
			'posts_per_page' => 50,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		);

		$search = wp_unslash( $_POST['ps'] );

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$posts = get_posts( $args );

		if ( ! $posts ) {
			wp_send_json_error( __( 'No items found.' ) );
		}

		$html = '<table class="widefat"><thead><tr><th class="found-radio"><br /></th><th>' . __( 'Title' ) . '</th><th class="no-break">' . __( 'Date' ) . '</th><th class="no-break">' . __( 'Status' ) . '</th><th class="no-break">' . __( 'Collections' ) . '</th><th class="no-break">' . __( 'Categories' ) . '</th><th class="no-break">' . __( 'Image' ) . '</th></tr></thead><tbody>';
		$alt  = '';
		foreach ( $posts as $post ) {
			$title = trim( $post->post_title ) ? $post->post_title : __( '(no title)' );
			$image = '';
			if ( has_post_thumbnail($post->ID ) ) {
				$image = '<div class="cmb-type-post-image-preview" style="max-height:100px; display: flex;">' . get_the_post_thumbnail( $post->ID, 'medium-large', [ 'style' => 'object-fit: contain; max-width:200px; width:100%; border: 2px solid #eee; height:auto; max-height:100px;' ] ) . '</div>';
			} else {
				$image_meta = get_post_meta( $post->ID, '_kc_preview_image', true );
				if ( ! empty( $image_meta['url'] ) ) {
					$image = '<div class="cmb-type-post-image-preview" style="max-height:100px; display: flex;"><img src="' . esc_url( $image_meta['url'] ) . '" style="max-width:100%; width:200px; border: 2px solid #eee; height:auto" /></div>';
				}
			}
			$collections_array              = [];
			if ( get_the_terms( $post, 'kadence-cloud-collections' ) ) {
				$terms = get_the_terms( $post, 'kadence-cloud-collections' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $key => $value ) {
						$collections_array[] = $value->name;
					}
				}
			}
			$categories_array              = [];
			if ( get_the_terms( $post, 'kadence-cloud-categories' ) ) {
				$terms = get_the_terms( $post, 'kadence-cloud-categories' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $key => $value ) {
						$categories_array[] = $value->name;
					}
				}
			}
			$alt   = ( 'alternate' === $alt ) ? '' : 'alternate';

			switch ( $post->post_status ) {
				case 'publish':
				case 'private':
					$stat = __( 'Published' );
					break;
				case 'future':
					$stat = __( 'Scheduled' );
					break;
				case 'pending':
					$stat = __( 'Pending Review' );
					break;
				case 'draft':
					$stat = __( 'Draft' );
					break;
			}

			if ( '0000-00-00 00:00:00' === $post->post_date ) {
				$time = '';
			} else {
				/* translators: Date format in table columns, see https://www.php.net/manual/datetime.format.php */
				$time = mysql2date( __( 'Y/m/d' ), $post->post_date );
			}

			$html .= '<tr class="' . trim( 'found-posts ' . $alt ) . '"><td class="found-radio"><input type="radio" id="found-' . $post->ID . '" name="found_post_id" value="' . esc_attr( $post->ID ) . '"></td>';
			$html .= '<td><label for="found-' . $post->ID . '">' . esc_html( $title ) . '</label></td><td class="no-break">' . esc_html( $time ) . '</td><td class="no-break custom-status">' . esc_html( $stat ) . ' </td><td class="no-break pattern-collections">' . esc_html( implode( ', ', $collections_array ) ) . '</td><td class="no-break pattern-categories">' . esc_html( implode( ', ', $categories_array ) ) . '</td><td class="no-break pattern-image">' . $image . '</td></tr>' . "\n\n";
		}

		$html .= '</tbody></table>';

		wp_send_json_success( $html );
	}

	/**
	 * Set the post type via pre_get_posts
	 *
	 * @param  array $query  The query instance
	 */
	public function set_post_type( $query ) {
		$types = $_POST['post_search_cpt'];
		$types = is_array( $types ) ? array_map( 'esc_attr', $types ) : esc_attr( $types );
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
		$query->set( 'post_type', $types );
	}
}
CMB2_Post_Search_field::get_instance();

// preserve a couple functions for back-compat.


if ( ! function_exists( 'cmb2_post_search_render_field' ) ) {
	function cmb2_post_search_render_field( $field, $escaped_value, $object_id, $object_type, $field_type ) {
		_deprecated_function( __FUNCTION__, '0.2.4', 'Please access these methods through the CMB2_Post_Search_field::get_instance() object.' );

		return CMB2_Post_Search_field::get_instance()->render_field( $field, $escaped_value, $object_id, $object_type, $field_type );
	}
}

// Remove old versions.
remove_action( 'cmb2_render_post_search_text', 'cmb2_post_search_render_field', 10, 5 );
remove_action( 'cmb2_after_form', 'cmb2_post_search_render_js', 10, 4 );

if ( ! function_exists( 'cmb2_has_post_search_text_field' ) ) {
	function cmb2_has_post_search_text_field( $fields ) {
		_deprecated_function( __FUNCTION__, '0.2.4', 'Please access these methods through the CMB2_Post_Search_field::get_instance() object.' );

		return CMB2_Post_Search_field::get_instance()->has_post_search_text_field( $fields );
	}
}
