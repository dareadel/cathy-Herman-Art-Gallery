<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Resources\Contracts;

use Generator;
use JsonException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\Contracts\ApiErrorExceptionInterface;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Requests\Credit\ListLedgerEntries;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit\LedgerPage;
use KadenceWP\KadencePro\Psr\Http\Client\ClientExceptionInterface;

/**
 * Defines the credits ledger resource surface.
 */
interface CreditsLedgerResourceInterface
{
	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function list(ListLedgerEntries $request): LedgerPage;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 *
	 * @return Generator<int, LedgerPage, mixed, void>
	 */
	public function pages(ListLedgerEntries $request): Generator;
}
