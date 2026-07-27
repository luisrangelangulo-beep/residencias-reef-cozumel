<?php
/**
 * Off-market enforcement (audit RRC-004 — "no off-market lifecycle").
 *
 * The sync accepts an `off_market` field ("1" = off market). This file is
 * the single enforcement point (ported from the Republic implementation,
 * sitemap/robots adapted to AIOSEO — this site's SEO plugin, which disables
 * core wp_robots and owns the meta via `aioseo_robots_meta`):
 *
 *  - every front-end villa query excludes off_market = 1 (main + custom
 *    WP_Query both pass through pre_get_posts), EXCEPT single resolution —
 *    the URL stays live by design ("never 404");
 *  - the AIOSEO sitemap drops off-market units;
 *  - the single page goes noindex,follow while remaining reachable;
 *  - lvc_active_villa_count() / lvc_active_villa_count_for_term() give
 *    every "N villas" claim an off-market-aware number, with transient
 *    invalidation on villa saves and off_market meta changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_is_off_market' ) ) {
	function lvc_is_off_market( $post_id = 0 ) {
		$post_id = $post_id ?: get_the_ID();
		return '1' === (string) get_post_meta( (int) $post_id, 'off_market', true );
	}
}

add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() ) {
		return;
	}
	$cpt   = function_exists( 'lvc_config' ) ? lvc_config( 'cpt', 'villas' ) : 'villas';
	$types = (array) $q->get( 'post_type' );
	/*
	 * Taxonomy-archive main queries carry an EMPTY post_type at this hook
	 * (WordPress resolves it later), so matching on post_type alone would
	 * let off-market units back into area/bedroom/collection archives.
	 */
	$is_villa_query = in_array( $cpt, $types, true );
	if ( ! $is_villa_query && $q->is_main_query() && $q->is_tax() ) {
		$villa_taxes = array_keys( (array) ( function_exists( 'lvc_config' ) ? lvc_config( 'taxonomies', array() ) : array() ) );
		foreach ( $villa_taxes as $vt ) {
			if ( $q->is_tax( $vt ) ) {
				$is_villa_query = true;
				break;
			}
		}
	}
	if ( ! $is_villa_query ) {
		return;
	}
	// Single resolution (permalink, previews, explicit IDs) stays untouched —
	// the off-market URL must keep resolving.
	if ( $q->is_singular() || $q->get( 'name' ) || $q->get( 'p' ) || $q->get( 'post__in' ) ) {
		return;
	}
	$meta   = (array) $q->get( 'meta_query' );
	$meta[] = array(
		'relation' => 'OR',
		array( 'key' => 'off_market', 'compare' => 'NOT EXISTS' ),
		array( 'key' => 'off_market', 'value' => '1', 'compare' => '!=' ),
	);
	$q->set( 'meta_query', $meta );
} );

// AIOSEO sitemap: drop off-market units from the villas sitemap.
add_filter( 'aioseo_sitemap_posts', function ( $entries, $post_type ) {
	$cpt = function_exists( 'lvc_config' ) ? lvc_config( 'cpt', 'villas' ) : 'villas';
	if ( $post_type !== $cpt || ! is_array( $entries ) ) {
		return $entries;
	}
	return array_values( array_filter( $entries, function ( $entry ) {
		$loc = is_array( $entry ) ? ( $entry['loc'] ?? '' ) : ( $entry->loc ?? '' );
		if ( ! $loc ) {
			return true;
		}
		$pid = url_to_postid( $loc );
		return ! ( $pid && lvc_is_off_market( $pid ) );
	} ) );
}, 10, 2 );

// AIOSEO owns robots output (disableWpRobotsCore) — noindex,follow the
// off-market single via its attributes filter.
add_filter( 'aioseo_robots_meta', function ( $attributes ) {
	$cpt = function_exists( 'lvc_config' ) ? lvc_config( 'cpt', 'villas' ) : 'villas';
	if ( is_singular( $cpt ) && lvc_is_off_market() ) {
		$attributes['noindex']  = 'noindex';
		$attributes['nofollow'] = '';
	}
	return $attributes;
}, 20 );

if ( ! function_exists( 'lvc_purge_active_count_transients' ) ) {
	/**
	 * Invalidate cached active-inventory counts for one unit. Without this,
	 * a page-cache purge right after an off-market toggle bakes the stale
	 * hour-long transient into the freshly cached page.
	 */
	function lvc_purge_active_count_transients( $post_id ) {
		delete_transient( 'lvc_active_villa_count' );
		foreach ( (array) get_object_taxonomies( get_post_type( $post_id ) ) as $tax ) {
			$tids = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $tids ) ) {
				continue;
			}
			foreach ( (array) $tids as $tid ) {
				delete_transient( 'lvc_active_count_term_' . (int) $tid );
			}
		}
	}
}

add_action( 'save_post', function ( $post_id, $post ) {
	$cpt = function_exists( 'lvc_config' ) ? lvc_config( 'cpt', 'villas' ) : 'villas';
	if ( $post instanceof WP_Post && $cpt === $post->post_type ) {
		lvc_purge_active_count_transients( $post_id );
	}
}, 10, 2 );

// ACF update_field() writes meta WITHOUT firing save_post — an off_market
// toggle through the sync must still invalidate.
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $lvc_meta_hook ) {
	add_action( $lvc_meta_hook, function ( $meta_id, $post_id, $meta_key ) {
		if ( 'off_market' === $meta_key ) {
			lvc_purge_active_count_transients( $post_id );
		}
	}, 10, 3 );
}

if ( ! function_exists( 'lvc_active_villa_count' ) ) {
	/** Published units EXCLUDING off-market — for every "N villas" claim. */
	function lvc_active_villa_count() {
		$count = get_transient( 'lvc_active_villa_count' );
		if ( false !== $count ) {
			return (int) $count;
		}
		$cpt = function_exists( 'lvc_config' ) ? lvc_config( 'cpt', 'villas' ) : 'villas';
		$q   = new WP_Query( array(
			'post_type'      => $cpt,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => 'off_market', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'off_market', 'value' => '1', 'compare' => '!=' ),
			),
		) );
		$count = (int) $q->found_posts;
		set_transient( 'lvc_active_villa_count', $count, HOUR_IN_SECONDS );
		return $count;
	}
}

if ( ! function_exists( 'lvc_active_villa_count_for_term' ) ) {
	/** Active count for a term — WP_Term->count is a raw DB count. */
	function lvc_active_villa_count_for_term( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return 0;
		}
		$key   = 'lvc_active_count_term_' . (int) $term->term_id;
		$count = get_transient( $key );
		if ( false !== $count ) {
			return (int) $count;
		}
		$cpt = function_exists( 'lvc_config' ) ? lvc_config( 'cpt', 'villas' ) : 'villas';
		$q   = new WP_Query( array(
			'post_type'      => $cpt,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => (int) $term->term_id ),
			),
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => 'off_market', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'off_market', 'value' => '1', 'compare' => '!=' ),
			),
		) );
		$count = (int) $q->found_posts;
		set_transient( $key, $count, HOUR_IN_SECONDS );
		return $count;
	}
}
