<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'art_gallery', 'cag_render_gallery_shortcode_modular' );
function cag_render_gallery_shortcode_modular( $atts ) {
    $atts = shortcode_atts( array( 'category' => '', 'id' => '' ), $atts );

    $args = array( 'post_type' => 'art_gallery', 'posts_per_page' => 1 );
    if ( !empty($atts['category']) ) {
        $args['tax_query'] = array( array( 'taxonomy' => 'art_category', 'field' => 'slug', 'terms' => $atts['category'] ) );
    } elseif ( !empty($atts['id']) ) {
        $args['p'] = intval($atts['id']);
    }

    $gallery_query = new WP_Query($args);
    if ( !$gallery_query->have_posts() ) return '<p style="text-align:center;">No gallery found.</p>';

    ob_start();
    while ( $gallery_query->have_posts() ) {
        $gallery_query->the_post();
        
        $display_type = get_field('display_type') ?: 'slider';
        $caption_style = get_field('caption_style') ?: 'hover';
        $main_image_ratio = get_field('main_image_ratio') ?: 'square';
        $custom_main_ratio = get_field('custom_main_ratio');
        $thumbnail_image_ratio = get_field('thumbnail_image_ratio') ?: 'square';
        $artworks = get_field('artworks');

        if ( !$artworks ) continue;
        
        $uid = uniqid('cag_');
        $container_classes = 'cag-gallery-container cag-uid-' . $uid . ' cag-ratio-main-' . esc_attr($main_image_ratio);
        
        $inline_style = '';
        if ($main_image_ratio === 'custom' && !empty($custom_main_ratio)) {
            $inline_style = ' style="--cag-custom-main-ratio: ' . esc_attr($custom_main_ratio) . ';"';
        }
        
        if ( $display_type === 'slider_thumbs' ) {
            $container_classes .= ' cag-ratio-thumb-' . esc_attr($thumbnail_image_ratio);
        }

        // Output wrapper with data attributes for the external JS file
        echo '<div class="' . esc_attr($container_classes) . '" data-uid="' . esc_attr($uid) . '" data-display="' . esc_attr($display_type) . '"' . $inline_style . '>';
        
        // Main Wrapper
        if ( strpos($display_type, 'slider') !== false ) {
            echo '<div class="swiper cag-swiper-main-' . esc_attr($uid) . ' cag-slider">';
            echo '<div class="swiper-wrapper">';
        } else {
            $grid_class = ($display_type === 'masonry') ? 'cag-masonry' : 'cag-grid';
            echo '<div class="' . esc_attr($grid_class) . '">';
        }

        // Loop Artworks
        foreach ( $artworks as $art ) {
            $image_array = isset($art['image']) ? $art['image'] : array();
            $img_url = isset($image_array['url']) ? $image_array['url'] : '';
            $img_alt = !empty($art['art_alt']) ? esc_attr($art['art_alt']) : 'Artwork';
            $title   = !empty($art['art_title']) ? esc_html($art['art_title']) : '';
            $caption = !empty($art['visible_caption']) ? wp_kses_post($art['visible_caption']) : '';
            $price   = !empty($art['price']) ? esc_html($art['price']) : '';

            $full_img_url = '';
            $image_id = isset($image_array['ID']) ? absint($image_array['ID']) : 0;
            if ($image_id) {
                $full_src_array = wp_get_attachment_image_src($image_id, 'full');
                $full_img_url = isset($full_src_array[0]) ? $full_src_array[0] : '';
            }
            if (empty($full_img_url)) $full_img_url = $img_url;

            $wrapper_class = (strpos($display_type, 'slider') !== false) ? 'swiper-slide' : '';
            
            echo '<div class="' . esc_attr($wrapper_class) . '">';
            echo '<div class="cag-item">';
            
            // Build caption HTML for Lightbox data parsing
            $caption_style_output = '';
            if( $title || $caption || $price ) {
                $caption_style_output .= '<div class="cag-caption cag-cap-' . esc_attr($caption_style) . '">';
                if ( $title ) $caption_style_output .= '<h4>' . $title . '</h4>';
                if ( $caption ) $caption_style_output .= '<div class="cag-visible-caption">' . $caption . '</div>';
                if ( !empty($price) ) $caption_style_output .= '<p class="cag-price">$' . $price . '</p>';
                $caption_style_output .= '</div>';
            }

            // CRITICAL FIX: Changed wp_kses_post to esc_attr to prevent HTML quotes from breaking the DOM
            echo '<a href="' . esc_url($full_img_url) . '" class="cag-lightbox" data-caption="' . esc_attr($caption_style_output) . '">';
            echo '<div class="cag-item-aspect-ratio">';
            echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" />';
            echo '</div></a>'; 
            
            // Output the overlay safely
            echo $caption_style_output;
            
            echo '</div></div>';
        }

        // Close Main Wrapper
        if ( strpos($display_type, 'slider') !== false ) {
            echo '</div>'; // close swiper-wrapper
            if ($display_type === 'slider') echo '<div class="swiper-pagination"></div>';
            echo '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
            echo '</div>'; // close main swiper

            // Thumbnails Wrapper
            if ( $display_type === 'slider_thumbs' ) {
                echo '<div class="swiper cag-swiper-thumbs-' . esc_attr($uid) . ' cag-thumb-gallery">';
                echo '<div class="swiper-wrapper">';
                foreach ( $artworks as $art ) {
                    $img_thumb = isset($art['image']['sizes']['thumbnail']) ? $art['image']['sizes']['thumbnail'] : $art['image']['url'];
                    echo '<div class="swiper-slide"><div class="cag-thumb-aspect-ratio">';
                    echo '<img src="' . esc_url($img_thumb) . '" />';
                    echo '</div></div>'; 
                }
                echo '</div></div>';
            }
        } else {
            echo '</div>'; // close grid
        }
        echo '</div>'; // close container
    }

    wp_reset_postdata();
    return ob_get_clean();
}