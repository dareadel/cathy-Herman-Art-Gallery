<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions;

use RuntimeException;

/**
 * Thrown when a response body cannot be decoded into the expected JSON structure.
 */
final class DecodingException extends RuntimeException
{
}
