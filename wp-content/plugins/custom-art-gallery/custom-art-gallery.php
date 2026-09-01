<?php
/**
 * Plugin Name: Custom Art Gallery UI (Modular Architecture)
 * Description: A modular art gallery plugin featuring Swiper.js, SimpleLightbox, ADA compliance mapping, and dynamic ACF controls.
 * Version: 3.0.0
 * Author: Your Gemini Assistant
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CAG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core files
require_once CAG_PLUGIN_DIR . 'includes/class-cag-cpt.php';
require_once CAG_PLUGIN_DIR . 'includes/class-cag-acf.php';
require_once CAG_PLUGIN_DIR . 'includes/class-cag-shortcode.php';

// Enqueue separated scripts and styles
add_action( 'wp_enqueue_scripts', 'cag_enqueue_modular_assets' );
function cag_enqueue_modular_assets() {
    // External Libraries
    wp_enqueue_style( 'swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css' );
    wp_enqueue_script( 'swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', array(), null, true );
    wp_enqueue_style( 'simplelightbox-css', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.0/simple-lightbox.min.css' );
    wp_enqueue_script( 'simplelightbox-js', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.0/simple-lightbox.min.js', array('jquery'), null, true );

    // Plugin Custom Assets
    wp_enqueue_style( 'cag-custom-style', CAG_PLUGIN_URL . 'assets/css/cag-style.css', array(), '3.0.0' );
    wp_enqueue_script( 'cag-custom-script', CAG_PLUGIN_URL . 'assets/js/cag-script.js', array('jquery', 'swiper-js', 'simplelightbox-js'), '3.0.0', true );
}