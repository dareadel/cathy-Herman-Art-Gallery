<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare( strict_types=1 );

namespace KadenceWP\KadencePro\LiquidWeb\Harbor\Http;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClientWordPress\Http\WordPressHttpClient;
use KadenceWP\KadencePro\Nyholm\Psr7\Factory\Psr17Factory;
use KadenceWP\KadencePro\Psr\Http\Client\ClientInterface;
use KadenceWP\KadencePro\Psr\Http\Message\RequestFactoryInterface;
use KadenceWP\KadencePro\Psr\Http\Message\StreamFactoryInterface;
use KadenceWP\KadencePro\LiquidWeb\Harbor\Contracts\Abstract_Provider;

/**
 * Registers shared PSR-17 HTTP message factories in the DI container.
 *
 * @since 1.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( WordPressHttpClient::class );
		$this->container->singleton( ClientInterface::class, WordPressHttpClient::class );
		$this->container->singleton( Psr17Factory::class );
		$this->container->singleton( RequestFactoryInterface::class, Psr17Factory::class );
		$this->container->singleton( StreamFactoryInterface::class, Psr17Factory::class );
	}
}
