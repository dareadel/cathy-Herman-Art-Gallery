<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('acf/init', 'cag_register_acf_settings');
function cag_register_acf_settings() {
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
                'instructions' => 'Enter a custom ratio like "16/9". Only used when "Custom Ratio" is selected above.',
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
            array(
                'key' => 'field_artworks',
                'label' => 'Gallery Photos (Table Interface)',
                'name' => 'artworks',
                'type' => 'repeater',
                'instructions' => 'Click "Add Artwork" to create a new row.',
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

// Map native image metadata to empty ACF fields on post save
add_filter('acf/update_value/name=artworks', 'cag_baseline_metadata_logic', 10, 3);
function cag_baseline_metadata_logic($value, $post_id, $field) {
    if ( empty($value) || !is_array($value) || !function_exists('acf_get_field') ) return $value;

    $image_sub_key = 'field_art_image';
    $title_sub_key = 'field_art_title';
    $alt_sub_key = 'field_art_alt'; 
    $caption_sub_key = 'field_art_caption_visible';

    foreach ($value as &$row) {
        if ( !isset($row[$image_sub_key]) || empty($row[$image_sub_key]) ) continue; 

        $image_id = absint($row[$image_sub_key]['ID']);

        if ( isset($row[$title_sub_key]) && empty($row[$title_sub_key]) ) {
            $native_title = get_the_title($image_id);
            if ( !empty($native_title) ) $row[$title_sub_key] = esc_html($native_title);
        }

        if ( isset($row[$alt_sub_key]) && empty($row[$alt_sub_key]) ) {
            $native_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            if ( !empty($native_alt) ) $row[$alt_sub_key] = esc_html($native_alt);
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