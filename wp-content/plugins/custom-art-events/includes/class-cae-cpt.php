<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'cae_register_event_cpt' );
function cae_register_event_cpt() {
    $labels = array(
        'name'               => 'Events',
        'singular_name'      => 'Event',
        'menu_name'          => 'Events',
        'add_new'            => 'Add New Event',
        'add_new_item'       => 'Add New Event',
        'edit_item'          => 'Edit Event',
        'new_item'           => 'New Event',
        'view_item'          => 'View Event',
        'search_items'       => 'Search Events',
        'not_found'          => 'No events found',
    );

    register_post_type( 'art_event', array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => false, // Not needed for a one-page site
        'menu_icon'     => 'dashicons-calendar-alt',
        'supports'      => array( 'title' ), // Title will be the Event Name
    ));
}