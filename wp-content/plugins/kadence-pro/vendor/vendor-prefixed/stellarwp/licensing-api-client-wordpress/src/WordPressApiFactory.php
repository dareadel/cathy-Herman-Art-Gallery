<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClientWordPress;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Api;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\ApiBuilder;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Config;
use KadenceWP\KadencePro\Psr\Http\Client\ClientInterface;
use KadenceWP\KadencePro\Psr\Http\Message\RequestFactoryInterface;
use KadenceWP\KadencePro\Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds the core licensing API client with WordPress-compatible transport dependencies.
 *
 * @note Use this when you don't have a DI Container to build out the dependency tree.
 */
final class WordPressApiFactory
{
	private ClientInterface $httpClient;

	private RequestFactoryInterface $requestFactory;

	private StreamFactoryInterface $streamFactory;

	public function __construct(
		ClientInterface $httpClient,
		RequestFactoryInterface $requestFactory,
		StreamFactoryInterface $streamFactory
	) {
		$this->httpClient     = $httpClient;
		$this->requestFactory = $requestFactory;
		$this->streamFactory  = $streamFactory;
	}

	public function make(Config $config): Api {
		return (new ApiBuilder(
			$this->httpClient,
			$this->requestFactory,
			$this->streamFactory,
			$config
		))->build();
	}
}
