<?php
/**
 * Search architecture hardening.
 *
 * Keeps one public URL for every mapped area, preserves crawlable links through
 * noindexed pagination, and removes malformed custom-field images from AIOSEO
 * sitemaps.
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native /area/{term}/ archives duplicate the curated flat commercial pages.
 * Redirect mapped terms in one hop; unmapped terms remain native archives.
 */
add_action(
	'template_redirect',
	function () {
		if ( is_front_page() && is_paged() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}

		if ( is_admin() || ! is_tax( 'area' ) || ! function_exists( 'lvc_area_lander_map' ) ) {
			return;
		}

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$page_slug = array_search( $term->slug, lvc_area_lander_map(), true );
		if ( ! $page_slug ) {
			return;
		}

		wp_safe_redirect( home_url( '/' . trim( $page_slug, '/' ) . '/' ), 301 );
		exit;
	},
	0
);

/**
 * Mapped area terms must not be advertised as alternate URLs in AIOSEO.
 */
add_filter(
	'aioseo_sitemap_exclude_terms',
	function ( $term_ids ) {
		$term_ids = is_array( $term_ids ) ? $term_ids : array();
		if ( ! function_exists( 'lvc_area_lander_map' ) ) {
			return $term_ids;
		}

		foreach ( lvc_area_lander_map() as $term_slug ) {
			$term = get_term_by( 'slug', $term_slug, 'area' );
			if ( $term instanceof WP_Term ) {
				$term_ids[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( array_map( 'intval', $term_ids ) ) );
	}
);

/**
 * AIOSEO discovers raw gallery custom fields. Some legacy fields contain
 * multiple URLs or explanatory prose in one value, producing invalid
 * <image:loc> nodes. Keep only one valid, reasonably sized HTTP URL per item.
 */
add_filter(
	'aioseo_sitemap_images',
	function ( $images, $post = null ) {
		if ( ! is_array( $images ) ) {
			return array();
		}

		$valid = array();
		foreach ( $images as $image ) {
			if ( ! is_string( $image ) ) {
				continue;
			}

			$url = trim( html_entity_decode( $image, ENT_QUOTES, 'UTF-8' ) );
			if (
				'' === $url
				|| strlen( $url ) > 2048
				|| preg_match( '/[\r\n\t]/', $url )
				|| ! wp_http_validate_url( $url )
			) {
				continue;
			}

			$valid[] = esc_url_raw( $url );
		}

		return array_values( array_unique( array_filter( $valid ) ) );
	},
	20,
	2
);
