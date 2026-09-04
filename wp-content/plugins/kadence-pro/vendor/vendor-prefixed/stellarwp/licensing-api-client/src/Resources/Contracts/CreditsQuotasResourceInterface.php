<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Contracts;

use JsonException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\Contracts\ApiErrorExceptionInterface;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Requests\Credit\SetQuota;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\DeleteQuota;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\QuotaCollection;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\ValueObjects\SiteQuota;
use KadenceWP\KadencePro\Psr\Http\Client\ClientExceptionInterface;

/**
 * Defines the credits quotas resource surface.
 */
interface CreditsQuotasResourceInterface
{
	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function list(string $licenseKey): QuotaCollection;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function set(SetQuota $request): SiteQuota;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function delete(string $licenseKey, string $domain, string $creditType): DeleteQuota;
}
