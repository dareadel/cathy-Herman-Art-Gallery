<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'art_events', 'cae_render_events_shortcode' );
function cae_render_events_shortcode() {
    $today = current_time('Ymd'); 

    $future_args = array(
        'post_type'      => 'art_event',
        'posts_per_page' => 5,
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array( array( 'key' => 'event_date', 'value' => $today, 'compare' => '>=' ) )
    );
    $future_events = new WP_Query($future_args);

    $past_args = array(
        'post_type'      => 'art_event',
        'posts_per_page' => 5,
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => array( array( 'key' => 'event_date', 'value' => $today, 'compare' => '<' ) )
    );
    $past_events = new WP_Query($past_args);

    ob_start();
    echo '<div class="cae-events-section">';

    echo '<h3>Upcoming Events</h3>';
    if ( $future_events->have_posts() ) {
        echo '<div class="cae-grid">';
        while ( $future_events->have_posts() ) {
            $future_events->the_post();
            cae_render_single_event_card();
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p class="cae-no-events">No upcoming events at this time.</p>';
    }

    echo '<h3>Past Events</h3>';
    if ( $past_events->have_posts() ) {
        echo '<div class="cae-grid">';
        while ( $past_events->have_posts() ) {
            $past_events->the_post();
            cae_render_single_event_card();
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p class="cae-no-events">No past events found.</p>';
    }

    echo '</div>';
    return ob_get_clean();
}

function cae_render_single_event_card() {
    $title    = get_the_title();
    $date_raw = get_field('event_date'); 
    $date_formatted = $date_raw ? date_i18n('F j, Y', strtotime($date_raw)) : '';
    $location = get_field('event_location');
    $reg_url  = get_field('registration_url');

    echo '<div class="cae-event-card">';
    if ( $date_formatted ) echo '<div class="cae-event-date">' . esc_html($date_formatted) . '</div>';
    echo '<h4 class="cae-event-title">' . esc_html($title) . '</h4>';
    if ( $location ) echo '<div class="cae-event-location">' . esc_html($location) . '</div>';
    if ( $reg_url ) echo '<a href="' . esc_url($reg_url) . '" class="cae-register-btn" target="_blank" rel="noopener noreferrer">Register</a>';
    echo '</div>';
}