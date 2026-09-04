<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\License\ValueObjects;

use DateTimeImmutable;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Concerns\InteractsWithDateTime;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Contracts\Response;

/**
 * Represents an active site activation returned during validation.
 *
 * @implements Response<array{domain: string, activated_at: string}>
 */
final class Activation implements Response
{
	use InteractsWithDateTime;

	public string $domain;

	public DateTimeImmutable $activatedAt;

	private function __construct(string $domain, DateTimeImmutable $activatedAt) {
		$this->domain      = $domain;
		$this->activatedAt = $activatedAt;
	}

	/**
	 * @param array{domain: string, activated_at: string} $attributes
	 *
	 * @throws UnexpectedResponseException
	 */
	public static function from(array $attributes): self {
		return new self(
			$attributes['domain'],
			self::parseDateTime($attributes['activated_at'])
		);
	}

	public function toArray(): array {
		return [
			'domain'       => $this->domain,
			'activated_at' => $this->formatDateTime($this->activatedAt),
		];
	}
}
