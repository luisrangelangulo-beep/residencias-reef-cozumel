<?php
/**
 * Luxury Villa Theme Core — property data helpers.
 * Related-property resolution with an area → destination fallback ladder,
 * and a rate-tier → Schema.org priceRange map.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Similar properties for a given property: same area first, then same
 * destination, excluding the current one. Returns an array of post IDs.
 */
if ( ! function_exists( 'lvc_related_properties' ) ) {
	function lvc_related_properties( $post_id, $limit = 3 ) {
		$cpt = lvc_config( 'cpt', 'villa' );

		foreach ( array( 'area', 'destination' ) as $tax ) {
			$terms = get_the_terms( $post_id, $tax );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			$ids = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			$q   = new WP_Query( array(
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				'post__not_in'   => array( (int) $post_id ),
				'orderby'        => 'rand',
				'fields'         => 'ids',
				'tax_query'      => array( array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $ids ) ),
			) );
			if ( $q->have_posts() ) {
				return $q->posts;
			}
		}
		return array();
	}
}

/**
 * Properties related to a magazine post by shared area/destination terms.
 */
if ( ! function_exists( 'lvc_related_properties_for_post' ) ) {
	function lvc_related_properties_for_post( $post_id, $limit = 2 ) {
		$cpt = lvc_config( 'cpt', 'villa' );

		foreach ( array( 'destination', 'area' ) as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$ids = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $ids ) || ! $ids ) {
				continue;
			}
			$q = new WP_Query( array(
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				'orderby'        => 'rand',
				'fields'         => 'ids',
				'tax_query'      => array( array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $ids ) ),
			) );
			if ( $q->have_posts() ) {
				return $q->posts;
			}
		}
		return array();
	}
}

/**
 * Map a rate-tier slug to a Schema.org priceRange shorthand.
 */
if ( ! function_exists( 'lvc_price_range' ) ) {
	function lvc_price_range( $tier ) {
		$map = array(
			'under-5k' => '$',
			'5k-10k'   => '$$',
			'10k-20k'  => '$$$',
			'20k-plus' => '$$$$',
		);
		return $map[ (string) $tier ] ?? '$$';
	}
}
