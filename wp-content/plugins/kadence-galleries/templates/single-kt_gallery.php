<?php
/**
 * Single Gallery Template.
 *
 * @package Kadence Galleries.
 */

get_header();

global $post;

do_action( 'kadence_gallery_post_before' );
?>
<div class="kt-galleries-container-outer">
	<div id="post-<?php echo esc_attr( $post->ID ); ?>" class="single-gallery entry content-bg single-entry">
		<?php
		while ( have_posts() ) :
			the_post();

			if ( post_password_required() ) {
				echo get_the_password_form();
			} else {
				do_action( 'kadence_gallery_post_before_content' );
				if ( apply_filters( 'kadence_gallery_single_show_title', true ) ) {
					?>
					<div class="kt-gallery-title">      
						<?php
						/**
						 * Hook for Gallery Title.
						 *
						 * @hooked kt_gal_title - 10
						 */
						do_action( 'kadence_gallery_post_header' );
						?>
					</div>
					<?php
				}
				?>
				<div class="entry-content gallery-content clearfix">
					<?php

					do_action( 'kadence_gallery_post_content_before' );

					echo do_shortcode( '[kadence_gallery id="' . esc_attr( $post->ID ) . '"]' );

					do_action( 'kadence_gallery_post_content_after' );

					?>
				</div>
				<?php
				do_action( 'kadence_gallery_post_after_content' );
				/**
				 * Hook for Gallery Comments.
				 *
				 * @hooked kt_gal_post_comments - 30
				 */
				do_action( 'kadence_gallery_post_comments' );
			}

		endwhile;
		?>
	</div>
</div>
<?php
do_action( 'kadence_gallery_post_after' );

get_footer();
