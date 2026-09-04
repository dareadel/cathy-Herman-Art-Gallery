<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Entitlement;

use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Contracts\Response;
use KadenceWP\KadencePro\LiquidWeb\LicensingApiClient\Responses\Entitlement\ValueObjects\UpsertProduct;

/**
 * Represents an entitlement upsert response payload.
 *
 * @phpstan-import-type ResponseProductPayload from UpsertProduct
 *
 * @implements Response<array{
 *     license_key: string,
 *     products: list<ResponseProductPayload>
 * }>
 */
final class Upsert implements Response
{
	public string $licenseKey;

	/**
	 * @var UpsertProduct[]
	 */
	public array $products;

	/**
	 * @param UpsertProduct[] $products
	 */
	private function __construct(string $licenseKey, array $products) {
		$this->licenseKey = $licenseKey;
		$this->products   = $products;
	}

	/**
	 * @param array{license_key: string, products: list<ResponseProductPayload>} $attributes
	 */
	public static function from(array $attributes): self {
		return new self(
			$attributes['license_key'],
			array_map(
				static fn (array $product): UpsertProduct => UpsertProduct::from($product),
				$attributes['products']
			)
		);
	}

	public function toArray(): array {
		return [
			'license_key' => $this->licenseKey,
			'products'    => array_map(
				static fn (UpsertProduct $product): array => $product->toArray(),
				$this->products
			),
		];
	}
}
