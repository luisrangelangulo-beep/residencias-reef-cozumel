<?php
/**
 * Luxury Villa Theme Core — bootstrap / loader
 * ─────────────────────────────────────────────────────────────────────────
 * Loads the brand config + modular includes, enqueues parent + brand styles,
 * and wires theme-wide hooks. Keep this file thin: feature logic lives in inc/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LVC_DIR', get_stylesheet_directory() );
define( 'LVC_URI', get_stylesheet_directory_uri() );
define( 'LVC_VERSION', '0.1.0' );

// Brand config first — everything else reads from it.
require_once LVC_DIR . '/theme-config.php';

// Modular includes (only those that exist load, so partial pulls are safe).
foreach ( array(
	'inc/helpers.php',
	'inc/cpt/register-property.php',
	'inc/property/data.php',
	'inc/property/fields.php',
	'inc/property/term-fields.php',
	'inc/property/area-lander-map.php',
	'inc/inquiry/ajax-handler.php',
	'inc/conversion/whatsapp-float.php',
	'inc/conversion/inquiry-frontend.php',
	'inc/sync/rest-sync.php',
	'inc/seo/schema.php',
	'inc/template-router.php',
) as $lvc_relative ) {
	$lvc_path = LVC_DIR . '/' . $lvc_relative;
	if ( file_exists( $lvc_path ) ) {
		require_once $lvc_path;
	}
}

/**
 * Styles: parent (Hello Elementor) + optional per-brand stylesheet.
 * The core ships NO CSS; create assets/brand.css per site (see docs/TOKEN_CONTRACT.md).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'hello-elementor', get_template_directory_uri() . '/style.css', array(), LVC_VERSION );

	if ( file_exists( LVC_DIR . '/assets/brand.css' ) ) {
		wp_enqueue_style( 'lvc-brand', LVC_URI . '/assets/brand.css', array( 'hello-elementor' ), (string) filemtime( LVC_DIR . '/assets/brand.css' ) );
	}

	if ( file_exists( LVC_DIR . '/assets/editorial.css' ) ) {
		wp_enqueue_style( 'lvc-editorial', LVC_URI . '/assets/editorial.css', array( 'lvc-brand' ), (string) filemtime( LVC_DIR . '/assets/editorial.css' ) );
	}

	if ( file_exists( LVC_DIR . '/assets/hero-overrides.css' ) ) {
		wp_enqueue_style( 'lvc-hero-overrides', LVC_URI . '/assets/hero-overrides.css', array( 'lvc-brand' ), (string) filemtime( LVC_DIR . '/assets/hero-overrides.css' ) );
	}
}, 20 );

// Theme supports + primary nav menu (set the menu in Appearance → Menus).
add_action( 'after_setup_theme', function () {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	register_nav_menus( array( 'primary' => 'Primary Navigation' ) );
} );

// Let templates own page output (Hello Elementor wrappers/title off).
add_filter( 'hello_elementor_page_title', '__return_false' );

// Flush rewrites when the theme is activated so CPT/taxonomy URLs resolve.
add_action( 'after_switch_theme', 'flush_rewrite_rules' );
