<?php
/**
 * Inquiry front-end wiring: cache-safe nonce + submit script + analytics.
 * ─────────────────────────────────────────────────────────────────────────
 * The inquiry form's nonce is server-rendered, so full-page caching (WP Rocket /
 * Cloudflare) can serve a STALE nonce and every inquiry would fail "Security
 * check failed." Fix: a tiny REST endpoint returns a FRESH nonce, and theme.js
 * fetches it right before submit, POSTs via fetch to admin-ajax, shows status,
 * and fires a `generate_lead` analytics event (GA4 gtag + dataLayer) on success.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Cache-safe nonce endpoint — dynamic, never cached.
add_action( 'rest_api_init', function () {
	register_rest_route( 'lvc/v1', '/inquiry-nonce', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			return array( 'nonce' => wp_create_nonce( (string) lvc_config( 'inquiry_action', 'lvc_inquiry' ) ) );
		},
	) );
} );

// Front-end script (drawer toggle + inquiry submit). Footer, no dependencies.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! file_exists( LVC_DIR . '/assets/theme.js' ) ) {
		return;
	}
	$lvc_theme_ver = (string) filemtime( LVC_DIR . '/assets/theme.js' );
	wp_register_script( 'lvc-theme', LVC_URI . '/assets/theme.js', array(), $lvc_theme_ver, true );
	wp_localize_script( 'lvc-theme', 'LVC_INQ', array(
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonceUrl' => rest_url( 'lvc/v1/inquiry-nonce' ),
		'action'   => (string) lvc_config( 'inquiry_action', 'lvc_inquiry' ),
	) );
	wp_enqueue_script( 'lvc-theme' );
}, 25 );
