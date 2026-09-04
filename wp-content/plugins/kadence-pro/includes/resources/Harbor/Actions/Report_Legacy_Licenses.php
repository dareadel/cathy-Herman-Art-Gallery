<?php declare( strict_types=1 );

namespace KadenceWP\KadencePro\Harbor\Actions;

use function KadenceWP\KadencePro\StellarWP\Uplink\get_license_key;
use function KadenceWP\KadencePro\StellarWP\Uplink\get_resource;

/**
 * Reports Kadence's legacy Uplink licenses into Harbor's unified license listing.
 *
 * @since 1.2.0
 */
final class Report_Legacy_Licenses {

	/**
	 * Reports the kadence-theme-pro Uplink-managed license to Harbor so it
	 * appears in the unified license UI.
	 *
	 * @since TBD add use_for_updates
	 * @since 1.2.0
	 *
	 * @param array $licenses Existing legacy licenses from other plugins.
	 *
	 * @return array
	 */
	public function __invoke( array $licenses ): array {
		$slug = 'kadence-theme-pro';
		$key  = get_license_key( $slug );

		if ( empty( $key ) ) {
			return $licenses;
		}

		$resource = get_resource( $slug );

		if ( ! $resource ) {
			return $licenses;
		}

		$is_active = $resource->has_valid_license();

		$licenses[] = [
			'key'       => $key,
			'slug'      => $slug,
			'name'      => __( 'Kadence Theme Pro', 'kadence-pro' ),
			'product'   => 'kadence',
			'is_active' => $is_active,
			'page_url'  => esc_url( admin_url( 'themes.php?page=kadence' ) ),
			'use_for_updates' => true,
		];

		return $licenses;
	}

}
