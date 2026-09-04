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
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Requests\Credit\CreatePool;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Requests\Credit\DeletePool as DeletePoolRequest;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Requests\Credit\UpdatePool;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\DeletePool;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\PoolCollection;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\ValueObjects\CreditPool;
use KadenceWP\KadencePro\Psr\Http\Client\ClientExceptionInterface;

/**
 * Defines the credits pools resource surface.
 */
interface CreditsPoolsResourceInterface
{
	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function list(string $licenseKey, bool $active = false): PoolCollection;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function create(CreatePool $request): CreditPool;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function update(UpdatePool $request): CreditPool;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function delete(DeletePoolRequest $request): DeletePool;
}
