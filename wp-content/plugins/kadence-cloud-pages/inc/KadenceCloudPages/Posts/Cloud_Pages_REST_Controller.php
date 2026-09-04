<?php
/**
 * The provider for all rest controller related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */

namespace KadenceWP\CloudPages\Posts;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider for all rest controller related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */
class Cloud_Pages_REST_Controller extends WP_REST_Controller {

	/**
	 * Include property name.
	 */
	const PROP_KEY = 'key';

	/**
	 * Include property name.
	 */
	const PROP_SITE = 'site';

		/**
	 * Include property name.
	 */
	const PROP_RETURN_COUNT = 'items';

	/**
	 * Include property name.
	 */
	const PROP_RETURN_PAGE = 'page-item';

		/**
	 * Include property name.
	 */
	const PROP_RETURN_PAGE_NUMBER = 'page';
	/**
	 * Include property name.
	 */
	const PROP_READ_ONLY = 'read';

	/**
	 * Rest Namespace.
	 *
	 * @var string
	 */
	public $namespace = '';

	/**
	 * Rest Get Base.
	 *
	 * @var string
	 */
	public $rest_base = '';

	/**
	 * Posts to process
	 *
	 * @var array
	 */
	private $processed_posts = [];

	/**
	 * Allow us to enable merged defaults on blocks individually.
	 * Considered setting this as a property within each block, but it's easier to see an exhaustive list here.
	 * Eventually all blocks will be supported.
	 *
	 * @var array
	 */
	protected $is_cpt_block = [
		'kadence/navigation',
		'kadence/header',
		'kadence/advanced-form',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'kadence-cloud/v1';
		$this->rest_base = 'pages';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/page',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/page-categories',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_categories' ],
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				],
			]
		);
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$key         = $request->get_param( self::PROP_KEY );
		$page_number = $request->get_param( self::PROP_RETURN_PAGE );
		$read_only   = $request->get_param( self::PROP_READ_ONLY );
		if ( empty( $page_number ) ) {
			$page_number = $request->get_param( self::PROP_RETURN_PAGE_NUMBER );
		}
		if ( $this->check_access( $request ) ) {
			$request_extras = apply_filters( 'kadence_cloud_rest_request_extras', [], $request );
			if ( ! empty( $page_number ) ) {
				$page_number = absint( $page_number );
			}
			if ( empty( $page_number ) ) {
				return new WP_Error( 'invalid_request', __( 'Invalid Request, Page ID is required', 'kadence-cloud' ) );
			}
			$settings = json_decode( get_option( 'kadence_cloud' ), true );
			if ( ! $read_only && isset( $settings['enable_analytics'] ) && $settings['enable_analytics'] ) {
				$key  = $request->get_param( self::PROP_KEY );
				$data = [
					'type'    => 'page',
					'post_id' => absint( $page_number ),
					'style'   => 'light',
				];
				\KadenceWP\KadenceCloud\Analytics_Dashboard_Util::record_event( $data );
			}
			add_filter( 'kadence_blocks_css_output_media_queries', '__return_false' );
			add_filter( 'kadence_blocks_post_block_style_force_output', '__return_true' );
			add_filter( 'kadence-blocks-countup-static', '__return_true' );
			add_filter( 'kadence-blocks-progress-bar-static', '__return_true' );
			return $this->get_template( $page_number, $key, $read_only, $request_extras );

		} else {
			return wp_send_json( __( 'Invalid Request, Incorrect Access Key', 'kadence-cloud' ) );
		}
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$key         = $request->get_param( self::PROP_KEY );
		$per_page    = $request->get_param( self::PROP_RETURN_COUNT );
		$page_number = $request->get_param( self::PROP_RETURN_PAGE_NUMBER );

		if ( $this->check_access( $request ) ) {
			$request_extras = apply_filters( 'kadence_cloud_rest_request_extras', [], $request );
			if ( ! empty( $per_page ) ) {
				$per_page = absint( $per_page );
			} else {
				$per_page = -1;
			}
			if ( ! empty( $page_number ) ) {
				$page_number = absint( $page_number );
			} else {
				$page_number = 1;
			}
			add_filter( 'kadence_blocks_css_output_media_queries', '__return_false' );
			add_filter( 'kadence_blocks_post_block_style_force_output', '__return_true' );
			add_filter( 'kadence-blocks-countup-static', '__return_true' );
			add_filter( 'kadence-blocks-progress-bar-static', '__return_true' );
			return $this->get_templates( $per_page, $page_number, $key, $request_extras );

		} else {
			return new WP_Error( 'invalid_request', __( 'Invalid Request, Incorrect Access Key', 'kadence-cloud' ) );
		}
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_categories( $request ) {
		if ( $this->check_access( $request ) ) {
			$terms       = get_terms(
				[
					'taxonomy'   => 'page_categories',
					'hide_empty' => false,
					'orderby'    => 'menu_order',
					'order'      => 'ASC',
				]
			);
			$terms_array = [];
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $key => $value ) {
					$terms_array[ $value->slug ] = $value->name;
				}
			}
			return rest_ensure_response( $terms_array );
		} else {
			return wp_send_json( __( 'Invalid Request, Incorrect Access Key', 'kadence-cloud' ) );
		}
	}
	/**
	 * Check if the request should get access to the files.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return Boolean true or false based on if access should be granted.
	 */
	public function check_access( $request ) {
		$access   = false;
		$key      = $request->get_param( self::PROP_KEY );
		$keys     = [];
		$settings = json_decode( get_option( 'kadence_cloud' ), true );
		if ( isset( $settings['access_keys'] ) && ! empty( $settings['access_keys'] ) && is_array( $settings['access_keys'] ) ) {
			$access_keys = [];
			if ( isset( $settings['access_keys'][0] ) && is_array( $settings['access_keys'][0] ) ) {
				foreach ( $settings['access_keys'] as $the_key => $the_keys ) {
					if ( $the_keys['key'] === $key ) {
						$access = true;
						break;
					}
				}
			} else {
				$access_keys = $settings['access_keys'];
				if ( is_array( $access_keys ) && ! empty( $access_keys ) && in_array( $key, $access_keys ) ) {
					$access = true;
				}
			}
		}
		return apply_filters( 'kadence_cloud_rest_request_access', $access, $key, $request );
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public function get_template_array( $template_query, $request_extras, $key ) {
		$library             = [];
		$fallback_image_args = apply_filters(
			'kadence_cloud_post_fallback_image_args',
			[
				'w' => intval( 1280 ),
				'h' => intval( 800 ),
			]
		);
		$html_sections       = false;
		if ( 'new-pages' === $key ) {
			// Supports old fallback.
			$html_sections = true;
		}
		if ( $template_query->have_posts() ) :
			while ( $template_query->have_posts() ) :
				$template_query->the_post();
				global $post, $wp_styles, $wp_scripts;
				$wp_styles  = new \WP_Styles();
				$wp_scripts = new \WP_Scripts();
				$slug       = 'pg-' . get_the_ID();
				if ( $slug ) {
					$library[ $slug ]         = [];
					$library[ $slug ]['slug'] = $slug;
					$library[ $slug ]['id']   = get_the_ID();
					$library[ $slug ]['name'] = the_title_attribute( 'echo=0' );
					$terms_array              = [];
					if ( get_the_terms( $post, 'page_categories' ) ) {
						$terms = get_the_terms( $post, 'page_categories' );
						if ( is_array( $terms ) ) {
							foreach ( $terms as $key => $value ) {
								// If the term is top-level (i.e., its parent is 0)
								if ( $value->parent == 0 ) {
									$terms_array[ $value->slug ] = $value->name;
								}
							}
						}
					}
					$library[ $slug ]['categories'] = $terms_array;
					$keywords_array                 = [];
					if ( get_the_terms( $post, 'kadence-cloud-keywords' ) ) {
						$terms = get_the_terms( $post, 'kadence-cloud-keywords' );
						if ( is_array( $terms ) ) {
							foreach ( $terms as $key => $value ) {
								$keywords_array[] = $value->name;
							}
						}
					}
					$library[ $slug ]['keywords'] = $keywords_array;
					$terms_array                  = [];
					if ( get_the_terms( $post, 'page_industries' ) ) {
						$terms = get_the_terms( $post, 'page_industries' );
						if ( is_array( $terms ) ) {
							foreach ( $terms as $key => $value ) {
								$terms_array[ $value->slug ] = $value->name;
							}
						}
					}
					$library[ $slug ]['industries'] = $terms_array;
					$terms_array                    = [];
					if ( get_the_terms( $post, 'page_style' ) ) {
						$terms = get_the_terms( $post, 'page_style' );
						if ( is_array( $terms ) ) {
							foreach ( $terms as $key => $value ) {
								$terms_array[ $value->slug ] = $value->name;
							}
						}
					}
					$library[ $slug ]['page_styles'] = $terms_array;
					if ( ! empty( $request_extras ) && is_array( $request_extras ) ) {
						foreach ( $request_extras as $key => $data ) {
							$library[ $slug ][ $key ] = $data;
						}
					}
					$post_extras = apply_filters( 'kadence_cloud_post_extra_args', [], $post, $request_extras );
					if ( ! empty( $post_extras ) && is_array( $post_extras ) ) {
						foreach ( $post_extras as $key => $data ) {
							$library[ $slug ][ $key ] = $data;
						}
					}
					$library[ $slug ]['pro']    = apply_filters( 'kadence_cloud_post_is_pro', false, $post, $request_extras );
					$library[ $slug ]['locked'] = apply_filters( 'kadence_cloud_post_is_locked', false, $post, $request_extras );
					if ( apply_filters( 'kadence_cloud_page_send_content', false, $post, $request_extras ) ) {
						$library[ $slug ]['content'] = $post->post_content;
					}
					if ( apply_filters( 'kadence_cloud_page_send_html_content', false, $post, $request_extras ) ) {
						if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
							$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
							if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
								$kadence_blocks_css::$styles        = [];
								$kadence_blocks_css::$head_styles   = [];
								$kadence_blocks_css::$custom_styles = [];
							}
						}
						if ( class_exists( 'Kadence_Blocks_Frontend' ) ) {
							$kadence_blocks = \Kadence_Blocks_Frontend::get_instance();
							if ( method_exists( $kadence_blocks, 'frontend_build_css' ) ) {
								$kadence_blocks->frontend_build_css( $post );
							}
							if ( class_exists( 'Kadence_Blocks_Pro_Frontend' ) ) {
								$kadence_blocks_pro = \Kadence_Blocks_Pro_Frontend::get_instance();
								if ( method_exists( $kadence_blocks_pro, 'frontend_build_css' ) ) {
									$kadence_blocks_pro->frontend_build_css( $post );
								}
							}
						}
						if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
							$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
							if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
								$kadence_blocks_css->frontend_block_css();
							}
						}
						ob_start();
						wp_print_styles();
						echo do_blocks( $post->post_content );
						$wp_scripts->print_scripts();
						$library[ $slug ]['html'] = ob_get_clean();
						$wp_styles                = new \WP_Styles();
						$wp_scripts               = new \WP_Scripts();
					}
					if ( apply_filters( 'kadence_cloud_page_use_acf', false ) ) {
						$rows = get_field( 'pattern' );
					} else {
						$rows = get_post_meta( get_the_ID(), 'sections', true );
					}
					$page_rows = [];
					if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
						$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
						if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
							$kadence_blocks_css::$styles        = [];
							$kadence_blocks_css::$head_styles   = [];
							$kadence_blocks_css::$custom_styles = [];
						}
					}
					$page_rows_content = '';
					if ( $rows ) {
						foreach ( $rows as $key => $row ) {
							$pattern_id = ( ! empty( $row['selected_pattern'] ) ) ? $row['selected_pattern'] : false;
							if ( ! $pattern_id ) {
								continue;
							}
							$pattern_object = get_post( $pattern_id );
							if ( ! $pattern_object ) {
								continue;
							}
							if ( ! is_object( $pattern_object ) ) {
								continue;
							}
							$style                = ( ! empty( $row['color_style'] ) ) ? $row['color_style'] : 'light';
							$condition            = ( ! empty( $row['condition'] ) ) ? $row['condition'] : 'general';
							$content              = $pattern_object->post_content;
							$context              = '';
							$page_single_row_html = '';
							if ( isset( $row['context'] ) && is_object( $row['context'] ) && ! empty( $row['context']->slug ) ) {
								$context = $row['context']->slug;
							}
							$terms_array = [];
							if ( get_the_terms( $pattern_object, 'kadence-cloud-categories' ) ) {
								$terms = get_the_terms( $pattern_object, 'kadence-cloud-categories' );
								if ( is_array( $terms ) ) {
									foreach ( $terms as $t_key => $value ) {
										$terms_array[ $value->slug ] = $value->name;
									}
								}
							}
							$required_array = [];
							if ( get_the_terms( $pattern_object, 'requires' ) ) {
								$terms = get_the_terms( $pattern_object, 'requires' );
								if ( is_array( $terms ) ) {
									foreach ( $terms as $t_key => $value ) {
										$required_array[ $value->slug ] = $value->name;
									}
								}
							}
							$page_rows[ $key ] = [
								'pattern_id'        => $pattern_id,
								'pattern_style'     => $style,
								'pattern_context'   => $context,
								'pattern_category'  => $terms_array,
								'pattern_requires'  => $required_array,
								'pattern_condition' => $condition,
								'pattern_html'      => '',
							];
							if ( apply_filters( 'kadence_cloud_post_send_content', true, $post, $request_extras ) ) {
								$cpt_blocks                              = $this->get_cpt_blocks( $content );
								$page_rows[ $key ]['pattern_content']    = $content;
								$page_rows[ $key ]['pattern_cpt_blocks'] = $cpt_blocks;
							}
							$row_wrapper_class = '';
							if ( $style === 'dark' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'dark' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-dark-page-section';
							} elseif ( $style === 'highlight' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'highlight' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-highlight-page-section';
							}
							if ( ! empty( $condition ) && 'general' !== $condition ) {
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-page-conditional-' . $condition;
							}
							$pattern_object->post_content = $content;
							if ( class_exists( 'Kadence_Blocks_Frontend' ) ) {
								$kadence_blocks = \Kadence_Blocks_Frontend::get_instance();
								if ( method_exists( $kadence_blocks, 'frontend_build_css' ) ) {
									$kadence_blocks->frontend_build_css( $pattern_object );
								}
								if ( class_exists( 'Kadence_Blocks_Pro_Frontend' ) ) {
									$kadence_blocks_pro = \Kadence_Blocks_Pro_Frontend::get_instance();
									if ( method_exists( $kadence_blocks_pro, 'frontend_build_css' ) ) {
										$kadence_blocks_pro->frontend_build_css( $pattern_object );
									}
								}
							}
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '<div class="' . $row_wrapper_class . '">';
							}
							$page_single_row_html .= $content;
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '</div>';
							}
							$page_rows_content .= $page_single_row_html;
						}
					}
					if ( apply_filters( 'kadence_cloud_post_send_full_page_html_content', false, $post, $request_extras ) ) {
						if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
							$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
							if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
								$kadence_blocks_css->frontend_block_css();
							}
						}
						ob_start();
							wp_print_styles();
							echo do_blocks( $page_rows_content );
							$wp_scripts->print_scripts();
							$page_rows_html            = ob_get_clean();
						$library[ $slug ]['rows_html'] = $page_rows_html;
					}
					$library[ $slug ]['rows'] = $page_rows;
					if ( $rows && apply_filters( 'kadence_cloud_post_send_html_content', $html_sections, $post, $request_extras ) ) {
						$wp_styles  = new \WP_Styles();
						$wp_scripts = new \WP_Scripts();
						foreach ( $rows as $key => $row ) {
							if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
								$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
								if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
									$kadence_blocks_css::$styles        = [];
									$kadence_blocks_css::$head_styles   = [];
									$kadence_blocks_css::$custom_styles = [];
								}
							}
							$pattern_id           = $row['selected_pattern'];
							$pattern_object       = get_post( $pattern_id );
							$style                = ( ! empty( $row['color_style'] ) ) ? $row['color_style'] : 'light';
							$condition            = ( ! empty( $row['condition'] ) ) ? $row['condition'] : 'general';
							$content              = $pattern_object->post_content;
							$page_single_row_html = '';
							$row_wrapper_class    = '';
							if ( $style === 'dark' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'dark' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-dark-page-section';
							} elseif ( $style === 'highlight' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'highlight' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-highlight-page-section';
							}
							if ( ! empty( $condition ) && 'general' !== $condition ) {
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-page-conditional-' . $condition;
							}
							$pattern_object->post_content = $content;
							add_filter( 'kadence_blocks_render_inline_css', '__return_false' );
							if ( class_exists( 'Kadence_Blocks_Frontend' ) ) {
								$kadence_blocks = \Kadence_Blocks_Frontend::get_instance();
								if ( method_exists( $kadence_blocks, 'frontend_build_css' ) ) {
									$kadence_blocks->frontend_build_css( $pattern_object );
								}
								if ( class_exists( 'Kadence_Blocks_Pro_Frontend' ) ) {
									$kadence_blocks_pro = \Kadence_Blocks_Pro_Frontend::get_instance();
									if ( method_exists( $kadence_blocks_pro, 'frontend_build_css' ) ) {
										$kadence_blocks_pro->frontend_build_css( $pattern_object );
									}
								}
							}
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '<div class="' . $row_wrapper_class . '">';
							}
							$page_single_row_html .= $content;
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '</div>';
							}
							$page_rows_content .= $page_single_row_html;
							$styles             = [];
							$custom_styles      = [];
							if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
								$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
								if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
									$styles        = $kadence_blocks_css::$styles;
									$custom_styles = $kadence_blocks_css::$custom_styles;
								}
							}
							ob_start();
							wp_print_styles();
							$output = '';
							if ( ! empty( $styles ) && is_array( $styles ) ) {
								foreach ( $styles as $sc_key => $value ) {
									$output .= $value;
								}
							}
							$custom_output = '';
							if ( ! empty( $custom_styles ) && is_array( $custom_styles ) ) {
								foreach ( $custom_styles as $c_key => $c_value ) {
									$custom_output .= $c_value;
								}
							}
							if ( ! empty( $output ) ) {
								wp_register_style( 'kbc-' . $pattern_id, false );
								wp_enqueue_style( 'kbc-' . $pattern_id );
								wp_add_inline_style( 'kbc-' . $pattern_id, $output );
								wp_print_styles( 'kbc-' . $pattern_id );
							}
							if ( ! empty( $custom_output ) ) {
								wp_register_style( 'kbcc-' . $pattern_id, false );
								wp_enqueue_style( 'kbcc-' . $pattern_id );
								wp_add_inline_style( 'kbcc-' . $pattern_id, $custom_output );
								wp_print_styles( 'kbcc-' . $pattern_id );
							}
							echo do_blocks( $page_single_row_html );
							$wp_scripts->print_scripts();
							$library[ $slug ]['rows'][ $key ]['pattern_html'] = ob_get_clean();
						}
					}
					$deepest_term = null;
					if ( get_the_terms( $post, 'page_categories' ) ) {
						$terms = wp_get_post_terms(
							get_the_ID(),
							'page_categories',
							[
								'orderby' => 'parent',
								'order'   => 'DESC',
							]
						);
						if ( $terms && ! is_wp_error( $terms ) ) {
							if ( is_array( $terms ) ) {
								$deepest_term = $terms[0];
							}
						}
					}
					if ( $deepest_term != null ) {
						// Fetch and print description
						$library[ $slug ]['description'] = wp_strip_all_tags( term_description( $deepest_term->term_id, 'page_categories' ) );
					} else {
						// No terms were found for the post
						$library[ $slug ]['description'] = $post->post_excerpt;
					}
					if ( ! has_post_thumbnail() ) {
						$library[ $slug ]['image']  = add_query_arg( $fallback_image_args, 'https://s0.wordpress.com/mshots/v1/' . rawurlencode( esc_url( get_permalink() ) ) );
						$library[ $slug ]['imageW'] = $fallback_image_args['w'];
						$library[ $slug ]['imageH'] = $fallback_image_args['h'];
					} else {
						$image                      = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'full' );
						$library[ $slug ]['image']  = $image[0];
						$library[ $slug ]['imageW'] = $image[1];
						$library[ $slug ]['imageH'] = $image[2];
					}
				}
			endwhile;
		endif;
		return $library;
	}
	
	/**
	 * Retrieves a collection of objects.
	 */
	public function get_template( $page_id = 1, $key = '', $read_only = false, $request_extras = [] ) {
		$page_object = get_post( $page_id );
		if ( ! $page_object || 'page_library' !== $page_object->post_type ) {
			return rest_ensure_response( new WP_Error( 'kadence_cloud_template_not_found', __( 'Template not found', 'kadence-cloud' ), [ 'status' => 404 ] ) );
		}
		$library = $this->get_single_template_array( $page_object, $read_only, $request_extras, $key );
		return rest_ensure_response( $library );
	}
	/**
	 * Process content for export
	 */
	private function process_content_for_export( $block, $cpt_blocks ) {
		switch ( $block['blockName'] ) {
			case 'kadence/advanced-form':
				if ( ! empty( $block['attrs']['id'] ) && ! in_array( $block['attrs']['id'], $this->processed_posts ) ) {
					$this->processed_posts[] = $block['attrs']['id'];
					$content                 = $this->prepare_post_for_export( $block['attrs']['id'] );
					if ( ! empty( $content ) ) {
						$cpt_blocks['kadence/advanced-form'][] = $content;
					}
				}
				break;
			case 'kadence/header':
				if ( ! empty( $block['attrs']['id'] ) && ! in_array( $block['attrs']['id'], $this->processed_posts ) ) {
					$this->processed_posts[] = $block['attrs']['id'];
					$content                 = $this->prepare_post_for_export( $block['attrs']['id'] );
					if ( ! empty( $content ) ) {
						$cpt_blocks['kadence/header'][] = $content;
					}
				}
				break;
			case 'kadence/navigation':
				if ( ! empty( $block['attrs']['id'] ) && ! in_array( $block['attrs']['id'], $this->processed_posts ) ) {
					$this->processed_posts[] = $block['attrs']['id'];
					$content                 = $this->prepare_post_for_export( $block['attrs']['id'] );
					if ( ! empty( $content ) ) {
						$cpt_blocks['kadence/navigation'][] = $content;
					}
				}
				break;
		}
		return $cpt_blocks;
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public function get_cpt_blocks( $content ) {
		$cpt_blocks = [];
		$blocks     = parse_blocks( $content );
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], $this->is_cpt_block, true ) ) {
				$cpt_blocks = $this->process_content_for_export( $block, $cpt_blocks );
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$cpt_blocks = $this->blocks_cycle_through( $block['innerBlocks'], $cpt_blocks );
			}
		}
		return $cpt_blocks;
	}
	/**
	 * Recursively get related posts
	 */
	private function get_inner_posts_recursive( $content, $inner_posts = [] ) {
		$blocks = parse_blocks( $content );

		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], $this->is_cpt_block ) && ! empty( $block['attrs']['id'] ) ) {
				$nested_id = $block['attrs']['id'];

				if ( ! in_array( $nested_id, $this->processed_posts ) ) {
					$nested_post = get_post( $nested_id );
					if ( $nested_post ) {
						$inner_posts[]           = $this->prepare_post_for_export( $nested_post );
						$this->processed_posts[] = $nested_id;
						$inner_posts             = $this->get_inner_posts_recursive( $nested_post->post_content, $inner_posts );
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $inner_block ) {
					$inner_posts = $this->get_inner_posts_recursive( serialize_blocks( [ $inner_block ] ) );
				}
			}
		}
		return $inner_posts;
	}
	/**
	 * Prepare post for export
	 */
	private function prepare_post_for_export( $post_id ) {
		if ( empty( $post_id ) ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		
		$post_data                = [
			'ID'           => $post->ID,
			'post_content' => $post->post_content,
			'post_title'   => $post->post_title,
			'post_excerpt' => $post->post_excerpt,
			'post_type'    => $post->post_type,
			'meta'         => [],
			'inner_posts'  => [],
		];
		$post_data['inner_posts'] = $this->get_inner_posts_recursive( $post->post_content );
		$meta                     = get_post_custom( $post->ID );
		foreach ( $meta as $key => $values ) {
			// Check if key starts with _kad.
			if ( strpos( $key, '_kad' ) === 0 ) {
				$post_data['meta'][ $key ] = array_map( 'maybe_unserialize', $values );
			}
		}

		return $post_data;
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public function blocks_cycle_through( $blocks, $cpt_blocks ) {
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], $this->is_cpt_block, true ) ) {
				$cpt_blocks = $this->process_content_for_export( $block, $cpt_blocks );
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$cpt_blocks = $this->blocks_cycle_through( $block['innerBlocks'], $cpt_blocks );
			}
		}
		return $cpt_blocks;
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public function get_single_template_array( $page_object, $read_only, $request_extras, $key ) {
		$library = [];
		add_filter(
			'single_product_archive_thumbnail_size',
			function ( $size ) {
				return 'full';
			} 
		);
		add_filter(
			'max_srcset_image_width',
			function ( $size ) {
				return 1;
			} 
		);
		if ( $page_object ) :
			global $wp_styles, $wp_scripts;
			$wp_styles  = new \WP_Styles();
			$wp_scripts = new \WP_Scripts();
			$slug       = 'pg-' . $page_object->ID;
			if ( $slug ) {
				$library['slug'] = $slug;
				$library['name'] = esc_attr( strip_tags( $page_object->post_title ) );

				if ( ! empty( $request_extras ) && is_array( $request_extras ) ) {
					foreach ( $request_extras as $key => $data ) {
						$library[ $key ] = $data;
					}
				}
				$post_extras = apply_filters( 'kadence_cloud_post_extra_args', [], $page_object, $request_extras );
				if ( ! empty( $post_extras ) && is_array( $post_extras ) ) {
					foreach ( $post_extras as $key => $data ) {
						$library[ $key ] = $data;
					}
				}
				if ( apply_filters( 'kadence_cloud_page_send_content', false, $page_object, $request_extras ) ) {
					$library['content'] = $page_object->post_content;
				}
				if ( apply_filters( 'kadence_cloud_page_use_acf', false ) ) {
					$rows = get_field( 'pattern', $page_object->ID );
				} else {
					$rows = get_post_meta( $page_object->ID, 'sections', true );
				}
				$page_rows = [];
				// Clear Kadence Blocks CSS.
				if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
					$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
					if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
						$kadence_blocks_css::$styles        = [];
						$kadence_blocks_css::$head_styles   = [];
						$kadence_blocks_css::$custom_styles = [];
					}
				}
				$page_rows_content = '';
				$page_has_events   = false;
				if ( $rows ) {
					foreach ( $rows as $key => $row ) {
						$pattern_id     = $row['selected_pattern'];
						$pattern_object = get_post( $pattern_id );
						if ( $pattern_object ) {
							$style                = ( isset( $row['color_style'] ) && ! empty( $row['color_style'] ) ) ? $row['color_style'] : 'light';
							$condition            = ( isset( $row['condition'] ) && ! empty( $row['condition'] ) ) ? $row['condition'] : 'general';
							$content              = $pattern_object->post_content;
							$context              = '';
							$page_single_row_html = '';
							$row_wrapper_class    = '';
							if ( class_exists( 'Tribe__Main' ) && has_term( 'tec', 'requires', $pattern_object ) ) {
								$page_has_events = true;
							}
							if ( isset( $row['context'] ) && is_object( $row['context'] ) && ! empty( $row['context']->slug ) ) {
								$context = $row['context']->slug;
							}
							$terms_array = [];
							if ( get_the_terms( $pattern_object, 'kadence-cloud-categories' ) ) {
								$terms = get_the_terms( $pattern_object, 'kadence-cloud-categories' );
								if ( is_array( $terms ) ) {
									foreach ( $terms as $t_key => $value ) {
										$terms_array[ $value->slug ] = $value->name;
									}
								}
							}
							$required_array = [];
							if ( get_the_terms( $pattern_object, 'requires' ) ) {
								$terms = get_the_terms( $pattern_object, 'requires' );
								if ( is_array( $terms ) ) {
									foreach ( $terms as $t_key => $value ) {
										$required_array[ $value->slug ] = $value->name;
									}
								}
							}
							$page_rows[ $key ] = [
								'pattern_id'        => $pattern_id,
								'pattern_style'     => $style,
								'pattern_context'   => $context,
								'pattern_category'  => $terms_array,
								'pattern_requires'  => $required_array,
								'pattern_condition' => $condition,
								'pattern_html'      => '',
							];
							if ( ! $read_only ) {
								$cpt_blocks                              = $this->get_cpt_blocks( $content );
								$page_rows[ $key ]['pattern_content']    = $content;
								$page_rows[ $key ]['pattern_cpt_blocks'] = $cpt_blocks;
							}
							if ( $style === 'dark' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'dark' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-dark-page-section';
							} elseif ( $style === 'highlight' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'highlight' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-highlight-page-section';
							}
							if ( ! empty( $condition ) && 'general' !== $condition ) {
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-page-conditional-' . $condition;
							}
							$pattern_object->post_content = $content;
							if ( class_exists( 'Kadence_Blocks_Frontend' ) ) {
								$kadence_blocks = \Kadence_Blocks_Frontend::get_instance();
								if ( method_exists( $kadence_blocks, 'frontend_build_css' ) ) {
									$kadence_blocks->frontend_build_css( $pattern_object );
								}
								if ( class_exists( 'Kadence_Blocks_Pro_Frontend' ) ) {
									$kadence_blocks_pro = \Kadence_Blocks_Pro_Frontend::get_instance();
									if ( method_exists( $kadence_blocks_pro, 'frontend_build_css' ) ) {
										$kadence_blocks_pro->frontend_build_css( $pattern_object );
									}
								}
							}
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '<div class="' . $row_wrapper_class . '">';
							}
							$page_single_row_html .= $content;
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '</div>';
							}
							$page_rows_content .= $page_single_row_html;
						}
					}
				}
				if ( apply_filters( 'kadence_cloud_post_send_full_page_html_content', false, $page_object, $request_extras ) ) {
					if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
						$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
						if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
							$kadence_blocks_css->frontend_block_css();
						}
					}
					ob_start();
						wp_print_styles();
						echo do_blocks( $page_rows_content );
						$wp_scripts->print_scripts();
						$page_rows_html   = ob_get_clean();
					$library['rows_html'] = $page_rows_html;
				}
				$library['rows'] = $page_rows;
				if ( $rows && apply_filters( 'kadence_cloud_page_single_send_html_content', $read_only, $page_object, $request_extras ) ) {
					$wp_styles  = new \WP_Styles();
					$wp_scripts = new \WP_Scripts();
					foreach ( $rows as $key => $row ) {
						if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
							$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
							if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
								$kadence_blocks_css::$styles        = [];
								$kadence_blocks_css::$head_styles   = [];
								$kadence_blocks_css::$custom_styles = [];
							}
						}
						$pattern_id  = $row['selected_pattern'];
						$single_post = get_post( $pattern_id );
						if ( $single_post ) {
							$style                = ( isset( $row['color_style'] ) && ! empty( $row['color_style'] ) ) ? $row['color_style'] : 'light';
							$condition            = ( isset( $row['condition'] ) && ! empty( $row['condition'] ) ) ? $row['condition'] : 'general';
							$content              = $single_post->post_content;
							$page_single_row_html = '';
							$row_wrapper_class    = '';
							if ( $style === 'dark' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'dark' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-dark-page-section';
							} elseif ( $style === 'highlight' ) {
								$content            = Cloud_Pages::row_patterns_custom_switch_color( $content, 'highlight' );
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-highlight-page-section';
							}
							if ( ! empty( $condition ) && 'general' !== $condition ) {
								$row_wrapper_class .= ( ! empty( $row_wrapper_class ) ? ' ' : '' ) . 'kb-blocks-page-conditional-' . $condition;
							}
							$single_post->post_content = $content;
							add_filter( 'kadence_blocks_render_inline_css', '__return_false' );
							if ( class_exists( 'Kadence_Blocks_Frontend' ) ) {
								$kadence_blocks = \Kadence_Blocks_Frontend::get_instance();
								if ( method_exists( $kadence_blocks, 'frontend_build_css' ) ) {
									$kadence_blocks->frontend_build_css( $single_post );
								}
								if ( class_exists( 'Kadence_Blocks_Pro_Frontend' ) ) {
									$kadence_blocks_pro = \Kadence_Blocks_Pro_Frontend::get_instance();
									if ( method_exists( $kadence_blocks_pro, 'frontend_build_css' ) ) {
										$kadence_blocks_pro->frontend_build_css( $single_post );
									}
								}
							}
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '<div class="' . $row_wrapper_class . '">';
							}
							$page_single_row_html .= $content;
							if ( ! empty( $row_wrapper_class ) ) {
								$page_single_row_html .= '</div>';
							}
							$page_rows_content .= $page_single_row_html;
							$styles             = [];
							$custom_styles      = [];
							if ( class_exists( 'Kadence_Blocks_CSS' ) ) {
								$kadence_blocks_css = \Kadence_Blocks_CSS::get_instance();
								if ( method_exists( $kadence_blocks_css, 'frontend_block_css' ) ) {
									$styles        = $kadence_blocks_css::$styles;
									$custom_styles = $kadence_blocks_css::$custom_styles;
								}
							}
							ob_start();
							wp_print_styles();
							$output = '';
							if ( ! empty( $styles ) && is_array( $styles ) ) {
								foreach ( $styles as $sc_key => $value ) {
									$output .= $value;
								}
							}
							$custom_output = '';
							if ( ! empty( $custom_styles ) && is_array( $custom_styles ) ) {
								foreach ( $custom_styles as $c_key => $c_value ) {
									$custom_output .= $c_value;
								}
							}
							if ( ! empty( $output ) ) {
								wp_register_style( 'kbc-' . $pattern_id, false );
								wp_enqueue_style( 'kbc-' . $pattern_id );
								wp_add_inline_style( 'kbc-' . $pattern_id, $output );
								wp_print_styles( 'kbc-' . $pattern_id );
							}
							if ( ! empty( $custom_output ) ) {
								wp_register_style( 'kbcc-' . $pattern_id, false );
								wp_enqueue_style( 'kbcc-' . $pattern_id );
								wp_add_inline_style( 'kbcc-' . $pattern_id, $custom_output );
								wp_print_styles( 'kbcc-' . $pattern_id );
							}
							echo do_blocks( $page_single_row_html );
							$wp_scripts->print_scripts();
							$library['rows'][ $key ]['pattern_html'] = ob_get_clean();
						}
					}
				}
			}
		endif;
		return $library;
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public function get_templates( $post_per_page = -1, $page_number = 1, $key = '', $request_extras = [] ) {
		$args     = [
			'post_type'      => 'page_library',
			'post_status'    => 'publish',
			'posts_per_page' => $post_per_page,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'offset'         => ( 1 < $page_number && -1 !== $post_per_page ? ( $page_number * $post_per_page ) : 0 ),
		];
		$settings = json_decode( get_option( 'kadence_cloud' ), true );
		if ( isset( $settings['access_keys'] ) && ! empty( $settings['access_keys'] ) && is_array( $settings['access_keys'] ) ) {
			if ( isset( $settings['access_keys'][0] ) && is_array( $settings['access_keys'][0] ) ) {
				foreach ( $settings['access_keys'] as $the_key => $the_keys ) {
					if ( $the_keys['key'] === $key ) {
						if ( isset( $the_keys['collections'] ) && ! empty( $the_keys['collections'] ) ) {
							$args['tax_query'] = [
								[
									'taxonomy' => 'kadence-cloud-collections',
									'field'    => 'id',
									'terms'    => explode( ',', $the_keys['collections'] ),
								],
							];
						}
						break;
					}
				}
			}
		}
		$args      = apply_filters( 'kadence_cloud_template_query_args', $args, $key, $request_extras );
		$templates = new WP_Query( $args );
		$library   = $this->get_template_array( $templates, $request_extras, $key );

		wp_send_json( $library );
	}
	/**
	 * Retrieves the query params for the search results collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params[ self::PROP_KEY ] = [
			'description' => __( 'The request key.', 'kadence-cloud' ),
			'type'        => 'string',
		];

		$query_params[ self::PROP_SITE ] = [
			'description' => __( 'The request website.', 'kadence-cloud' ),
			'type'        => 'string',
		];

		$query_params[ self::PROP_RETURN_COUNT ] = [
			'description' => __( 'Items to return.', 'kadence-cloud' ),
			'type'        => 'string',
		];

		$query_params[ self::PROP_RETURN_PAGE ] = [
			'description' => __( 'The Page to return.', 'kadence-cloud' ),
			'type'        => 'string',
		];

		$query_params[ self::PROP_RETURN_PAGE_NUMBER ] = [
			'description' => __( 'The Page to return.', 'kadence-cloud' ),
			'type'        => 'string',
		];
		$query_params[ self::PROP_READ_ONLY ]          = [
			'description' => __( 'Return the item html content only.', 'kadence-cloud' ),
			'type'        => 'boolean',
			'default'     => false,
		];

		return $query_params;
	}
}
