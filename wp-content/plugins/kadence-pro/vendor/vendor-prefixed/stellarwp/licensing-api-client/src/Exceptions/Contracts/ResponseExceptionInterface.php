<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\Contracts;

use KadenceWP\KadencePro\Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Marks exceptions that were created from an HTTP response.
 */
interface ResponseExceptionInterface extends Throwable
{
	/**
	 * Returns the raw PSR-7 response for debugging and inspection.
	 */
	public function getResponse(): ResponseInterface;

	/**
	 * Returns the HTTP status code from the failed response.
	 */
	public function statusCode(): int;
}
