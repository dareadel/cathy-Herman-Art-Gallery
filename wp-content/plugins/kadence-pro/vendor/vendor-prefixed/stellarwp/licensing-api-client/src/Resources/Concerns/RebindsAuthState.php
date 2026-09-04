<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Concerns;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Http\AuthState;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsLedgerResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsPoolsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsQuotasResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\EntitlementsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\LicensesResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\ProductsResource;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\TokensResource;

/**
 * Provides immutable auth-state rebinding for auth-bound resource views.
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
trait RebindsAuthState
{
	/**
	 * Returns the current resource when the auth state is unchanged, or a rebound
	 * resource view when a different auth state is requested.
	 */
	public function withAuthState(AuthState $authState): self {
		if ($this->authState === $authState) {
			return $this;
		}

		return $this->rebindWithAuthState($authState);
	}

	/**
	 * Rebuilds the concrete resource with the provided auth state.
	 */
	abstract protected function rebindWithAuthState(AuthState $authState): self;
}
