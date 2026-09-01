<?php
/**
 * Album Template.
 *
 * @package Kadence Galleries.
 */

get_header();

global $wp_query, $kt_galleries;
$cat_obj  = $wp_query->get_queried_object();
$termslug = $cat_obj->slug;
$columns  = array(
	'lg' => '3',
	'md' => '3',
	'sm' => '2',
);
// get the query object.
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$args  = array(
	'paged'          => $paged,
	'posts_per_page' => ( isset( $kt_galleries['album_post_per_page'] ) && ! empty( $kt_galleries['album_post_per_page'] ) ? $kt_galleries['album_post_per_page'] : '10' ),
	'orderby'        => 'menu-order',
	'order'          => 'ASC',
	'post_type'      => 'kt_gallery',
	'kt_album'       => $termslug,
);
$args = apply_filters( 'kadence_gallery_album_args', $args );
query_posts( $args );

$columns = apply_filters( 'kadence_album_columns', $columns, $termslug );
wp_enqueue_script( 'kadence-galleries' );
do_action( 'kadence_gallery_album_before' );
?>
<div class="kt-album-gallery">
	<?php

	do_action( 'kadence_gallery_album_before_content' );
	?>
	<div class="gallery-albumn-content clearfix">

		<?php
			echo category_description();
		?>
		<div class="kt-gal-outer">
			<div class="kt-galleries-loading kt-loadeding">
				<div class="kt-load-cube-grid">
					<div class="kt-load-cube kt-load-cube1"></div>
					<div class="kt-load-cube kt-load-cube2"></div>
					<div class="kt-load-cube kt-load-cube3"></div>
					<div class="kt-load-cube kt-load-cube4"></div>
					<div class="kt-load-cube kt-load-cube5"></div>
					<div class="kt-load-cube kt-load-cube6"></div>
					<div class="kt-load-cube kt-load-cube7"></div>
					<div class="kt-load-cube kt-load-cube8"></div>
					<div class="kt-load-cube kt-load-cube9"></div>
					<div class="kt-loading-text"><?php echo esc_html__( 'Loading Images', 'kadence-galleries' ); ?></div>
				</div>
			</div>
			<div class="kt-galleries-container kt-local-gallery kt-loadeding kt-gallery-<?php echo esc_attr( $termslug ); ?> kt-galleries-show-caption-bottom kt-ga-columns-lg-<?php echo esc_attr( $columns['lg'] ); ?> kt-ga-columns-md-<?php echo esc_attr( $columns['md'] ); ?> kt-ga-columns-sm-<?php echo esc_attr( $columns['sm'] ); ?>" data-gallery-source="local" data-gallery-lightbox="none" data-gallery-id="<?php echo esc_attr( $termslug ); ?>" data-ratio="notcropped" data-gallery-name="<?php echo esc_attr( $termslug ); ?>" data-gallery-filter="false" data-gallery-type="masonry">
				<?php
				do_action( 'kadence_gallery_album_content_before' );
				while ( have_posts() ) :
					the_post();
					do_action( 'kadence_gallery_loop' );
					endwhile;
				?>
				</div>
			</div>
		</div>
		<?php
		do_action( 'kadence_gallery_album_after_content' );
	?>
	</div>
<?php
do_action( 'kadence_gallery_album_after' );

get_footer();
