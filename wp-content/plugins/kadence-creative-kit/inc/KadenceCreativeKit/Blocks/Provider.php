<?php
/**
 * The provider hooking Admin class methods to WordPress events.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */

namespace KadenceWP\CreativeKit\Blocks;

use KadenceWP\CreativeKit\Contracts\Service_Provider;

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
class Provider extends Service_Provider {

	/**
	 * {@inheritdoc}
	 */
	public function register(): void {
		// Register the Blocks.
		if ( defined( 'KADENCE_CREATIVE_KIT_BETA' ) ) {
			add_action( 'init', $this->container->callback( Page_Wizard_Block::class, 'page_wizard_block' ) );
		}
		
		// Register Marquee blocks.
		$this->container->singleton( Marquee_Block::class );
		$this->container->singleton( Marquee_Item_Block::class );
		add_action( 'init', function() {
			$this->container->get( Marquee_Block::class );
			$this->container->get( Marquee_Item_Block::class );
		}, 5 );
		
		if ( ! defined( 'KBP_VERSION' ) ) {
			add_action( 'enqueue_block_editor_assets', $this->container->callback( AOS_Block_Addon::class, 'enqueue_early_filters' ), 1 );
			add_action( 'enqueue_block_editor_assets', $this->container->callback( AOS_Block_Addon::class, 'enqueue_aos_in_editor' ), 10 );
			add_action( 'wp_enqueue_scripts', $this->container->callback( AOS_Block_Addon::class, 'register_scripts' ), 10 );
			add_action( 'wp_enqueue_scripts', $this->container->callback( AOS_Block_Addon::class, 'frontend_inline_css' ), 80 );
		}

	}
}
