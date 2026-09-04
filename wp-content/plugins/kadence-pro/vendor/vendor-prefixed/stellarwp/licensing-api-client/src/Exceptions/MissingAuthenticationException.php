<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions;

use RuntimeException;

/**
 * Thrown when a request requires authentication but no token is available.
 */
final class MissingAuthenticationException extends RuntimeException
{
}
