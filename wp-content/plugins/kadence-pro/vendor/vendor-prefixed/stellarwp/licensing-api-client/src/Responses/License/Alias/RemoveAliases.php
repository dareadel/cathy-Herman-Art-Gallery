<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\License\Alias;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Contracts\Response;

/**
 * Represents an alias removal response.
 *
 * @implements Response<array{removed: int}>
 */
final class RemoveAliases implements Response
{
	public int $removed;

	private function __construct(int $removed) {
		$this->removed = $removed;
	}

	/**
	 * @param array{removed: int} $attributes
	 */
	public static function from(array $attributes): self {
		return new self($attributes['removed']);
	}

	/**
	 * @return array{removed: int}
	 */
	public function toArray(): array {
		return [
			'removed' => $this->removed,
		];
	}
}
