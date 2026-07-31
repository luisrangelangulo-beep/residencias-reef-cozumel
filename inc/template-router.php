<?php
/**
 * Luxury Villa Theme Core — template router.
 * ─────────────────────────────────────────────────────────────────────────
 * Maps the configured property CPT + its taxonomies to the GENERIC template
 * parts, so the same files work no matter the CPT slug (villa/chalet/condo).
 * No need to rename single-{cpt}.php / archive-{cpt}.php per brand.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'single_template', 'lvc_route_single' );
function lvc_route_single( $template ) {
	if ( is_singular( lvc_config( 'cpt', 'villa' ) ) ) {
		// If the brand ships a dedicated single-{cpt}.php, let WP's normal
		// template hierarchy use it instead of the generic part.
		if ( locate_template( 'single-' . lvc_config( 'cpt', 'villa' ) . '.php' ) ) {
			return $template;
		}
		$part = LVC_DIR . '/template-parts/property-single.php';
		if ( file_exists( $part ) ) {
			return $part;
		}
	}
	return $template;
}

add_filter( 'archive_template', 'lvc_route_archive' );
function lvc_route_archive( $template ) {
	if ( is_post_type_archive( lvc_config( 'cpt', 'villa' ) ) ) {
		$part = LVC_DIR . '/template-parts/property-archive.php';
		if ( file_exists( $part ) ) {
			return $part;
		}
	}
	return $template;
}

add_filter( 'taxonomy_template', 'lvc_route_taxonomy' );
function lvc_route_taxonomy( $template ) {
	$obj = get_queried_object();
	if ( $obj instanceof WP_Term && array_key_exists( $obj->taxonomy, (array) lvc_config( 'taxonomies', array() ) ) ) {
		$part = LVC_DIR . '/template-parts/term-archive.php';
		if ( file_exists( $part ) ) {
			return $part;
		}
	}
	return $template;
}

/**
 * Apply sanitized GET filters on the property archive (filter bar support).
 * Only touches the main query on the front-end CPT archive.
 */
/**
 * Apply sanitized GET filters on the property archive AND on villa taxonomy
 * archives (portfolio pattern — filtered views stay noindexed with a clean
 * canonical via inc/seo/filter-params.php, so this adds no crawl surface).
 */
add_action( 'pre_get_posts', 'lvc_archive_filters' );
function lvc_archive_filters( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$taxes  = array_keys( (array) lvc_config( 'taxonomies', array() ) );
	$on_tax = $q->is_tax( $taxes );
	if ( ! $q->is_post_type_archive( lvc_config( 'cpt', 'villa' ) ) && ! $on_tax ) {
		return;
	}

	// Shallower pagination = shallower crawl depth for the 150-villa archive
	// (10/page = 15 pages; 30/page = 5). Helps deep villas get discovered.
	$q->set( 'posts_per_page', 30 );

	// APPEND to any existing clauses — overwriting here would clobber what
	// other pre_get_posts hooks (the off-market exclusion) already added.
	$tax_query = (array) $q->get( 'tax_query' );
	foreach ( $taxes as $tax ) {
		if ( $on_tax && $q->get( $tax ) ) {
			continue; // The page's own term comes from the URL, not the filter bar.
		}
		$filter_param = function_exists( 'lvc_filter_param_for_taxonomy' ) ? lvc_filter_param_for_taxonomy( $tax ) : 'filter_' . $tax;
		$filter_value = get_query_var( $filter_param );
		if ( ! $filter_value && ! empty( $_GET[ $filter_param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter_value = wp_unslash( $_GET[ $filter_param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( $filter_value ) {
			$tax_query[] = array(
				'taxonomy' => $tax,
				'field'    => 'slug',
				'terms'    => sanitize_title( $filter_value ),
			);
		}
	}
	if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
		$tax_query['relation'] = 'AND';
	}
	if ( $tax_query ) {
		$q->set( 'tax_query', $tax_query );
	}
}
