<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Credit;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Contracts\Response;

/**
 * Represents a successful quota deletion.
 *
 * @implements Response<array{deleted: bool}>
 */
final class DeleteQuota implements Response
{
	public bool $deleted;

	private function __construct(bool $deleted) {
		$this->deleted = $deleted;
	}

	/**
	 * @param array{deleted: bool} $attributes
	 */
	public static function from(array $attributes): self {
		return new self($attributes['deleted']);
	}

	/**
	 * @return array{deleted: bool}
	 */
	public function toArray(): array {
		return [
			'deleted' => $this->deleted,
		];
	}
}
