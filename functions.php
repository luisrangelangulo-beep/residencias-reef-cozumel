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
	'inc/inquiry/cpt-inquiry.php',
	'inc/inquiry/ajax-handler.php',
	'inc/conversion/float-actions.php',
	'inc/conversion/inquiry-frontend.php',
	'inc/sync/rest-sync.php',
	'inc/seo/schema.php',
	'inc/seo/legacy-redirects.php',
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

/* ── Remote image width (cached) ─────────────────────────────────────────
 * One-time measurement per unique URL, cached permanently on success. Used
 * by hero selection to refuse sources too small for full-bleed display. On
 * fetch failure the width is unknown (0) — callers treat unknown as
 * acceptable, so a network hiccup never demotes a real photo; the failure
 * is retried after a day rather than cached forever.
 */

/* Image hosts the width-measurement helper may contact. */
if ( ! function_exists( 'lvc_image_host_allowed' ) ) {
	function lvc_image_host_allowed( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $host ) {
			return false;
		}
		$site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$allowed_exact    = array( $site_host, 'www.' . $site_host, 'images.' . $site_host, 'images.rmoceanfrontrentals.com' );
		$allowed_suffixes = array( '.r2.dev', '.wp.com' );
		if ( in_array( $host, $allowed_exact, true ) ) {
			return true;
		}
		foreach ( $allowed_suffixes as $suffix ) {
			if ( strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'lvc_remote_image_width' ) ) {
	function lvc_remote_image_width( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}
		// SSRF guard: measurement performs a real HTTP fetch, so only hosts we
		// actually store imagery on are ever contacted. Anything else (typo'd
		// import data, injected URLs, internal addresses) returns unknown
		// without network I/O.
		if ( ! lvc_image_host_allowed( $url ) ) {
			return 0;
		}
		$key   = 'lvc_imgw_' . md5( $url );
		$known = get_option( $key, false );
		if ( false !== $known ) {
			return (int) $known;
		}
		if ( get_transient( $key . '_fail' ) ) {
			return 0;
		}
		$size = @getimagesize( $url );
		if ( is_array( $size ) && ! empty( $size[0] ) ) {
			add_option( $key, (int) $size[0], '', 'no' );
			return (int) $size[0];
		}
		set_transient( $key . '_fail', 1, DAY_IN_SECONDS );
		return 0;
	}
}

/*
 * WP Rocket's advanced-cache.php refuses to serve OR write page cache when
 * wp-content/cache/wp-rocket/ is missing, and nothing recreates the folder on
 * the front end - page caching dies silently site-wide (found live on all 7
 * portfolio sites 2026-07-25). Recreate on detection.
 */
add_action( 'init', function () {
	if ( defined( 'WP_ROCKET_ADVANCED_CACHE_PROBLEM' ) && defined( 'WP_ROCKET_CACHE_PATH' ) && ! is_dir( WP_ROCKET_CACHE_PATH ) ) {
		wp_mkdir_p( WP_ROCKET_CACHE_PATH );
	}
} );

/*
 * Edge-purge companion to the Cloudflare "cache-anonymous-html" rule: once
 * Cloudflare caches HTML at the edge, every WP Rocket purge (content edit,
 * villa sync, manual purge) must also clear the edge or visitors see stale
 * pages for the rule's TTL. Fire-and-forget so purging never slows admin.
 * Credentials live ONLY in DB options (lvc_cf_zone_id / lvc_cf_purge_token
 * - cache-purge-scoped token, never committed to the repo).
 */
add_action( 'after_rocket_clean_domain', function () {
	$zone  = (string) get_option( 'lvc_cf_zone_id', '' );
	$token = (string) get_option( 'lvc_cf_purge_token', '' );
	if ( '' === $zone || '' === $token ) {
		return;
	}
	wp_remote_post( 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $zone ) . '/purge_cache', [
		'timeout'  => 10,
		'blocking' => false,
		'headers'  => [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		],
		'body'     => '{"purge_everything":true}',
	] );
} );

/*
 * Per-post purges must reach the edge too: content updated outside a full
 * domain purge (sheet sync, quick edit) triggers Rocket's per-post purge —
 * without this hook the Cloudflare copy stayed stale for the cache rule's
 * full TTL. Purges the post's URL, the homepage, and its archive.
 */
add_action( 'after_rocket_clean_post', function ( $post ) {
	$zone  = (string) get_option( 'lvc_cf_zone_id', '' );
	$token = (string) get_option( 'lvc_cf_purge_token', '' );
	if ( '' === $zone || '' === $token || ! is_object( $post ) ) {
		return;
	}
	$urls = array_values( array_filter( array(
		get_permalink( $post ),
		home_url( '/' ),
		get_post_type_archive_link( get_post_type( $post ) ),
	) ) );
	wp_remote_post( 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $zone ) . '/purge_cache', array(
		'timeout'  => 10,
		'blocking' => false,
		'headers'  => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		),
		'body'     => wp_json_encode( array( 'files' => $urls ) ),
	) );
} );
