<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions;

use RuntimeException;

/**
 * Thrown when the API returns a malformed or unexpected response.
 */
final class UnexpectedResponseException extends RuntimeException
{
}
