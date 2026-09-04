<?php
/**
 * The main plugin class.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */

namespace KadenceWP\CloudPages;

use InvalidArgumentException;
use RuntimeException;
use KadenceWP\KadenceBlocks\StellarWP\ContainerContract\ContainerInterface as StellarContainerInterface;
use KadenceWP\CloudPages\lucatume\DI52\Container as DI52Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The primary class responsible for booting up the plugin.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CloudPages
 */
class Core {

	public const PLUGIN_FILE         = 'kadence_cloud_pages.plugin_file';

	/**
	 * The server path to the plugin's main file.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * The centralized container.
	 *
	 * @since 0.1.0
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * The singleton instance for the plugin.
	 *
	 * @var Core
	 */
	private static self $instance;

	/**
	 * An array of providers to register within the container.
	 *
	 * @since 0.1.0
	 *
	 * @var array<int,string>
	 */
	private $providers = [
		Admin\Provider::class,
		Posts\Provider::class,
	];
	/**
	 * @param  string    $plugin_file The full server path to the main plugin file.
	 * @param  Container $container The container instance.
	 */
	private function __construct(
		string $plugin_file,
		Container $container
	) {
		$this->plugin_file = $plugin_file;
		$this->container   = $container;
		$this->container->singleton( Container::class, $this->container );
		$this->container->singleton( StellarContainerInterface::class, $this->container );
		$this->container->singleton( ContainerInterface::class, $this->container );

		// Set container variables available to pre bootstrap providers.
		$this->container->setVar( self::PLUGIN_FILE, $this->plugin_file );
	}
	/**
	 * Get the singleton instance of our plugin.
	 *
	 * @param  string|null    $plugin_file  The full server path to the main plugin file.
	 * @param  Container|null $container    The container instance.
	 *
	 * @throws InvalidArgumentException If no existing instance and no plugin file or container is provided.
	 *
	 * @return Core
	 */
	public static function instance( ?string $plugin_file = null, ?Container $container = null ): Core {
		if ( ! isset( self::$instance ) ) {
			if ( ! $plugin_file ) {
				$plugin_file = realpath( KADENCE_CLOUD_PAGES_PATH . '/kadence-cloud-pages.php' );
			}

			if ( ! $container ) {
				$container = new Container();
			}

			self::$instance = new self( $plugin_file, $container );
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @action plugins_loaded
	 *
	 * @return void
	 */
	public function init(): void {

		// Register all providers.
		foreach ( $this->providers as $class ) {
			$this->container->get( $class )->register( $this->container );
		}
	}

	/**
	 * Returns the container instance.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Prevent wakeup.
	 *
	 * @throws RuntimeException When attempting to wake up this instance.
	 */
	public function __wakeup(): void {
		throw new RuntimeException( 'method not implemented' );
	}

	/**
	 * Prevent sleep.
	 *
	 * @throws RuntimeException When attempting to sleep this instance.
	 */
	public function __sleep(): array {
		throw new RuntimeException( 'method not implemented' );
	}

	/**
	 * Prevent cloning.
	 *
	 * @throws RuntimeException When attempting to clone this instance.
	 */
	private function __clone() {
		throw new RuntimeException( 'Cloning not allowed' );
	}
}
