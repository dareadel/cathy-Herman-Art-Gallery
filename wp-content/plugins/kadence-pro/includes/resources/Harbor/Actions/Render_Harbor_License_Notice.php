<?php declare( strict_types=1 );

namespace KadenceWP\KadencePro\Harbor\Actions;

/**
 * Renders the Harbor (Liquid Web Software Manager) notice on legacy Uplink license forms.
 *
 * @since 1.2.0
 */
final class Render_Harbor_License_Notice {

	/**
	 * Renders a Harbor notice below the save button on the kadence-theme-pro
	 * Uplink license field.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function __invoke(): void {
		$url = lw_harbor_get_license_page_url();
		if ( empty( $url ) ) {
			return;
		}
		?>
		<hr style="margin: 20px 0;"/>
		<h4><span class="dashicons dashicons-info" style="vertical-align: middle; margin-right: 4px;"></span><?php esc_html_e( 'Liquid Web Software Manager', 'kadence-pro' ); ?></h4>
		<p class="tooltip description"><?php echo wp_kses(
			sprintf(
				/* translators: 1: product name, 2: URL to the Liquid Web Software Manager page. */
				__( '%1$s is now part of Liquid Web\'s software offerings. This field is still available for managing legacy licenses from your previous %1$s account. If you purchased a new plan through Liquid Web, your products are managed through the <a href="%2$s">Liquid Web Software Manager</a>.', 'kadence-pro' ),
				esc_html__( 'Kadence Theme Pro', 'kadence-pro' ),
				esc_url( $url )
			),
			[ 'a' => [ 'href' => [] ] ]
		); ?></p>
		<?php
	}

}
