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
	'inc/property/homepage-fields.php',
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
 * Resolve an area term to its flat commercial area lander when one exists.
 * Falls back to the native term archive only when the term has no mapped lander.
 */
if ( ! function_exists( 'lvc_area_lander_url_by_term' ) ) {
	function lvc_area_lander_url_by_term( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return home_url( '/' );
		}

		if ( function_exists( 'lvc_area_lander_map' ) ) {
			$map       = lvc_area_lander_map();
			$page_slug = array_search( $term->slug, $map, true );
			if ( $page_slug ) {
				$page = get_page_by_path( $page_slug );
				if ( $page ) {
					return get_permalink( $page );
				}
				return home_url( '/' . trim( $page_slug, '/' ) . '/' );
			}
		}

		$link = get_term_link( $term );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
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

	if ( file_exists( LVC_DIR . '/assets/motion-effects.css' ) ) {
		wp_enqueue_style( 'lvc-motion-effects', LVC_URI . '/assets/motion-effects.css', array( 'lvc-brand', 'lvc-hero-overrides' ), (string) filemtime( LVC_DIR . '/assets/motion-effects.css' ) );
	}
}, 20 );

/**
 * Force important SEO/CRO pages to theme-owned templates.
 *
 * Some live pages were originally built with Elementor or assigned custom page
 * templates, so page.php routing can be bypassed. template_include runs after
 * WordPress has resolved that choice, letting us protect revenue pages from
 * falling back to old thin layouts.
 */
add_filter( 'template_include', function ( $template ) {
	if ( ! is_page() ) {
		return $template;
	}

	$page_id = get_queried_object_id();
	$slug    = $page_id ? get_post_field( 'post_name', $page_id ) : '';

	$forced = array(
		'magazine'                    => 'page-templates/magazine.php',
		'riviera-maya-villa-rentals'  => 'page-templates/riviera-maya-villa-rentals.php',
	);

	if ( isset( $forced[ $slug ] ) ) {
		$forced_template = locate_template( $forced[ $slug ] );
		if ( $forced_template ) {
			return $forced_template;
		}
	}

	if ( function_exists( 'lvc_area_lander_map' ) && $slug ) {
		$map = lvc_area_lander_map();
		if ( isset( $map[ $slug ] ) ) {
			$area_template = locate_template( 'page-templates/area-lander.php' );
			if ( $area_template ) {
				return $area_template;
			}
		}
	}

	return $template;
}, 99 );

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
