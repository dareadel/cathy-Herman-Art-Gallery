<?php
/**
 * Plugin Name: Custom Art Gallery UI (Accessible & Dynamic Final)
 * Description: Fully restored unified plugin. Safely maintains the ACF Repeater field for all artwork data. Fully dynamic frontend that conditionally renders sliders, grids, and aspect ratios based on backend selections. Includes SimpleLightbox integration, large desktop typography, Cathy Herman logo matching, and ADA compliance server-side mapping.
 * Version: 2.5.0
 * Author: Your Gemini Assistant
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// ==========================================
// 1. Register Custom Post Type & Taxonomy
// ==========================================
add_action( 'init', 'cag_register_cpt_taxonomy_v3' );
function cag_register_cpt_taxonomy_v3() {
    register_taxonomy( 'art_category', array( 'art_gallery' ), array(
        'hierarchical'      => true,
        'labels'            => array(
            'name'          => 'Art Categories',
            'singular_name' => 'Art Category',
            'menu_name'     => 'Art Category',
        ),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array( 'slug' => 'art-category' ),
    ));

    $labels = array(
        'name'               => 'Art Galleries',
        'singular_name'      => 'Art Gallery',
        'menu_name'          => 'Art Galleries',
        'name_admin_bar'     => 'Art Gallery',
        'add_new'            => 'Add Gallery', 
        'add_new_item'       => 'Add Gallery',
        'new_item'           => 'New Gallery',
        'edit_item'          => 'Edit Gallery',
        'view_item'          => 'View Gallery',
        'all_items'          => 'All Galleries',
        'search_items'       => 'Search Galleries',
        'not_found'          => 'No galleries found.',
        'not_found_in_trash' => 'No galleries found in Trash.',
    );

    register_post_type( 'art_gallery', array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-format-gallery',
        'supports'      => array( 'title' ),
    ));
}

// ==========================================
// 2. Register Advanced ACF Field Group (RESTORED)
// ==========================================
add_action('acf/init', 'cag_register_acf_fields_v3');
function cag_register_acf_fields_v3() {
    if( function_exists('acf_add_local_field_group') ):

    acf_add_local_field_group(array(
        'key' => 'group_art_gallery_settings_v2', 
        'title' => 'Gallery Settings & Artwork Interface',
        'fields' => array(
            array(
                'key' => 'field_display_type',
                'label' => 'Gallery Layout Style',
                'name' => 'display_type',
                'type' => 'select',
                'instructions' => 'Choose the frontend visual structure.',
                'choices' => array(
                    'slider'        => 'Carousel Slider',
                    'slider_thumbs' => 'Thumbnail Slider',
                    'grid'          => 'Uniform Grid',
                    'masonry'       => 'Masonry Grid',
                ),
                'default_value' => 'slider',
                'wrapper' => array('width' => '33.3%'),
            ),
            array(
                'key' => 'field_caption_style',
                'label' => 'Caption & Price Placement',
                'name' => 'caption_style',
                'type' => 'select',
                'instructions' => 'Choose how titles and prices behave.',
                'choices' => array(
                    'hover'         => 'Cover Image (Show on Hover)',
                ),
                'default_value' => 'hover',
                'wrapper' => array('width' => '33.3%'),
            ),
            array(
                'key' => 'field_main_image_ratio',
                'label' => 'Main Image Aspect Ratio',
                'name' => 'main_image_ratio',
                'type' => 'select',
                'instructions' => 'Controls the aspect ratio of the main gallery images for Slider and Grid layouts.',
                'choices' => array(
                    'square'        => 'Square (1:1)',
                    'landscape_3_2' => 'Landscape (3:2)',
                    'landscape_4_3' => 'Landscape (4:3)',
                    'portrait_3_4'  => 'Portrait (3:4)',
                    'portrait_2_3'  => 'Portrait (2:3)',
                    'custom'        => 'Custom Ratio',
                ),
                'default_value' => 'square',
                'wrapper' => array('width' => '33.3%'),
            ),
            array(
                'key' => 'field_custom_main_ratio',
                'label' => 'Custom Main Ratio (e.g., 16/9)',
                'name' => 'custom_main_ratio',
                'type' => 'text',
                'instructions' => 'Enter a custom ratio like "16/9" for widescreen or "1" for square. Only used when "Custom Ratio" is selected above.',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_main_image_ratio',
                            'operator' => '==',
                            'value' => 'custom',
                        ),
                    ),
                ),
                'wrapper' => array('width' => '50%'),
            ),
            array(
                'key' => 'field_thumbnail_image_ratio',
                'label' => 'Thumbnail Aspect Ratio',
                'name' => 'thumbnail_image_ratio',
                'type' => 'select',
                'instructions' => 'Controls the aspect ratio of the thumbnails in the Thumbnail Slider layout.',
                'choices' => array(
                    'square'        => 'Square (1:1)',
                    'landscape_3_2' => 'Landscape (3:2)',
                    'landscape_4_3' => 'Landscape (4:3)',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_display_type',
                            'operator' => '==',
                            'value' => 'slider_thumbs',
                        ),
                    ),
                ),
                'default_value' => 'square',
                'wrapper' => array('width' => '50%'),
            ),
            // --- CRITICAL RESTORE: The entire Artwork Repeater Array is back ---
            array(
                'key' => 'field_artworks',
                'label' => 'Gallery Photos (Table Interface)',
                'name' => 'artworks',
                'type' => 'repeater',
                'instructions' => 'Click "Add Artwork" to create a new row. The table layout acts as a spreadsheet for fast input. Dedicated fields for Title, Alt Text, Caption, and Price provide granular contextual control.',
                'layout' => 'table', 
                'button_label' => 'Add Artwork',
                'sub_fields' => array(
                    array(
                        'key' => 'field_art_image',
                        'label' => 'Artwork Image',
                        'name' => 'image',
                        'type' => 'image',
                        'return_format' => 'array',
                    ),
                    array(
                        'key' => 'field_art_title',
                        'label' => 'Artwork Title',
                        'name' => 'art_title',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_art_alt',
                        'label' => 'Alt Text (Accessibility)',
                        'name' => 'art_alt',
                        'type' => 'text',
                        'instructions' => 'Explicit contextual Alt text. Crucial for ADA/accessibility compliance. Used for the HTML `alt=""` tag.',
                    ),
                    array(
                        'key' => 'field_art_caption_visible',
                        'label' => 'Frontend Caption',
                        'name' => 'visible_caption',
                        'type' => 'textarea',
                        'new_lines' => 'br',
                        'rows' => 3,
                    ),
                    array(
                        'key' => 'field_art_price',
                        'label' => 'Price ($)',
                        'name' => 'price',
                        'type' => 'text',
                        'prepend' => '$',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'art_gallery',
                ),
            ),
        ),
    ));

    endif;
}

// ==========================================
// 3. Enqueue Assets (Swiper.js + SimpleLightbox)
// ==========================================
add_action( 'wp_enqueue_scripts', 'cag_enqueue_assets_v3' );
function cag_enqueue_assets_v3() {
    wp_enqueue_style( 'swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css' );
    wp_enqueue_script( 'swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', array(), null, true );
    wp_enqueue_style( 'simplelightbox-css', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.0/simple-lightbox.min.css' );
    wp_enqueue_script( 'simplelightbox-js', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.0/simple-lightbox.min.js', array('jquery'), null, true );
}

// ==========================================
// 4. Inject Dynamic CSS 
// ==========================================
add_action('wp_head', 'cag_custom_styles_v3');
function cag_custom_styles_v3() {
    ?>
    <style>
        .cag-gallery-container {
            max-width: 1200px;
            margin: 0 auto 40px auto;
            text-align: center;
            font-family: inherit; 
        }
        .cag-item {
            position: relative;
            overflow: hidden;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            height: 100%;
        }
        
        .cag-item img { display: block; width: 100%; object-fit: cover; }
        .cag-grid .cag-item img, .cag-slider .cag-item img { height: 350px; }
        .cag-ratio-main-custom .cag-item img { height: 100% !important; }
        .cag-item-aspect-ratio { width: 100%; display: block; }

        .cag-masonry .cag-item-aspect-ratio { aspect-ratio: auto; } 
        .cag-masonry .cag-item img { height: auto; }

        .cag-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .cag-masonry { column-count: 3; column-gap: 20px; }
        .cag-masonry .cag-item { break-inside: avoid; margin-bottom: 20px; }
        @media (max-width: 900px) { .cag-masonry { column-count: 2; } }
        @media (max-width: 600px) { .cag-masonry { column-count: 1; } }

        .cag-caption {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: auto; 
            max-width: 90%;
            background: rgba(255, 255, 255, 0.85); 
            padding: 20px;
            border-radius: 8px;
            box-sizing: border-box;
            z-index: 10;
        }

        .cag-caption h4, .cag-visible-caption, .cag-price { font-family: inherit; }
        .cag-caption h4 { margin: 0 0 5px 0; color: #7F5F30; font-weight: bold; line-height: 1.1; font-family: "Garamond Premier", Garamond, Georgia, serif; letter-spacing: -0.015em; }
        .cag-visible-caption { margin: 0 0 10px 0; color: #A17F55; line-height: 1.3; font-family: inherit; }
        .cag-price { margin: 0; color: #A17F55; font-weight: bold; font-family: inherit; }

        @media (max-width: 768px) {
            .cag-cap-hover { opacity: 1; visibility: visible; }
            .cag-caption h4 { font-size: 1.2rem; }
            .cag-visible-caption { font-size: 1rem; }
            .cag-price { font-size: 1.1rem; }
        }

        @media (min-width: 769px) {
            .cag-cap-hover { opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; }
            .cag-item:hover .cag-cap-hover { opacity: 1; visibility: visible; }
            .cag-caption h4 { font-size: 3rem; margin-bottom: 10px; color: #7F5F30; font-family: "Garamond Premier", Garamond, Georgia, serif; font-weight: bold; letter-spacing: -0.015em; }
            .cag-visible-caption { font-size: 2.8rem; margin-bottom: 15px; }
            .cag-price { font-size: 2.9rem; }
        }

        .cag-item:hover { cursor: pointer; }
        .sl-caption { background: rgba(255, 255, 255, 0.95); padding: 25px; text-align: center; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 80%; margin: 0 auto; }
        .cag-thumb-gallery { margin-top: 15px; }
        .cag-thumb-gallery .swiper-slide { opacity: 0.5; cursor: pointer; border-radius: 4px; overflow:hidden;}
        .cag-thumb-gallery .swiper-slide-thumb-active { opacity: 1; border: 2px solid #333; }
        .cag-thumb-aspect-ratio { display: block; width: 100%; }
        .cag-thumb-aspect-ratio img { height: 100%; width: 100%; object-fit: cover; }
        .swiper-button-next, .swiper-button-prev { color: #222; }
        .swiper-pagination-bullet-active { background: #222; }
    </style>
    <?php
}

// ==========================================
// 5. Shortcode Renderer (Dynamic & Lightbox)
// ==========================================
add_shortcode( 'art_gallery', 'cag_render_gallery_shortcode_v2' );
function cag_render_gallery_shortcode_v2( $atts ) {
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
        $artworks = get_field('artworks');
        
        $main_image_ratio = get_field('main_image_ratio') ?: 'square';
        $custom_main_ratio = get_field('custom_main_ratio');
        $thumbnail_image_ratio = get_field('thumbnail_image_ratio') ?: 'square';

        if ( !$artworks ) continue;
        $uid = uniqid('cag_');
        
        $container_classes = 'cag-gallery-container cag-ratio-main-' . esc_attr($main_image_ratio);
        
        if ($main_image_ratio === 'custom' && !empty($custom_main_ratio)) {
            $inline_style_var = ' style="--cag-custom-main-ratio: ' . esc_attr($custom_main_ratio) . ';"';
            echo '<style>.cag-gallery-container.cag-ratio-main-custom .cag-item-aspect-ratio { aspect-ratio: var(--cag-custom-main-ratio); }</style>';
        } else {
            $inline_style_var = '';
        }
        
        if ( $display_type === 'slider_thumbs' ) {
            $container_classes .= ' cag-ratio-thumb-' . esc_attr($thumbnail_image_ratio);
        }

        echo '<div class="' . esc_attr($container_classes) . '"' . $inline_style_var . '>';
        
        if ( strpos($display_type, 'slider') !== false ) {
            echo '<div class="swiper cag-swiper-main-' . $uid . ' cag-slider">';
            echo '<div class="swiper-wrapper">';
        } else {
            $grid_class = ($display_type === 'masonry') ? 'cag-masonry' : 'cag-grid';
            echo '<div class="' . $grid_class . '">';
        }

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
            if (empty($full_img_url)) {
                $full_img_url = $img_url;
            }

            $wrapper_class = (strpos($display_type, 'slider') !== false) ? 'swiper-slide' : '';
            
            echo '<div class="' . $wrapper_class . '">';
            echo '<div class="cag-item">';
            
            $caption_style_output = '';
            if( $title || $caption || $price ) {
                $caption_style_output .= '<div class="cag-caption cag-cap-' . esc_attr($caption_style) . '">';
                if ( $title ) $caption_style_output .= '<h4>' . $title . '</h4>';
                if ( $caption ) $caption_style_output .= '<div class="cag-visible-caption">' . $caption . '</div>';
                if ( !empty($price) ) $caption_style_output .= '<p class="cag-price">$' . $price . '</p>';
                $caption_style_output .= '</div>';
            }

            echo '<a href="' . esc_url($full_img_url) . '" class="cag-lightbox cag-uid-' . $uid . '" data-caption="' . wp_kses_post($caption_style_output) . '">';
            
            echo '<div class="cag-item-aspect-ratio">';
            echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" />';
            echo '</div>'; 
            
            echo '</a>'; 
            echo $caption_style_output;
            
            echo '</div>';
            echo '</div>';
        }

        if ( strpos($display_type, 'slider') !== false ) {
            echo '</div>'; 
            if ($display_type === 'slider') echo '<div class="swiper-pagination"></div>';
            echo '<div class="swiper-button-prev"></div>';
            echo '<div class="swiper-button-next"></div>';
            echo '</div>'; 

            if ( $display_type === 'slider_thumbs' ) {
                echo '<div class="swiper cag-swiper-thumbs-' . $uid . ' cag-thumb-gallery">';
                echo '<div class="swiper-wrapper">';
                foreach ( $artworks as $art ) {
                    $img_url = isset($art['image']['sizes']['thumbnail']) ? $art['image']['sizes']['thumbnail'] : $art['image']['url'];
                    echo '<div class="swiper-slide">';
                    echo '<div class="cag-thumb-aspect-ratio">';
                    echo '<img src="' . esc_url($img_url) . '" />';
                    echo '</div>'; 
                    echo '</div>'; 
                }
                echo '</div></div>';
            }

            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let thumbs_<?php echo $uid; ?> = null;
                    
                    <?php if ( $display_type === 'slider_thumbs' ) : ?>
                    thumbs_<?php echo $uid; ?> = new Swiper('.cag-swiper-thumbs-<?php echo $uid; ?>', {
                        spaceBetween: 15,
                        slidesPerView: 4,
                        freeMode: true,
                        watchSlidesProgress: true,
                        breakpoints: { 768: { slidesPerView: 6 } }
                    });
                    <?php endif; ?>

                    new Swiper('.cag-swiper-main-<?php echo $uid; ?>', {
                        slidesPerView: 1,
                        spaceBetween: 20,
                        loop: <?php echo ($display_type === 'slider') ? 'true' : 'false'; ?>,
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                        <?php if ( $display_type === 'slider' ) : ?>
                        pagination: { el: '.swiper-pagination', clickable: true },
                        breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
                        <?php else: ?>
                        thumbs: { swiper: thumbs_<?php echo $uid; ?> }
                        <?php endif; ?>
                    });

                    jQuery(document).ready(function($) {
                        let lightboxSelectors = $('.cag-lightbox.cag-uid-<?php echo $uid; ?>');
                        if (lightboxSelectors.length > 0) {
                            new SimpleLightbox(lightboxSelectors, {
                                captionsData: 'alt', 
                                captionDelay: 250,
                                captionSelector: function getCaption(el) {
                                    return el.getAttribute('data-caption');
                                },
                                captionPosition: 'bottom', 
                                showCounter: true,
                                alertError: true
                            });
                        }
                    });

                });
            </script>
            <?php
        } else {
            echo '</div>'; 
        }
        
        echo '</div>'; 
    }

    wp_reset_postdata();
    return ob_get_clean();
}

// ==========================================
// 6. Expert Smart Metadata Baseline Logic 
// ==========================================
add_filter('acf/update_value/name=artworks', 'cag_baseline_metadata_to_empty_fields_optimized', 10, 3);
function cag_baseline_metadata_to_empty_fields_optimized($value, $post_id, $field) {
    if ( empty($value) || !is_array($value) || !function_exists('acf_get_field') ) {
        return $value;
    }

    $image_sub_key   = 'field_art_image';
    $title_sub_key   = 'field_art_title';
    $alt_sub_key     = 'field_art_alt'; 
    $caption_sub_key = 'field_art_caption_visible';

    foreach ($value as &$row) {
        if ( !isset($row[$image_sub_key]) || empty($row[$image_sub_key]) ) {
            continue; 
        }

        $image_id = absint($row[$image_sub_key]['ID']);

        if ( isset($row[$title_sub_key]) && empty($row[$title_sub_key]) ) {
            $native_title = get_the_title($image_id);
            if ( !empty($native_title) ) {
                $row[$title_sub_key] = esc_html($native_title);
            }
        }

        if ( isset($row[$alt_sub_key]) && empty($row[$alt_sub_key]) ) {
            $native_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            if ( !empty($native_alt) ) {
                $row[$alt_sub_key] = esc_html($native_alt);
            }
        }

        if ( isset($row[$caption_sub_key]) && empty($row[$caption_sub_key]) ) {
            $attachment_meta = wp_get_attachment_image_meta($image_id);
            if ( $attachment_meta && !empty($attachment_meta['caption']) ) {
                $row[$caption_sub_key] = esc_html($attachment_meta['caption']);
            }
        }
    }

    return $value;
}