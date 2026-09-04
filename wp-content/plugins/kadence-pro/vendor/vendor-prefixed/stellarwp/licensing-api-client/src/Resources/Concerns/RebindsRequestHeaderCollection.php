<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Concerns;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\RequestHeaderCollection;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsLedgerResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsPoolsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsQuotasResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\EntitlementsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\LicensesResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\ProductsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\TokensResource;

/**
 * Provides immutable request-header rebinding for resource views.
 *
 * @mixin CreditsLedgerResource
 * @mixin CreditsPoolsResource
 * @mixin CreditsQuotasResource
 * @mixin CreditsResource
 * @mixin EntitlementsResource
 * @mixin LicensesResource
 * @mixin ProductsResource
 * @mixin TokensResource
 */
trait RebindsRequestHeaderCollection
{
	public function withRequestHeaderCollection(RequestHeaderCollection $requestHeaderCollection): self {
		if ($this->requestHeaderCollection === $requestHeaderCollection) {
			return $this;
		}

		return $this->rebindWithRequestHeaderCollection($requestHeaderCollection);
	}

	abstract protected function rebindWithRequestHeaderCollection(RequestHeaderCollection $requestHeaderCollection): self;
}
