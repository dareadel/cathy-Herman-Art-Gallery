<?php
/**
 * Plugin Name: Nonprofit Event Home List (for The Events Calendar)
 * Description: Shortcode that displays a minimalist grid of up to 5 past and
 *              5 upcoming events (title, location, date) pulled from The
 *              Events Calendar. Rows alternate background color, which the
 *              site owner can set from an admin settings screen. Each row
 *              links to the normal single-event page, so registration/RSVP
 *              continues to be handled entirely by The Events Calendar.
 * Version:     1.0.0
 * Author:      Custom (built for a nonprofit on a zero-plugin-budget)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NP_Event_Home_List {

    const OPTION_KEY   = 'np_ehl_settings';
    const NONCE_ACTION = 'np_ehl_save_settings';
    const NONCE_FIELD  = 'np_ehl_nonce';
    const MAX_ROWS     = 5; // hard cap, per spec: "up to 5" each direction

    private $style_printed = false;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_shortcode( 'np_event_home_list', array( $this, 'render_shortcode' ) );
    }

    /* ---------------------------------------------------------------------
     * Settings
     * ------------------------------------------------------------------ */

    private function get_defaults() {
        return array(
            'bg_odd'      => '#ffffff',
            'bg_even'     => '#f5f5f5',
            'past_count'  => 5,
            'future_count'=> 5,
            'date_format' => 'M j, Y',
        );
    }

    private function get_settings() {
        $saved = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( $saved, $this->get_defaults() );
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=tribe_events', // lives under the existing Events menu
            'Home List Settings',
            'Home List Settings',
            'manage_options',
            'np-event-home-list',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'np-event-home-list' ) === false ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_add_inline_script( 'wp-color-picker', "jQuery(function($){ $('.np-ehl-color-field').wpColorPicker(); });" );
    }

    public function render_settings_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();

        if ( isset( $_POST['np_ehl_save'] ) && check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD ) ) {

            $settings['bg_odd']       = sanitize_hex_color( wp_unslash( $_POST['bg_odd'] ) ) ?: $this->get_defaults()['bg_odd'];
            $settings['bg_even']      = sanitize_hex_color( wp_unslash( $_POST['bg_even'] ) ) ?: $this->get_defaults()['bg_even'];
            $settings['past_count']   = min( self::MAX_ROWS, max( 0, absint( $_POST['past_count'] ) ) );
            $settings['future_count'] = min( self::MAX_ROWS, max( 0, absint( $_POST['future_count'] ) ) );
            $settings['date_format']  = sanitize_text_field( wp_unslash( $_POST['date_format'] ) );

            update_option( self::OPTION_KEY, $settings );
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Event Home List Settings</h1>
            <p>These are the defaults used by the <code>[np_event_home_list]</code> shortcode. Any of them can be overridden per-instance with shortcode attributes (see below).</p>

            <form method="post">
                <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="bg_odd">Odd row background</label></th>
                        <td><input type="text" id="bg_odd" name="bg_odd" class="np-ehl-color-field" value="<?php echo esc_attr( $settings['bg_odd'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bg_even">Even row background</label></th>
                        <td><input type="text" id="bg_even" name="bg_even" class="np-ehl-color-field" value="<?php echo esc_attr( $settings['bg_even'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="past_count">Past events to show</label></th>
                        <td>
                            <input type="number" id="past_count" name="past_count" min="0" max="<?php echo esc_attr( self::MAX_ROWS ); ?>" value="<?php echo esc_attr( $settings['past_count'] ); ?>" />
                            <p class="description">Maximum <?php echo esc_html( self::MAX_ROWS ); ?>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="future_count">Upcoming events to show</label></th>
                        <td>
                            <input type="number" id="future_count" name="future_count" min="0" max="<?php echo esc_attr( self::MAX_ROWS ); ?>" value="<?php echo esc_attr( $settings['future_count'] ); ?>" />
                            <p class="description">Maximum <?php echo esc_html( self::MAX_ROWS ); ?>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="date_format">Date format</label></th>
                        <td>
                            <input type="text" id="date_format" name="date_format" value="<?php echo esc_attr( $settings['date_format'] ); ?>" />
                            <p class="description">PHP date format, e.g. <code>M j, Y</code> for "Sep 27, 2026". See the <a href="https://wordpress.org/documentation/article/customize-date-and-time-format/" target="_blank" rel="noopener noreferrer">WordPress date format reference</a>.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="np_ehl_save" class="button button-primary" value="Save Settings" />
                </p>
            </form>

            <hr />
            <h2>Using the shortcode</h2>
            <p>Add <code>[np_event_home_list]</code> to any page (e.g. your home page) or drop it into a Shortcode block. It will use the settings above by default. To override on a single instance, pass attributes, for example:</p>
            <p><code>[np_event_home_list bg_odd="#ffffff" bg_even="#eef6ff" past_count="3" future_count="5"]</code></p>
        </div>
        <?php
    }

    /* ---------------------------------------------------------------------
     * Data
     * ------------------------------------------------------------------ */

    private function get_location( $event_id ) {

        $venue_id = get_post_meta( $event_id, '_EventVenueID', true );
        if ( ! $venue_id ) {
            return '';
        }

        if ( function_exists( 'tribe_get_venue' ) ) {
            $venue = tribe_get_venue( $event_id );
            if ( $venue ) {
                return $venue;
            }
        }

        $venue_post = get_post( $venue_id );
        return $venue_post ? $venue_post->post_title : '';
    }

    private function get_display_date( $event_id, $format ) {

        if ( function_exists( 'tribe_get_start_date' ) ) {
            $date = tribe_get_start_date( $event_id, false, $format );
            if ( $date ) {
                return $date;
            }
        }

        $start = get_post_meta( $event_id, '_EventStartDate', true );
        return $start ? date_i18n( $format, strtotime( $start ) ) : '';
    }

    /**
     * @param string $direction 'past' or 'future'
     */
    private function query_events( $direction, $count ) {

        if ( $count < 1 ) {
            return array();
        }

        $now = current_time( 'mysql' );

        $args = array(
            'post_type'      => 'tribe_events',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'meta_key'       => '_EventStartDate',
            'orderby'        => 'meta_value',
            'meta_query'     => array(
                array(
                    'key'     => '_EventStartDate',
                    'value'   => $now,
                    'compare' => 'past' === $direction ? '<' : '>=',
                    'type'    => 'DATETIME',
                ),
            ),
        );

        if ( 'past' === $direction ) {
            // Nearest-to-now past events first, so the LIMIT keeps the most recent ones.
            $args['order'] = 'DESC';
        } else {
            $args['order'] = 'ASC';
        }

        $query = new WP_Query( $args );
        $ids   = wp_list_pluck( $query->posts, 'ID' );

        if ( 'past' === $direction ) {
            // Re-sort chronologically (oldest to most recent) for natural reading order.
            $ids = array_reverse( $ids );
        }

        return $ids;
    }

    /* ---------------------------------------------------------------------
     * Shortcode
     * ------------------------------------------------------------------ */

    public function render_shortcode( $atts ) {

        $settings = $this->get_settings();

        $atts = shortcode_atts( array(
            'bg_odd'       => $settings['bg_odd'],
            'bg_even'      => $settings['bg_even'],
            'past_count'   => $settings['past_count'],
            'future_count' => $settings['future_count'],
            'date_format'  => $settings['date_format'],
            'show_labels'  => 'yes',
        ), $atts, 'np_event_home_list' );

        $past_count   = min( self::MAX_ROWS, max( 0, absint( $atts['past_count'] ) ) );
        $future_count = min( self::MAX_ROWS, max( 0, absint( $atts['future_count'] ) ) );
        $bg_odd       = sanitize_hex_color( $atts['bg_odd'] ) ?: $settings['bg_odd'];
        $bg_even      = sanitize_hex_color( $atts['bg_even'] ) ?: $settings['bg_even'];
        $show_labels  = ( 'no' !== $atts['show_labels'] );

        $past_ids   = $this->query_events( 'past', $past_count );
        $future_ids = $this->query_events( 'future', $future_count );

        if ( empty( $past_ids ) && empty( $future_ids ) ) {
            return '<p class="np-ehl-empty">No events to display.</p>';
        }

        ob_start();

        $this->print_style_once();

        printf(
            '<div class="np-ehl-grid" style="--np-ehl-bg-odd:%s;--np-ehl-bg-even:%s;">',
            esc_attr( $bg_odd ),
            esc_attr( $bg_even )
        );

        if ( ! empty( $past_ids ) ) {
            if ( $show_labels ) {
                echo '<div class="np-ehl-heading">Recent Events</div>';
            }
            $this->render_rows( $past_ids, $atts['date_format'] );
        }

        if ( ! empty( $future_ids ) ) {
            if ( $show_labels ) {
                echo '<div class="np-ehl-heading">Upcoming Events</div>';
            }
            $this->render_rows( $future_ids, $atts['date_format'] );
        }

        echo '</div>';

        return ob_get_clean();
    }

    private function render_rows( $ids, $date_format ) {

        $i = 0;
        foreach ( $ids as $event_id ) {

            $row_class = ( 0 === $i % 2 ) ? 'np-ehl-row-odd' : 'np-ehl-row-even';
            $title     = get_the_title( $event_id );
            $location  = $this->get_location( $event_id );
            $date      = $this->get_display_date( $event_id, $date_format );
            $permalink = get_permalink( $event_id );

            printf(
                '<a class="np-ehl-row %1$s" href="%2$s">
                    <span class="np-ehl-title">%3$s</span>
                    <span class="np-ehl-location">%4$s</span>
                    <span class="np-ehl-date">%5$s</span>
                </a>',
                esc_attr( $row_class ),
                esc_url( $permalink ),
                esc_html( $title ),
                esc_html( $location ),
                esc_html( $date )
            );

            $i++;
        }
    }

    private function print_style_once() {

        if ( $this->style_printed ) {
            return;
        }
        $this->style_printed = true;
        ?>
        <style>
            .np-ehl-grid {
                display: flex;
                flex-direction: column;
                border: 1px solid #e2e2e2;
                border-radius: 4px;
                overflow: hidden;
                font-family: inherit;
                margin: 1.5em 0;
            }
            .np-ehl-heading {
                font-weight: 700;
                font-size: 0.8em;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #888;
                padding: 10px 16px 4px;
            }
            .np-ehl-row {
                display: grid;
                grid-template-columns: 2fr 1.3fr 1fr;
                gap: 12px;
                align-items: center;
                padding: 10px 16px;
                text-decoration: none;
                color: inherit;
            }
            .np-ehl-row-odd  { background: var(--np-ehl-bg-odd, #ffffff); }
            .np-ehl-row-even { background: var(--np-ehl-bg-even, #f5f5f5); }
            .np-ehl-row:hover { filter: brightness(0.96); }
            .np-ehl-title { font-weight: 600; }
            .np-ehl-location,
            .np-ehl-date {
                font-size: 0.9em;
                color: #555;
            }
            .np-ehl-date { text-align: right; }
            @media (max-width: 600px) {
                .np-ehl-row {
                    grid-template-columns: 1fr;
                    gap: 2px;
                    padding: 10px 16px;
                }
                .np-ehl-date { text-align: left; }
            }
        </style>
        <?php
    }
}

new NP_Event_Home_List();
