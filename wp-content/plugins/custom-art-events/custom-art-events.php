<?php
/**
 * Plugin Name: Custom Art Events UI (Modular)
 * Description: Creates a custom post type for Events, ACF fields for date/location/registration, and a shortcode for grid display.
 * Version: 2.0.0
 * Author: Your Gemini Assistant
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define Plugin Paths
define( 'CAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core modular files
require_once CAE_PLUGIN_DIR . 'includes/class-cae-cpt.php';
require_once CAE_PLUGIN_DIR . 'includes/class-cae-acf.php';
require_once CAE_PLUGIN_DIR . 'includes/class-cae-shortcode.php';

// Enqueue separated CSS
add_action( 'wp_enqueue_scripts', 'cae_enqueue_modular_assets' );
function cae_enqueue_modular_assets() {
    wp_enqueue_style( 'cae-custom-style', CAE_PLUGIN_URL . 'assets/css/cae-style.css', array(), '2.0.0' );
}