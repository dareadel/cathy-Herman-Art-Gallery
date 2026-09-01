<?php
/**
 * Handles all functionality related to the AOS_Block_Addon
 *
 * @since 0.0.1
 *
 * @package KadenceWP\CreativeKit
 */

declare( strict_types=1 );

namespace KadenceWP\CreativeKit\Blocks;

use function parse_blocks;
use function wp_enqueue_script;
/**
 * Handles all functionality related to the AOS_Block_Addon.
 *
 * @since 0.0.1
 *
 * @package KadenceWP\CreativeKit
 */
class AOS_Block_Addon {

	/**
     * Registers scripts and styles.
     */
    public function register_scripts() {
        // If in the backend, bail out.
        if ( is_admin() ) {
            return;
        }

        wp_register_style( 'kadence-creative-aos', KADENCE_CREATIVE_KIT_URL . 'assets/css/aos.min.css', array(), KADENCE_CREATIVE_KIT_VERSION );
        wp_register_script( 'kadence-creative-aos', KADENCE_CREATIVE_KIT_URL . 'assets/js/aos.min.js', array(), KADENCE_CREATIVE_KIT_VERSION, true );
		$configs = json_decode( get_option( 'kadence_blocks_config_blocks' ), true );
        wp_localize_script(
            'kadence-creative-aos',
            'kadence_aos_params',
            array(
                'offset'   => ( isset( $configs ) && isset( $configs['kadence/aos'] ) && isset( $configs['kadence/aos']['offset'] ) && ! empty( $configs['kadence/aos']['offset'] ) ? $configs['kadence/aos']['offset'] : 120 ),
                'duration' => ( isset( $configs ) && isset( $configs['kadence/aos'] ) && isset( $configs['kadence/aos']['duration'] ) && ! empty( $configs['kadence/aos']['duration'] ) ? $configs['kadence/aos']['duration'] : 400 ),
                'easing'   => ( isset( $configs ) && isset( $configs['kadence/aos'] ) && isset( $configs['kadence/aos']['ease'] ) ? $configs['kadence/aos']['ease'] : 'ease' ),
                'delay'    => ( isset( $configs ) && isset( $configs['kadence/aos'] ) && isset( $configs['kadence/aos']['delay'] ) ? $configs['kadence/aos']['delay'] : 0 ),
                'once'     => ( isset( $configs ) && isset( $configs['kadence/aos'] ) && isset( $configs['kadence/aos']['once'] ) ? $configs['kadence/aos']['once'] : false ),
            )
        );
	}
	/**
	 * Register the AOS_Block_Addon.
	 *
	 * @return void
	 */
	public function enqueue_early_filters() {
		if ( ! is_admin() ) {
			return;
		}
		$asset_meta = $this->get_asset_file( 'build/aos-early-filters' );
		wp_enqueue_script( 'kadence-creative-aos-early-filters-js', KADENCE_CREATIVE_KIT_URL . 'build/aos-early-filters.js', array_merge( $asset_meta['dependencies'], array( 'wp-blocks', 'wp-i18n', 'wp-element' ) ), $asset_meta['version'], true );
	}

	/**
	 * Register the AOS_Block_Addon.
	 *
	 * @return void
	 */
	public function enqueue_aos_in_editor() {
		if ( ! is_admin() ) {
			return;
		}
		$asset_meta = $this->get_asset_file( 'build/aos-editor' );
		wp_enqueue_script( 'kadence-creative-aos-editor-js', KADENCE_CREATIVE_KIT_URL . 'build/aos-editor.js', array_merge( $asset_meta['dependencies'], array( 'wp-blocks', 'wp-i18n', 'wp-element' ) ), $asset_meta['version'], true );
		wp_enqueue_style( 'kadence-creative-aos-editor-css', KADENCE_CREATIVE_KIT_URL . 'build/aos-editor.css', array( 'wp-edit-blocks' ), $asset_meta['version'] );

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
                'dependencies' => [ 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ],
                'version'      => KADENCE_CREATIVE_KIT_VERSION,
            );
    }
	 /**
     * Outputs extra css for blocks.
     */
    public function frontend_inline_css() {
        if ( function_exists( 'has_blocks' ) && has_blocks( get_the_ID() ) ) {
            global $post;
            if ( ! is_object( $post ) ) {
                return;
            }
            $this->frontend_enqueue_scripts( $post );
        }
    }
	 /**
     * Outputs extra css & js for AOS.
     *
     * @param $post_object object of WP_Post.
     */
    public function frontend_enqueue_scripts( $post_object ) {
        if ( ! is_object( $post_object ) ) {
            return;
        }
        if ( ! method_exists( $post_object, 'post_content' ) ) {
            $post_content = $post_object->post_content;
            $blocks = parse_blocks( $post_content );
            if ( ! is_array( $blocks ) || empty( $blocks ) ) {
                return;
            }
            foreach ( $blocks as $indexkey => $block ) {
                if ( ! is_object( $block ) && is_array( $block ) && isset( $block['blockName'] ) ) {
                    if ( ! empty( $block['attrs']['kadenceAnimation'] ) ) {
                        wp_enqueue_script( 'kadence-creative-aos' );
                        wp_enqueue_style( 'kadence-creative-aos' );
                    }
					if ( 'core/block' === $block['blockName'] ) {
                        if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
                            $blockattr = $block['attrs'];
                            if ( isset( $blockattr['ref'] ) ) {
                                $reusable_block = get_post( $blockattr['ref'] );
                                if ( $reusable_block && 'wp_block' === $reusable_block->post_type ) {
                                    $reuse_data_block = parse_blocks( $reusable_block->post_content );
                                    $this->blocks_cycle_through( $reuse_data_block );
                                }
                            }
                        }
                    }
					if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
                        $this->blocks_cycle_through( $block['innerBlocks'] );
                    }
				}
			}
		}
	}
	/**
     * Builds css for inner blocks
     *
     * @param array $inner_blocks array of inner blocks.
     */
    public function blocks_cycle_through( $inner_blocks ) {
        foreach ( $inner_blocks as $in_indexkey => $inner_block ) {
            if ( ! is_object( $inner_block ) && is_array( $inner_block ) && isset( $inner_block['blockName'] ) ) {
                if ( isset( $inner_block['blockName'] ) ) {
                    if ( ! empty( $inner_block['attrs']['kadenceAnimation'] ) ) {
						wp_enqueue_script( 'kadence-creative-aos' );
                        wp_enqueue_style( 'kadence-creative-aos' );
                    }
				}
			}
            if ( ! empty( $inner_block['innerBlocks'] ) && is_array( $inner_block['innerBlocks'] ) ) {
                $this->blocks_cycle_through( $inner_block['innerBlocks'] );
            }
		}
	}

}
