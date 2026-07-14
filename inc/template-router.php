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
add_action( 'pre_get_posts', 'lvc_archive_filters' );
function lvc_archive_filters( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( ! $q->is_post_type_archive( lvc_config( 'cpt', 'villa' ) ) ) {
		return;
	}

	// Shallower pagination = shallower crawl depth for the 150-villa archive
	// (10/page = 15 pages; 30/page = 5). Helps deep villas get discovered.
	$q->set( 'posts_per_page', 30 );

	$tax_query = array();
	foreach ( array_keys( (array) lvc_config( 'taxonomies', array() ) ) as $tax ) {
		if ( ! empty( $_GET[ $tax ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tax_query[] = array(
				'taxonomy' => $tax,
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET[ $tax ] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}
	}
	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}
	if ( $tax_query ) {
		$q->set( 'tax_query', $tax_query );
	}
}
