<?php
/**
 * Handles all functionality related to the Marquee Item Block
 *
 * @since 1.0.0
 *
 * @package KadenceWP\CreativeKit
 */

declare( strict_types=1 );

namespace KadenceWP\CreativeKit\Blocks;

/**
 * Handles all functionality related to the Marquee Item Block.
 *
 * @since 1.0.0
 *
 * @package KadenceWP\CreativeKit
 */
class Marquee_Item_Block extends Abstract_Block {

	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Block determines if style needs to be loaded for block.
	 *
	 * @var string
	 */
	protected $has_style = false;

	/**
	 * Block name within the namespace.
	 *
	 * @var string
	 */
	protected $block_name = 'marquee-item';

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
		// Get marquee item attributes with defaults
		$background = isset( $attributes['background'] ) ? $attributes['background'] : '';
		$background_type = isset( $attributes['backgroundType'] ) ? $attributes['backgroundType'] : 'normal';
		$background_gradient = isset( $attributes['backgroundGradient'] ) ? $attributes['backgroundGradient'] : '';
		$desktop_margin = isset( $attributes['margin'] ) && is_array( $attributes['margin'] ) ? $attributes['margin'] : array( '', '', '', '' );
		$tablet_margin = isset( $attributes['tabletMargin'] ) && is_array( $attributes['tabletMargin'] ) ? $attributes['tabletMargin'] : array( '', '', '', '' );
		$mobile_margin = isset( $attributes['mobileMargin'] ) && is_array( $attributes['mobileMargin'] ) ? $attributes['mobileMargin'] : array( '', '', '', '' );
		$desktop_padding = isset( $attributes['padding'] ) && is_array( $attributes['padding'] ) ? $attributes['padding'] : array( '', '', '', '' );
		$tablet_padding = isset( $attributes['tabletPadding'] ) && is_array( $attributes['tabletPadding'] ) ? $attributes['tabletPadding'] : array( '', '', '', '' );
		$mobile_padding = isset( $attributes['mobilePadding'] ) && is_array( $attributes['mobilePadding'] ) ? $attributes['mobilePadding'] : array( '', '', '', '' );
		$margin_unit = isset( $attributes['marginUnit'] ) ? $attributes['marginUnit'] : 'px';
		$padding_unit = isset( $attributes['paddingUnit'] ) ? $attributes['paddingUnit'] : 'px';
		$className = isset( $attributes['className'] ) ? $attributes['className'] : '';

		// Build inline styles for the inner element to ensure background displays even before CSS loads.
		$inner_styles = '';
		if ( 'normal' === $background_type && ! empty( $background ) ) {
			$css_class = Minified_CSS::get_instance();
			$rendered_color = $css_class->render_color( $background );
			if ( ! empty( $rendered_color ) ) {
				$inner_styles .= 'background-color:' . esc_attr( $rendered_color ) . ';';
			}
		} elseif ( 'gradient' === $background_type && ! empty( $background_gradient ) ) {
			$inner_styles .= 'background:' . esc_attr( $background_gradient ) . ';';
		}

		// Create wrapper classes
		$classes = array(
			'wp-block-kadence-marquee-item',
			'kb-marquee-item',
			'kb-marquee-item-' . $unique_id,
			'splide__slide'
		);
		if ( $className ) {
			$classes[] = $className;
		}

		// Create the marquee item structure
		$margin_data = array(
			'desk'   => $desktop_margin,
			'tablet' => $tablet_margin,
			'mobile' => $mobile_margin,
		);
		$padding_data = array(
			'desk'   => $desktop_padding,
			'tablet' => $tablet_padding,
			'mobile' => $mobile_padding,
		);

		$content = sprintf(
			'<li class="%1$s"><div class="kb-advanced-marquee-item"><div class="kb-advanced-marquee-item-inner kb-marquee-item-inner"%3$s>%2$s</div></div></li>',
			esc_attr( implode( ' ', $classes ) ),
			$content,
			$inner_styles ? ' style="' . $inner_styles . '"' : ''
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

		$css->set_style_id( 'kb-' . $this->block_name . $unique_style_id );

		// Text color
		if ( ! empty( $attributes['textColor'] ) ) {
			$css->set_selector( '.kb-advanced-marquee .kb-marquee-item-' . $unique_id );
			$css->add_property( 'color', $css->render_color( $attributes['textColor'] ) );
		}

		// Link colors
		if ( ! empty( $attributes['linkColor'] ) ) {
			$css->set_selector( '.kb-advanced-marquee .kb-marquee-item-' . $unique_id . ' a' );
			$css->add_property( 'color', $css->render_color( $attributes['linkColor'] ) );
		}
		if ( ! empty( $attributes['linkHoverColor'] ) ) {
			$css->set_selector( '.kb-advanced-marquee .kb-marquee-item-' . $unique_id . ' a:hover' );
			$css->add_property( 'color', $css->render_color( $attributes['linkHoverColor'] ) );
		}

		// Background
		$background_type = ! empty( $attributes['backgroundType'] ) ? $attributes['backgroundType'] : 'normal';
		$css->set_selector( '.kb-advanced-marquee .kb-marquee-item-' . $unique_id . ' .kb-advanced-marquee-item-inner' );
		
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

		// Margin and padding
		$margin_unit = isset( $attributes['marginUnit'] ) ? $attributes['marginUnit'] : 'px';
		$padding_unit = isset( $attributes['paddingUnit'] ) ? $attributes['paddingUnit'] : 'px';

		$css->set_selector( '.kb-advanced-marquee .kb-marquee-item-' . $unique_id );
		$css->render_measure_output( $attributes, 'margin', 'margin' );
		$css->render_measure_output( $attributes, 'padding', 'padding' );


		return $css->css_output();
	}

	/**
	 * Registers scripts and styles.
	 */
	public function register_scripts() {
		// Call parent to register styles
		parent::register_scripts();
	}

}
