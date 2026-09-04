<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Requests\License;

/**
 * Represents a regenerate-key request payload.
 *
 * @phpstan-type RegenerateKeyPayload array{
 *     identity_id: string
 * }
 */
final class RegenerateKey
{
	/**
	 * Identity identifier whose license key should be regenerated.
	 *
	 * @example identity_123
	 */
	public string $identityId;

	public function __construct(string $identityId) {
		$this->identityId = $identityId;
	}

	/**
	 * @return RegenerateKeyPayload
	 */
	public function toArray(): array {
		return [
			'identity_id' => $this->identityId,
		];
	}
}
