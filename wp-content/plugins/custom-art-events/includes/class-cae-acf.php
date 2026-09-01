<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('acf/init', 'cae_register_acf_fields');
function cae_register_acf_fields() {
    if( function_exists('acf_add_local_field_group') ):

    acf_add_local_field_group(array(
        'key' => 'group_art_event_details',
        'title' => 'Event Details',
        'fields' => array(
            array(
                'key' => 'field_event_date',
                'label' => 'Event Date',
                'name' => 'event_date',
                'type' => 'date_picker',
                'instructions' => 'Select the date of the event.',
                'required' => 1,
                'display_format' => 'F j, Y',
                'return_format' => 'Ymd', // Stored as YYYYMMDD for accurate database querying
            ),
            array(
                'key' => 'field_event_location',
                'label' => 'Location',
                'name' => 'event_location',
                'type' => 'text',
                'instructions' => 'e.g., Main Gallery, New York, NY',
            ),
            array(
                'key' => 'field_event_registration',
                'label' => 'Registration URL (Optional)',
                'name' => 'registration_url',
                'type' => 'url',
                'instructions' => 'Leave blank if this is just an informational event. If a URL is added, a "Register" button will appear on the frontend.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'art_event',
                ),
            ),
        ),
    ));

    endif;
}