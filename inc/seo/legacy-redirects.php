<?php
/**
 * Legacy Residencias Reef unit URLs → canonical /villas/ pages.
 *
 * The pre-rebuild site exposed the five units at /residencias-reef-{unit}/
 * (plus loose variants). Those paths 404 today (audit RRC-010 — the
 * "template divergence" the auditor saw was actually a dead route behind a
 * stale cache). One-hop 301 by unit number, resolved against the LIVE post
 * so a future slug change cannot strand the redirect.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = strtolower( trim( (string) parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' ) );
	// residencias-reef-6100, residencias-reef-condo-6100, reef-condo-6100 …
	if ( ! preg_match( '#^(?:residencias-)?reef-(?:cozumel-)?(?:condo-)?(\d{4})$#', $path, $m ) ) {
		return;
	}
	$unit = $m[1];
	$hit  = get_posts( array(
		'post_type'      => 'villas',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		's'              => 'Residencias Reef ' . $unit,
	) );
	if ( ! $hit ) {
		// Fallback: slug scan (search can miss when the number is only in the slug).
		global $wpdb;
		$hit = $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'villas' AND post_status = 'publish' AND post_name LIKE %s LIMIT 1",
			'%' . $wpdb->esc_like( $unit ) . '%'
		) );
	}
	if ( $hit ) {
		wp_safe_redirect( get_permalink( (int) $hit[0] ), 301 );
		exit;
	}
} );
