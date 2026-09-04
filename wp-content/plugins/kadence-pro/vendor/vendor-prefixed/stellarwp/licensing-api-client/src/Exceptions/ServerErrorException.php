<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions;

/**
 * Thrown when the API returns a 5xx response.
 */
final class ServerErrorException extends ApiResponseException
{
}
