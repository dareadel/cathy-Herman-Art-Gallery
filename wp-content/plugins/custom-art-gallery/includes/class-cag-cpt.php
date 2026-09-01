<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'cag_register_cpt_and_taxonomy' );
function cag_register_cpt_and_taxonomy() {
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