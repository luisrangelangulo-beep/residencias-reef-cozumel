<?php
/**
 * Luxury Villa Theme Core — Sheet → WordPress sync receiver.
 * ─────────────────────────────────────────────────────────────────────────
 * POST /wp-json/lvc/v1/sync   (the Google-Sheet connector pushes here)
 *
 * Auth:  header  X-LVC-Sync-Token  must equal the `lvc_sync_token` option
 *        (set once per site; never stored in the repo).
 * Body:  { "villas": [ { ...one villa's fields... }, ... ] }  (or a single object)
 *
 * Per villa it UPSERTS the CPT by slug (so re-running updates, never duplicates):
 *   - post at the `url` slug (preserves live ranking slugs), fallback community+lot+area
 *   - the 33 ACF fields (only those present in the payload)
 *   - taxonomy terms: destination, area, amenity (one per canonical token),
 *     collection (from travel_experience), bedrooms (from bed_count), catering
 *   - Rank Math title/description, FIFU featured image
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'lvc/v1', '/sync', array(
		'methods'             => 'POST',
		'permission_callback' => 'lvc_sync_auth',
		'callback'            => 'lvc_sync_handle',
	) );
	register_rest_route( 'lvc/v1', '/sync-ping', array(
		'methods'             => 'GET',
		'permission_callback' => 'lvc_sync_auth',
		'callback'            => function () {
			return array( 'ok' => true, 'cpt' => lvc_config( 'cpt', 'villa' ), 'brand' => lvc_brand() );
		},
	) );
} );

/** Shared-secret auth: header X-LVC-Sync-Token (or ?token=) vs the lvc_sync_token option. */
function lvc_sync_auth( $request ) {
	$token = (string) get_option( 'lvc_sync_token', '' );
	if ( '' === $token ) {
		return new WP_Error( 'lvc_no_token', 'Sync token not configured on this site.', array( 'status' => 503 ) );
	}
	$sent = (string) $request->get_header( 'x_lvc_sync_token' );
	if ( '' === $sent ) {
		$sent = (string) $request->get_param( 'token' );
	}
	if ( '' === $sent || ! hash_equals( $token, $sent ) ) {
		return new WP_Error( 'lvc_bad_token', 'Invalid sync token.', array( 'status' => 401 ) );
	}
	return true;
}

function lvc_sync_handle( WP_REST_Request $request ) {
	$body = (array) $request->get_json_params();

	$villas = array();
	if ( isset( $body['villas'] ) && is_array( $body['villas'] ) ) {
		$villas = $body['villas'];
	} elseif ( isset( $body['url'] ) || isset( $body['property_name'] ) ) {
		$villas = array( $body );
	}
	if ( ! $villas ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'No villas in payload.' ), 400 );
	}

	$results = array();
	foreach ( $villas as $v ) {
		$results[] = lvc_sync_upsert_villa( (array) $v );
	}
	$created = count( array_filter( $results, function ( $r ) { return ! empty( $r['ok'] ) && 'created' === ( $r['action'] ?? '' ); } ) );
	$updated = count( array_filter( $results, function ( $r ) { return ! empty( $r['ok'] ) && 'updated' === ( $r['action'] ?? '' ); } ) );

	return new WP_REST_Response( array(
		'ok'      => true,
		'count'   => count( $results ),
		'created' => $created,
		'updated' => $updated,
		'results' => $results,
	), 200 );
}

/* ── helpers ─────────────────────────────────────────────────────────────── */

function lvc_sync_val( $v, $key, $default = '' ) {
	return ( isset( $v[ $key ] ) && null !== $v[ $key ] ) ? $v[ $key ] : $default;
}

/** "private-pool" → "Private Pool", "golf-resort" → "Golf Resort". */
function lvc_sync_label( $token ) {
	return ucwords( str_replace( array( '-', '_' ), ' ', strtolower( trim( (string) $token ) ) ) );
}

/** Ensure a term exists (by slug) and assign it to the post. $append=false replaces. */
function lvc_sync_term( $post_id, $tax, $name, $append = true ) {
	$name = trim( (string) $name );
	if ( '' === $name || ! taxonomy_exists( $tax ) ) {
		return;
	}
	$slug = sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $tax );
	if ( $term ) {
		$tid = (int) $term->term_id;
	} else {
		$res = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			return;
		}
		$tid = (int) $res['term_id'];
	}
	wp_set_object_terms( $post_id, array( $tid ), $tax, $append );
}

function lvc_sync_upsert_villa( $v ) {
	$cpt  = (string) lvc_config( 'cpt', 'villa' );
	$name = trim( (string) lvc_sync_val( $v, 'property_name' ) );
	$slug = sanitize_title( (string) lvc_sync_val( $v, 'url' ) );
	if ( '' === $slug ) {
		$slug = sanitize_title( trim( lvc_sync_val( $v, 'community' ) . ' ' . lvc_sync_val( $v, 'lot' ) . ' ' . lvc_sync_val( $v, 'area' ) ) );
	}
	/*
	 * ── IDENTITY ────────────────────────────────────────────────────────────
	 * `wp_post_id` wins; the slug is only a fallback.
	 *
	 * This previously matched on the slug alone. Rename a villa in the sheet and
	 * its slug changes, so the next run finds nothing and CREATES A SECOND POST —
	 * which is what produced the duplicate Los Cabos listings. The sheet writes
	 * `wp_post_id` back on every successful push, so from the second run onward
	 * identity survives any rename.
	 *
	 * The connector must SEND that column, not merely write it back. Fixing this
	 * receiver alone changes nothing.
	 */
	$existing_id = 0;
	$sent_id     = (int) lvc_sync_val( $v, 'wp_post_id', 0 );

	if ( $sent_id > 0 ) {
		$p = get_post( $sent_id );
		if ( $p && $p->post_type === $cpt && 'trash' !== $p->post_status ) {
			$existing_id = $sent_id;
		}
	}

	if ( ! $existing_id && '' !== $slug ) {
		$byslug = get_page_by_path( $slug, OBJECT, $cpt );
		if ( $byslug ) {
			$existing_id = (int) $byslug->ID;
		}
	}

	// A resolved wp_post_id IS identity. Requiring a name alongside it blocks the
	// most useful operation here: change one cell and push.
	if ( ! $existing_id && '' === $slug && '' === $name ) {
		return array( 'ok' => false, 'error' => 'Row identifies nothing: needs wp_post_id, url, or property_name.' );
	}

	$postarr = array(
		'post_type'   => $cpt,
		'post_status' => 'publish',
	);
	$action = 'created';
	if ( $existing_id ) {
		$postarr['ID'] = $existing_id;
		$action        = 'updated';
	}

	/*
	 * Title and slug only CHANGE when supplied, so a partial update cannot
	 * silently rename or re-slug a villa. The existing title is still passed
	 * through on update because wp_insert_post() rejects a post whose title,
	 * content and excerpt are all empty — even when only meta is changing.
	 */
	if ( '' !== $name ) {
		$postarr['post_title'] = $name;
	} elseif ( $existing_id ) {
		$postarr['post_title'] = get_post_field( 'post_title', $existing_id );
	} else {
		$postarr['post_title'] = lvc_sync_label( $slug );
	}

	if ( '' !== $slug ) {
		$postarr['post_name'] = $slug;
	}
	$post_id = wp_insert_post( $postarr, true );
	if ( is_wp_error( $post_id ) ) {
		return array( 'ok' => false, 'slug' => $slug, 'error' => $post_id->get_error_message() );
	}

	// ACF fields — set only those present in the payload.
	$acf = array(
		'community', 'lot', 'card_title', 'h1_title', 'villa_aliases',
		'bed_count', 'bath_count', 'guests_max', 'from_rate_tier', 'featured',
		'property_descr', 'indoor_living', 'outdoor_living', 'bedroom_desc',
		'travel_experience', 'catering_level', 'catering_detail', 'tags', 'gallery_squares',
		'faq_q1', 'faq_a1', 'faq_q2', 'faq_a2', 'faq_q3', 'faq_a3', 'faq_q4', 'faq_a4',
	);
	if ( function_exists( 'update_field' ) ) {
		foreach ( $acf as $f ) {
			if ( array_key_exists( $f, $v ) ) {
				update_field( $f, $v[ $f ], $post_id );
			}
		}
		if ( '' === (string) lvc_sync_val( $v, 'card_title' ) ) {
			update_field( 'card_title', $name, $post_id );
		}
	}

	// Taxonomy terms.
	lvc_sync_term( $post_id, 'destination', lvc_sync_val( $v, 'destination' ), false );
	lvc_sync_term( $post_id, 'area', lvc_sync_val( $v, 'area' ), false );

	$amenities = array_filter( array_map( 'trim', explode( ',', (string) lvc_sync_val( $v, 'amenities' ) ) ) );
	if ( $amenities ) {
		wp_set_object_terms( $post_id, array(), 'amenity' ); // reset, then add fresh
		foreach ( $amenities as $tok ) {
			lvc_sync_term( $post_id, 'amenity', lvc_sync_label( $tok ), true );
		}
	}

	$te = lvc_sync_val( $v, 'travel_experience' );
	if ( $te ) {
		lvc_sync_term( $post_id, 'collection', lvc_sync_label( $te ), false );
	}
	$bc = (int) lvc_sync_val( $v, 'bed_count' );
	if ( $bc > 0 ) {
		lvc_sync_term( $post_id, 'bedrooms', $bc . ' Bedrooms', false );
	}
	$cl = lvc_sync_val( $v, 'catering_level' );
	if ( $cl ) {
		lvc_sync_term( $post_id, 'catering', lvc_sync_label( $cl ), false );
	}

	// Rank Math meta.
	$st = lvc_sync_val( $v, 'seo_title' );
	if ( $st ) {
		update_post_meta( $post_id, 'rank_math_title', $st );
	}
	$md = lvc_sync_val( $v, 'meta_description' );
	if ( $md ) {
		update_post_meta( $post_id, 'rank_math_description', $md );
	}

	// FIFU featured image (hero, else first gallery URL).
	$img = (string) lvc_sync_val( $v, 'hero_image_url' );
	if ( '' === $img ) {
		$urls = preg_split( '/[\r\n,]+/', (string) lvc_sync_val( $v, 'gallery_squares' ) );
		foreach ( (array) $urls as $u ) {
			$u = trim( $u );
			if ( preg_match( '#^https?://#i', $u ) ) {
				$img = $u;
				break;
			}
		}
	}
	if ( $img ) {
		update_post_meta( $post_id, 'fifu_image_url', $img );
		update_post_meta( $post_id, 'fifu_image_alt', $name );
	}

	return array(
		'ok'      => true,
		'slug'    => $slug,
		'post_id' => (int) $post_id,
		'action'  => $action,
		'url'     => get_permalink( $post_id ),
	);
}
