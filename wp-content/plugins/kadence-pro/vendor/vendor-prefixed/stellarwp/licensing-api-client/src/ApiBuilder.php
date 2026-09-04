<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\ApiVersion;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\AuthContext;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\AuthState;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\Factories\ApiUriFactory;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\Factories\ResponseExceptionFactory;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\JsonDecoder;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\RequestBuilder;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\RequestExecutor;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\RequestHeaderCollection;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsLedgerResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsPoolsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsQuotasResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\EntitlementsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\LicensesResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\ProductsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\TokensResource;
use KadenceWP\KadencePro\Psr\Http\Client\ClientInterface as HttpClient;
use KadenceWP\KadencePro\Psr\Http\Message\RequestFactoryInterface;
use KadenceWP\KadencePro\Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds a fully-wired API client from the transport dependencies.
 *
 * Use this if your application is not using a container to build dependencies.
 */
final class ApiBuilder
{
	private HttpClient $httpClient;

	private RequestFactoryInterface $requestFactory;

	private StreamFactoryInterface $streamFactory;

	private Config $config;

	public function __construct(
		HttpClient $httpClient,
		RequestFactoryInterface $requestFactory,
		StreamFactoryInterface $streamFactory,
		Config $config
	) {
		$this->httpClient     = $httpClient;
		$this->requestFactory = $requestFactory;
		$this->streamFactory  = $streamFactory;
		$this->config         = $config;
	}

	public function build(): Api {
		$authState               = new AuthState(new AuthContext(), $this->config->configuredToken);
		$requestHeaderCollection = new RequestHeaderCollection();
		$apiUriFactory           = new ApiUriFactory($this->config, ApiVersion::default());
		$requestExecutor         = $this->buildRequestExecutor();
		$creditsPools            = new CreditsPoolsResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection);
		$creditsQuotas           = new CreditsQuotasResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection);
		$creditsLedger           = new CreditsLedgerResource(
			$requestExecutor,
			$apiUriFactory,
			$authState,
			$requestHeaderCollection
		);

		return new Api(
			$authState,
			$requestHeaderCollection,
			new LicensesResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection),
			new ProductsResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection),
			new CreditsResource(
				$requestExecutor,
				$apiUriFactory,
				$authState,
				$requestHeaderCollection,
				$creditsPools,
				$creditsQuotas,
				$creditsLedger
			),
			new EntitlementsResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection),
			new TokensResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection)
		);
	}

	private function buildRequestExecutor(): RequestExecutor {
		$jsonDecoder = new JsonDecoder();

		return new RequestExecutor(
			$this->httpClient,
			new RequestBuilder(
				$this->requestFactory,
				$this->streamFactory
			),
			$jsonDecoder,
			new ResponseExceptionFactory($jsonDecoder)
		);
	}
}
