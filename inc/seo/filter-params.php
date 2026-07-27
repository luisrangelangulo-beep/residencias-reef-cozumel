<?php
/**
 * Filtered-view indexing guard (portfolio pattern, AIOSEO-adapted — this
 * site's SEO plugin disables core wp_robots and owns robots/canonical via
 * its own filters).
 *
 * Any archive/taxonomy URL carrying a recognized filter parameter
 * (?bedrooms=…, ?collection=…, ?guests=…) is a browse STATE, not a page:
 * it renders for the visitor but goes noindex,follow with a canonical
 * pointing at the clean URL, so filters add zero crawlable surface
 * (audit RRC-017).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Central registry: every parameter the filter bars/router understand. */
function lvc_filter_param_names() {
	$taxes = array_keys( (array) lvc_config( 'taxonomies', array() ) );
	return array_merge( $taxes, array( 'guests', 'beds', 'arrival', 'departure', 'vp' ) );
}

/** True when the current request carries any recognized filter parameter. */
function lvc_request_is_filtered_view() {
	foreach ( lvc_filter_param_names() as $param ) {
		if ( isset( $_GET[ $param ] ) && '' !== $_GET[ $param ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}
	}
	return false;
}

/*
 * Registered at file load, not in a template — AIOSEO memoizes output early,
 * so template-time registration can miss the run (same class of lesson as
 * the Rank Math canonical memoization on sibling sites).
 */
add_filter( 'aioseo_robots_meta', function ( $attributes ) {
	if ( ( is_archive() || is_post_type_archive() || is_tax() || is_home() ) && lvc_request_is_filtered_view() ) {
		$attributes['noindex']  = 'noindex';
		$attributes['nofollow'] = '';
	}
	return $attributes;
}, 40 );

add_filter( 'aioseo_canonical_url', function ( $url ) {
	if ( ( is_archive() || is_post_type_archive() || is_tax() || is_home() ) && lvc_request_is_filtered_view() ) {
		$clean = remove_query_arg( lvc_filter_param_names(), $url ? $url : home_url( add_query_arg( array() ) ) );
		return $clean;
	}
	return $url;
}, 40 );
