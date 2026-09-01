<?php
/**
 * Handles all functionality related to the Page Wizard Block
 *
 * @since 0.0.1
 *
 * @package KadenceWP\CreativeKit
 */

declare( strict_types=1 );

namespace KadenceWP\CreativeKit\Blocks;

/**
 * Handles all functionality related to the Page Wizard Block.
 *
 * @since 0.0.1
 *
 * @package KadenceWP\CreativeKit
 */
class Page_Wizard_Block {

	/**
	 * Register the Page Wizard Block.
	 *
	 * @return void
	 */
	public function page_wizard_block() {
		// Register the block.
        $plugin_asset_meta = $this->get_asset_file( 'build/blocks-page-wizard' );
        wp_register_script(
            'kadence-page-wizard-block',
            KADENCE_CREATIVE_KIT_URL . 'build/blocks-page-wizard.js',
            $plugin_asset_meta['dependencies'],
            $plugin_asset_meta['version']
        );
        wp_register_style(
            'kadence-page-wizard-block',
            KADENCE_CREATIVE_KIT_URL . 'build/blocks-page-wizard.css',
            [],
            $plugin_asset_meta['version']
        );
		register_block_type(
            'kadence/page-wizard',
			[
                'editor_script' => 'kadence-page-wizard-block',
                'editor_style'  => 'kadence-page-wizard-block',
				'render_callback' => [ $this, 'render_page_wizard' ],
			]
		);
	}

	/**
	 * Render Page wizard Block
	 *
	 * @param array    $attributes Blocks attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 */
	public function render_page_wizard( $attributes, $content, $block ) {
		return '';
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

}
