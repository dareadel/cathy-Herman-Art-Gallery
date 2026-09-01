<?php
/**
 * Handles all functionality related to the Marquee Block
 *
 * @since 1.0.0
 *
 * @package KadenceWP\CreativeKit
 */

declare( strict_types=1 );

namespace KadenceWP\CreativeKit\Blocks;

/**
 * Handles all functionality related to the Marquee Block.
 *
 * @since 1.0.0
 *
 * @package KadenceWP\CreativeKit
 */
class Marquee_Block extends Abstract_Block {

	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Block name within the namespace.
	 *
	 * @var string
	 */
	protected $block_name = 'marquee';

	/**
	 * If true, register a style and have it available
	 *
	 * @var boolean
	 */
	protected $has_style = true;

	/**
	 * If true, register a script and have it available
	 *
	 * @var boolean
	 */
	protected $has_script = true;

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
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}



	/**
	 * Build HTML for dynamic blocks
	 *
	 * @param array    $attributes The attributes.
	 * @param string   $unique_id The unique Id.
	 * @param string   $content The content.
	 * @param WP_Block $block_instance The instance of the WP_Block class that represents the block being rendered.
	 *
	 * @return mixed
	 */
	public function build_html( $attributes, $unique_id, $content, $block_instance ) {

		// Check if content already contains a complete marquee structure
		// This prevents nested marquee structures when using alignment classes (alignfull, alignwide)
		// Look for the specific structure that indicates a complete marquee
		if ( strpos( $content, 'kb-advanced-marquee' ) !== false && 
			 strpos( $content, 'kb-blocks-advanced-marquee-init' ) !== false &&
			 strpos( $content, 'splide__track' ) !== false &&
			 strpos( $content, 'splide__list' ) !== false ) {
			// Content already has a complete marquee structure, just return it as is
			return $content;
		}
		// Get marquee attributes with defaults
		$orientation = isset( $attributes['orientation'] ) ? $attributes['orientation'] : 'horizontal';
		$direction = isset( $attributes['direction'] ) ? $attributes['direction'] : 'left';
		$speed = isset( $attributes['speed'] ) ? $attributes['speed'] : 25;
		$pause_on_hover = isset( $attributes['pauseOnHover'] ) ? $attributes['pauseOnHover'] : false;
		$show_play_pause = isset( $attributes['showPlayPause'] ) ? $attributes['showPlayPause'] : true;
		$gap = isset( $attributes['gap'] ) ? $attributes['gap'] : 20;
		$gap_tablet = isset( $attributes['gapTablet'] ) ? $attributes['gapTablet'] : $gap;
		$gap_mobile = isset( $attributes['gapMobile'] ) ? $attributes['gapMobile'] : $gap_tablet;
		$gap_unit = isset( $attributes['gapUnit'] ) ? $attributes['gapUnit'] : 'px';
        $transition = isset( $attributes['transition'] ) ? $attributes['transition'] : true;
        $transition_distance = isset( $attributes['transitionDistance'] ) ? $attributes['transitionDistance'] : array( 100, 60, 40 );
        // Handle backward compatibility for old single value
        if ( ! is_array( $transition_distance ) ) {
            $transition_distance = array( $transition_distance, round( $transition_distance * 0.6 ), round( $transition_distance * 0.4 ) );
        }
        $transition_distance_desktop = isset( $transition_distance[0] ) && is_numeric( $transition_distance[0] ) ? absint( $transition_distance[0] ) : 100;
        $transition_distance_tablet  = isset( $transition_distance[1] ) && is_numeric( $transition_distance[1] ) ? absint( $transition_distance[1] ) : $transition_distance_desktop;
        $transition_distance_mobile  = isset( $transition_distance[2] ) && is_numeric( $transition_distance[2] ) ? absint( $transition_distance[2] ) : $transition_distance_tablet;
		$background = isset( $attributes['background'] ) ? $attributes['background'] : '';
		$background_type = isset( $attributes['backgroundType'] ) ? $attributes['backgroundType'] : 'normal';
		$background_gradient = isset( $attributes['backgroundGradient'] ) ? $attributes['backgroundGradient'] : '';
		$max_width = isset( $attributes['maxWidth'] ) ? $attributes['maxWidth'] : array( '', '', '' );
		$max_width_unit = isset( $attributes['maxWidthUnit'] ) ? $attributes['maxWidthUnit'] : 'px';

		// Build inline styles for background
		$container_styles = '';
		if ( $background_type === 'normal' && $background ) {
			// Use CSS class to properly render colors (handles palette colors)
			$css_class = Minified_CSS::get_instance();
			$rendered_color = $css_class->render_color( $background );
			$container_styles = 'background-color:' . esc_attr( $rendered_color ) . ';';
		} else if ( $background_type === 'gradient' && $background_gradient ) {
			$container_styles = 'background:' . esc_attr( $background_gradient ) . ';';
		}

		// Convert boolean transition to string for CSS class and data attribute
		$transition_type = $transition ? 'fade' : 'none';


		// Use container styles without background color for masks (now using CSS masks)
		$combined_styles = $container_styles;

		// Create wrapper classes
		$outer_classes = array(
			'kb-advanced-marquee',
			'kb-advanced-marquee-' . $unique_id,
			'kb-advanced-marquee--' . $orientation,
		);
		$wrapper_args = array(
			'class' => implode( ' ', $outer_classes ),
		);
		$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );

		// Create play/pause button HTML conditionally
		$play_pause_button = '';
		if ( $show_play_pause ) {
			$play_pause_button = '<button class="splide__toggle" type="button" aria-label="Toggle autoplay"><svg class="splide__toggle__play" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8 5v14l11-7z"/></svg><svg class="splide__toggle__pause" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg></button>';
		}
		
		$marquee_data_attributes = array(
			// Only runtime attributes consumed by JS
			'data-marquee-orientation'    => $orientation,
			'data-marquee-direction'      => $direction,
			'data-marquee-speed'          => max( 1, $speed / 4 ),
			'data-marquee-pause-on-hover' => $pause_on_hover ? 'true' : 'false',
		);

		$marquee_attribute_pairs = array();
		if ( $combined_styles ) {
			$marquee_attribute_pairs['style'] = $combined_styles;
		}
		foreach ( $marquee_data_attributes as $attribute_name => $attribute_value ) {
			$marquee_attribute_pairs[ $attribute_name ] = $attribute_value;
		}

		$marquee_attribute_parts = array();
		foreach ( $marquee_attribute_pairs as $attribute_name => $attribute_value ) {
			$value = 'style' === $attribute_name ? $attribute_value : esc_attr( $attribute_value );
			$marquee_attribute_parts[] = "{$attribute_name}=\"{$value}\"";
		}
		$marquee_attribute_string = implode( ' ', $marquee_attribute_parts );

		// Create the marquee structure
		$orientation_class = 'vertical' === $orientation ? 'splide--ttb' : 'splide--ltr';

		$content = sprintf(
			'<div %1$s><div class="kb-advanced-marquee-inner-contain"><div class="kb-blocks-advanced-marquee kb-marquee-container"><div class="kb-blocks-advanced-marquee-init kb-marquee-loading kb-blocks-marquee splide splide--loop %6$s kb-marquee-transition-%2$s" %3$s><div class="splide__slider"><div class="splide__track"><ul class="splide__list">%4$s</ul></div></div>%5$s</div></div></div></div>',
			$wrapper_attributes,
			esc_attr( $transition_type ),
			$marquee_attribute_string,
			$content,
			$play_pause_button,
			esc_attr( $orientation_class )
		);

		return $content;
	}

	/**
	 * Builds CSS for block.
	 *
	 * @param array $attributes the blocks attributes.
	 * @param Kadence_Blocks_CSS $css the css class for blocks.
	 * @param string $unique_id the blocks attr ID.
	 * @param string $unique_style_id the blocks alternate ID for queries.
	 */
	public function build_css( $attributes, $css, $unique_id, $unique_style_id ) {

		$this->enqueue_script( 'kadence-creative-kit-' . $this->block_name );

		$css->set_style_id( 'kb-' . $this->block_name . $unique_style_id );

		// Get attributes with defaults
		$direction = isset( $attributes['direction'] ) ? $attributes['direction'] : 'left';
		$orientation = isset( $attributes['orientation'] ) ? $attributes['orientation'] : 'horizontal';
		$height = isset( $attributes['height'] ) ? $attributes['height'] : array( '', '', '' );
		$height_unit = isset( $attributes['heightUnit'] ) ? $attributes['heightUnit'] : 'px';
		$item_width = isset( $attributes['itemWidth'] ) ? $attributes['itemWidth'] : array( '100', '100', '100' );
		$item_width_unit = isset( $attributes['itemWidthUnit'] ) ? $attributes['itemWidthUnit'] : '%';
		$gap = isset( $attributes['gap'] ) ? $attributes['gap'] : 30;
		$gap_tablet = isset( $attributes['gapTablet'] ) ? $attributes['gapTablet'] : $gap;
		$gap_mobile = isset( $attributes['gapMobile'] ) ? $attributes['gapMobile'] : $gap_tablet;
		$gap_unit = isset( $attributes['gapUnit'] ) ? $attributes['gapUnit'] : 'px';
		$margin_unit = isset( $attributes['marginUnit'] ) ? $attributes['marginUnit'] : 'px';
		$padding_unit = isset( $attributes['paddingUnit'] ) ? $attributes['paddingUnit'] : 'px';
		$max_width = isset( $attributes['maxWidth'] ) ? $attributes['maxWidth'] : array( '', '', '' );
		$max_width_unit = isset( $attributes['maxWidthUnit'] ) ? $attributes['maxWidthUnit'] : 'px';
		$border_radius = isset( $attributes['borderRadius'] ) ? $attributes['borderRadius'] : array( '', '', '', '' );
		$tablet_border_radius = isset( $attributes['tabletBorderRadius'] ) ? $attributes['tabletBorderRadius'] : array( '', '', '', '' );
		$mobile_border_radius = isset( $attributes['mobileBorderRadius'] ) ? $attributes['mobileBorderRadius'] : array( '', '', '', '' );
		$border_radius_unit = isset( $attributes['borderRadiusUnit'] ) ? $attributes['borderRadiusUnit'] : 'px';
		$transition_distance = isset( $attributes['transitionDistance'] ) ? $attributes['transitionDistance'] : array( 100, 60, 40 );
		// Handle backward compatibility for old single value
		if ( ! is_array( $transition_distance ) ) {
			$transition_distance = array( $transition_distance, round( $transition_distance * 0.6 ), round( $transition_distance * 0.4 ) );
		}
		$transition_distance_desktop = isset( $transition_distance[0] ) ? (int) $transition_distance[0] : 100;
		$transition_distance_tablet = isset( $transition_distance[1] ) ? (int) $transition_distance[1] : 60;
		$transition_distance_mobile = isset( $transition_distance[2] ) ? (int) $transition_distance[2] : 40;
		$default_tablet_vertical_height = 'var(--kb-marquee-container-height, 250px) !important';
		$default_tablet_vertical_max_height = 'var(--kb-marquee-max-height, var(--kb-marquee-container-height, 400px)) !important';
		$default_mobile_vertical_height = 'var(--kb-marquee-container-height, 200px) !important';
		$default_mobile_vertical_max_height = 'var(--kb-marquee-max-height, var(--kb-marquee-container-height, 300px)) !important';

		// Set gap CSS custom properties
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
		$css->add_property( '--kb-marquee-gap', $gap . $gap_unit );
		
		if ( isset( $attributes['gapTablet'] ) && is_numeric( $attributes['gapTablet'] ) ) {
			$css->set_media_state( 'tablet' );
			$css->add_property( '--kb-marquee-gap', $gap_tablet . $gap_unit );
		}
		
		if ( isset( $attributes['gapMobile'] ) && is_numeric( $attributes['gapMobile'] ) ) {
			$css->set_media_state( 'mobile' );
			$css->add_property( '--kb-marquee-gap', $gap_mobile . $gap_unit );
		}
		$css->set_media_state( 'desktop' );

		// Responsive transition distance so masks scale with breakpoint settings
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init' );
		$css->add_property( '--kb-marquee-transition-distance', $transition_distance_desktop . 'px' );
		$css->set_media_state( 'tablet' );
		$css->add_property( '--kb-marquee-transition-distance', $transition_distance_tablet . 'px' );
		$css->set_media_state( 'mobile' );
		$css->add_property( '--kb-marquee-transition-distance', $transition_distance_mobile . 'px' );
		$css->set_media_state( 'desktop' );

		// Default vertical marquee containment when custom heights aren't provided
		// Ensure a sensible desktop height so vertical marquees render without JS
		$css->set_media_state( 'desktop' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init[data-marquee-orientation="vertical"]' );
		$css->add_property( 'height', 'var(--kb-marquee-container-height, 300px)' );
		$css->add_property( 'max-height', 'var(--kb-marquee-max-height, var(--kb-marquee-container-height, 500px))' );
		$css->set_media_state( 'tablet' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init[data-marquee-orientation="vertical"]' );
		$css->add_property( 'height', $default_tablet_vertical_height );
		$css->add_property( 'max-height', $default_tablet_vertical_max_height );
		$css->set_media_state( 'mobile' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init[data-marquee-orientation="vertical"]' );
		$css->add_property( 'height', $default_mobile_vertical_height );
		$css->add_property( 'max-height', $default_mobile_vertical_max_height );
		$css->set_media_state( 'desktop' );

		// Responsive max-width data attribute support using dynamic breakpoints
		$css->set_media_state( 'tablet' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . '[data-marquee-max-width-tablet]' );
		$css->add_property( 'max-width', 'var(--kb-marquee-max-width-tablet)' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . '[data-marquee-max-width-tablet] .kb-advanced-marquee-inner-contain' );
		$css->add_property( 'max-width', 'var(--kb-marquee-max-width-tablet)' );
		$css->set_media_state( 'mobile' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . '[data-marquee-max-width-mobile]' );
		$css->add_property( 'max-width', 'var(--kb-marquee-max-width-mobile)' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . '[data-marquee-max-width-mobile] .kb-advanced-marquee-inner-contain' );
		$css->add_property( 'max-width', 'var(--kb-marquee-max-width-mobile)' );
		$css->set_media_state( 'desktop' );

		// Background handling
		$background_type = ! empty( $attributes['backgroundType'] ) ? $attributes['backgroundType'] : 'normal';
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init' );
		
		switch ( $background_type ) {
			case 'normal':
				if ( ! empty( $attributes['background'] ) ) {
					$css->add_property( 'background-color', $css->render_color( $attributes['background'] ) );
				}
				break;
			case 'gradient':
				if ( ! empty( $attributes['backgroundGradient'] ) ) {
					$css->add_property( 'background-color', 'transparent' );
					$css->add_property( 'background-image', $attributes['backgroundGradient'] );
				}
				break;
		}

		// Height styles (apply to container only for vertical orientation)
		if ( 'vertical' === $orientation ) {
			if ( ! empty( $height[0] ) ) {
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init' );
				$css->add_property( 'height', $height[0] . $height_unit );
			}
			if ( ! empty( $height[1] ) ) {
				$css->set_media_state( 'tablet' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init' );
				$css->add_property( 'height', $height[1] . $height_unit );
			}
			if ( ! empty( $height[2] ) ) {
				$css->set_media_state( 'mobile' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init' );
				$css->add_property( 'height', $height[2] . $height_unit );
			}
			$css->set_media_state( 'desktop' );
		}

		// Item dimension custom properties for marquee items
		if ( 'horizontal' === $orientation ) {
			if ( ! empty( $height[0] ) ) {
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-item-height', $height[0] . $height_unit );
			}
			if ( ! empty( $height[1] ) ) {
				$css->set_media_state( 'tablet' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-item-height', $height[1] . $height_unit );
			}
			if ( ! empty( $height[2] ) ) {
				$css->set_media_state( 'mobile' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-item-height', $height[2] . $height_unit );
			}
			$css->set_media_state( 'desktop' );
		}

		if ( 'vertical' === $orientation ) {
			if ( ! empty( $height[0] ) ) {
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-container-height', $height[0] . $height_unit );
			}
			if ( ! empty( $height[1] ) ) {
				$css->set_media_state( 'tablet' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-container-height', $height[1] . $height_unit );
			}
			if ( ! empty( $height[2] ) ) {
				$css->set_media_state( 'mobile' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-container-height', $height[2] . $height_unit );
			}
			$css->set_media_state( 'desktop' );

			if ( ! empty( $item_width[0] ) ) {
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-item-width', $item_width[0] . $item_width_unit );
			}
			if ( ! empty( $item_width[1] ) ) {
				$css->set_media_state( 'tablet' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-item-width', $item_width[1] . $item_width_unit );
			}
			if ( ! empty( $item_width[2] ) ) {
				$css->set_media_state( 'mobile' );
				$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
				$css->add_property( '--kb-marquee-item-width', $item_width[2] . $item_width_unit );
			}
			$css->set_media_state( 'desktop' );
		}

		// Max width styles
		if ( ! empty( $max_width[0] ) ) {
			$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
			$css->add_property( 'max-width', $max_width[0] . $max_width_unit );
		}
		if ( ! empty( $max_width[1] ) ) {
			$css->set_media_state( 'tablet' );
			$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
			$css->add_property( 'max-width', $max_width[1] . $max_width_unit );
		}
		if ( ! empty( $max_width[2] ) ) {
			$css->set_media_state( 'mobile' );
			$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
			$css->add_property( 'max-width', $max_width[2] . $max_width_unit );
		}
		$css->set_media_state( 'desktop' );

		// Margin and padding
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
		$css->render_measure_output( $attributes, 'margin', 'margin' );

		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-blocks-advanced-marquee-init' );
		$css->render_measure_output( $attributes, 'padding', 'padding' );
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .splide__toggle' );
		$css->render_measure_output( $attributes, 'pauseButtonPadding', 'padding' );
		if ( ! empty( $attributes['pauseButtonBackground'] ) ) {
			$css->add_property(
				'background-color',
				$css->render_color( $attributes['pauseButtonBackground'] )
			);
		}
		if ( ! empty( $attributes['pauseButtonColor'] ) ) {
			$color_value = $css->render_color( $attributes['pauseButtonColor'] );
			$css->add_property( 'color', $color_value );
			$css->set_selector(
				'.kb-advanced-marquee-' . $unique_id . ' .splide__toggle svg, .kb-advanced-marquee-' . $unique_id . ' .splide__toggle svg path'
			);
			$css->add_property( 'fill', $color_value );
			$css->add_property( 'stroke', $color_value );
			$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .splide__toggle' );
		}
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id );

		// Z-index
		if ( isset( $attributes['zIndex'] ) && is_numeric( $attributes['zIndex'] ) ) {
			$css->set_selector( '.kb-advanced-marquee-' . $unique_id );
			$css->add_property( 'position', 'relative' );
			$css->add_property( 'z-index', (int) $attributes['zIndex'] );
		}

		// Border radius - apply to all marquee items
		$css->set_selector( '.kb-advanced-marquee-' . $unique_id . ' .kb-marquee-item-inner' );
		$css->render_measure_output( $attributes, 'borderRadius', 'border-radius' );
		if ( ! empty( $border_radius[0] ) || ! empty( $border_radius[1] ) || ! empty( $border_radius[2] ) || ! empty( $border_radius[3] ) ) {
			$css->add_property( 'overflow', 'hidden' );
		}

		return $css->css_output();
	}

	/**
	 * Registers scripts and styles.
	 */
	public function register_scripts() {
		// Call parent to register styles
		parent::register_scripts();

		// If in the backend, bail out.
		if ( is_admin() ) {
			return;
		}
		if ( apply_filters( 'kadence_blocks_check_if_rest', false ) && function_exists( 'kadence_blocks_is_rest' ) && kadence_blocks_is_rest() ) {
			return;
		}

		// Register GSAP if not already registered (for marquee block).
		if ( ! wp_script_is( 'gsap', 'registered' ) ) {
			$gsap_src = apply_filters(
				'kadence_creative_kit_gsap_src',
				KADENCE_CREATIVE_KIT_URL . 'assets/js/gsap.min.js',
			);
			wp_register_script(
				'gsap',
				$gsap_src,
				array(),
				'3.12.5',
				true
			);
		}

		// Register the main marquee script (GSAP-based).
		wp_register_script(
			'kadence-creative-kit-' . $this->block_name,
			KADENCE_CREATIVE_KIT_URL . 'assets/js/kb-marquee-init.min.js',
			array( 'gsap' ),
			KADENCE_CREATIVE_KIT_VERSION,
			true
		);
	}

}
