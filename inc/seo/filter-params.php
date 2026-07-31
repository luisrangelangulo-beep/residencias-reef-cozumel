<?php
/**
 * Filtered-view indexing guard.
 *
 * Filters use private query parameters (`filter_area`, `filter_bedrooms`, and
 * so on) so WordPress never mistakes a filter for the request's primary
 * taxonomy. Every filtered or custom-paginated state remains usable for
 * visitors while receiving noindex,follow and a canonical to its clean URL.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Taxonomy => non-public query parameter used by the filter UI. */
function lvc_filter_taxonomy_param_map() {
	$map = array();
	foreach ( array_keys( (array) lvc_config( 'taxonomies', array() ) ) as $taxonomy ) {
		$map[ $taxonomy ] = 'filter_' . $taxonomy;
	}
	return $map;
}

/** Return the safe filter parameter for a taxonomy. */
function lvc_filter_param_for_taxonomy( $taxonomy ) {
	$map = lvc_filter_taxonomy_param_map();
	return isset( $map[ $taxonomy ] ) ? $map[ $taxonomy ] : 'filter_' . sanitize_key( $taxonomy );
}

/** Every current and legacy parameter that must be stripped from canonicals. */
function lvc_filter_param_names() {
	$taxes = array_keys( (array) lvc_config( 'taxonomies', array() ) );
	return array_values(
		array_unique(
			array_merge(
				$taxes,
				array_values( lvc_filter_taxonomy_param_map() ),
				array( 'guests', 'beds', 'arrival', 'departure', 'vp' )
			)
		)
	);
}

/** True when the current request carries a recognized filter parameter. */
function lvc_request_is_filtered_view() {
	foreach ( lvc_filter_param_names() as $param ) {
		if ( isset( $_GET[ $param ] ) && '' !== $_GET[ $param ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}
	}
	return false;
}

/**
 * Preserve old shared filter URLs without allowing taxonomy query-var hijacks.
 *
 * Only a taxonomy variable supplied in the query string is removed. A taxonomy
 * variable created by the path rewrite (for example /collections/beachfront/)
 * is left intact.
 */
add_filter( 'request', function ( $query_vars ) {
	foreach ( lvc_filter_taxonomy_param_map() as $taxonomy => $filter_param ) {
		if ( isset( $_GET[ $taxonomy ] ) && '' !== $_GET[ $taxonomy ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_vars[ $filter_param ] = sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			unset( $query_vars[ $taxonomy ] );
		}
	}
	return $query_vars;
}, 1 );

add_filter( 'query_vars', function ( $vars ) {
	return array_values(
		array_unique(
			array_merge( $vars, array_values( lvc_filter_taxonomy_param_map() ), array( 'guests', 'beds', 'arrival', 'departure', 'vp' ) )
		)
	);
} );

/** True on a route where the theme intentionally supports filter states. */
function lvc_is_filterable_view() {
	if ( is_archive() || is_post_type_archive() || is_tax() || is_home() ) {
		return true;
	}
	if ( ! is_page() ) {
		return false;
	}

	$page_id  = get_queried_object_id();
	$template = $page_id ? get_page_template_slug( $page_id ) : '';
	if ( 'page-templates/area-lander.php' === $template ) {
		return true;
	}

	$slug = $page_id ? get_post_field( 'post_name', $page_id ) : '';
	return $slug && function_exists( 'lvc_area_lander_map' ) && isset( lvc_area_lander_map()[ $slug ] );
}

/*
 * Registered at file load because AIOSEO can memoize its output before a
 * template runs.
 */
add_filter( 'aioseo_robots_meta', function ( $attributes ) {
	if ( lvc_is_filterable_view() && lvc_request_is_filtered_view() ) {
		$attributes['noindex']  = 'noindex';
		$attributes['nofollow'] = '';
	}
	return $attributes;
}, 40 );

add_filter( 'aioseo_canonical_url', function ( $url ) {
	if ( lvc_is_filterable_view() && lvc_request_is_filtered_view() ) {
		return remove_query_arg(
			lvc_filter_param_names(),
			$url ? $url : home_url( add_query_arg( array() ) )
		);
	}
	return $url;
}, 40 );
