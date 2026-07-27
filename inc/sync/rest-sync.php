<?php
/**
 * Luxury Villa Theme Core — Sheet → WordPress sync receiver (governed).
 * ─────────────────────────────────────────────────────────────────────────
 * POST /wp-json/lvc/v1/sync   (the Google-Sheet connector pushes here)
 *
 * Auth:  header X-LVC-Sync-Token ONLY (query-param auth removed — audit
 *        RRC-023: tokens in URLs leak into access logs and analytics).
 *
 * Governance (audit RRC-004, ported from the Republic receiver):
 *   - dry_run: true      → validate + report, write NOTHING
 *   - allow_term_create  → unknown taxonomy terms are SKIPPED with a warning
 *                          unless this flag is true (no more taxonomy sprawl
 *                          from sheet typos). Code-derived terms (bedrooms
 *                          "N Bedrooms") are exempt.
 *   - "-" as a field/term value = explicit clear; "" = no change
 *   - NEW records missing core fields are created as DRAFT (quarantined)
 *   - updates PRESERVE post_status (a sync can no longer republish a
 *     paused/off-market unit)
 *   - off_market field accepted; enforcement lives in
 *     inc/property/off-market.php
 *   - amenity set is resolved BEFORE replacing (a row of unknown names can
 *     no longer wipe curated terms)
 *   - batch `ok` reflects row failures
 *
 * Identity: wp_post_id wins; slug is fallback (rename-safe — see the
 * Los Cabos duplicate lesson below).
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

/** Shared-secret auth: header X-LVC-Sync-Token ONLY vs the lvc_sync_token option. */
function lvc_sync_auth( $request ) {
	$token = (string) get_option( 'lvc_sync_token', '' );
	if ( '' === $token ) {
		return new WP_Error( 'lvc_no_token', 'Sync token not configured on this site.', array( 'status' => 503 ) );
	}
	$sent = (string) $request->get_header( 'x_lvc_sync_token' );
	if ( '' === $sent || ! hash_equals( $token, $sent ) ) {
		return new WP_Error( 'lvc_bad_token', 'Invalid sync token (header X-LVC-Sync-Token required).', array( 'status' => 401 ) );
	}
	return true;
}

function lvc_sync_handle( WP_REST_Request $request ) {
	$body = (array) $request->get_json_params();

	// Governance flags — per-request, read by the helpers via globals.
	$GLOBALS['lvc_sync_dry']         = ! empty( $body['dry_run'] );
	$GLOBALS['lvc_sync_allow_terms'] = ! empty( $body['allow_term_create'] );

	$villas = array();
	if ( isset( $body['villas'] ) && is_array( $body['villas'] ) ) {
		$villas = $body['villas'];
	} elseif ( isset( $body['url'] ) || isset( $body['property_name'] ) || isset( $body['wp_post_id'] ) ) {
		$villas = array( $body );
	}
	if ( ! $villas ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'No villas in payload.' ), 400 );
	}

	$results = array();
	$all_ok  = true;
	foreach ( $villas as $v ) {
		$r = lvc_sync_upsert_villa( (array) $v );
		if ( empty( $r['ok'] ) ) {
			$all_ok = false;
		}
		$results[] = $r;
	}
	$created = count( array_filter( $results, function ( $r ) { return ! empty( $r['ok'] ) && 0 === strpos( (string) ( $r['action'] ?? '' ), 'created' ); } ) );
	$updated = count( array_filter( $results, function ( $r ) { return ! empty( $r['ok'] ) && 'updated' === ( $r['action'] ?? '' ); } ) );

	return new WP_REST_Response( array(
		'ok'      => $all_ok,
		'dry_run' => ! empty( $GLOBALS['lvc_sync_dry'] ),
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

/**
 * Assign a term to the post. $append=false replaces.
 *
 * Unknown terms are NOT auto-created (audit RRC-004) — they are skipped and
 * the warning is returned for the row report. Deliberate new terms require
 * allow_term_create=true on the request. $force_create is for CODE-derived
 * names only (bedrooms "N Bedrooms"), never raw sheet input. The literal
 * value "-" clears the taxonomy.
 *
 * @return string|null Warning message, or null when assigned/cleared.
 */
function lvc_sync_term( $post_id, $tax, $name, $append = true, $force_create = false ) {
	$name = trim( (string) $name );
	if ( ! taxonomy_exists( $tax ) ) {
		return null;
	}
	if ( '-' === $name ) {
		if ( empty( $GLOBALS['lvc_sync_dry'] ) ) {
			wp_set_object_terms( $post_id, array(), $tax, false );
		}
		return null;
	}
	if ( '' === $name ) {
		return null;
	}
	$slug = sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $tax );
	if ( $term ) {
		$tid = (int) $term->term_id;
	} elseif ( $force_create || ! empty( $GLOBALS['lvc_sync_allow_terms'] ) ) {
		if ( ! empty( $GLOBALS['lvc_sync_dry'] ) ) {
			return "dry_run: would CREATE term '{$slug}' in {$tax}";
		}
		$res = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			return "term '{$slug}' ({$tax}) failed: " . $res->get_error_message();
		}
		$tid = (int) $res['term_id'];
	} else {
		return "unknown term '{$slug}' in {$tax} skipped — pass allow_term_create=true if deliberate";
	}
	if ( empty( $GLOBALS['lvc_sync_dry'] ) ) {
		wp_set_object_terms( $post_id, array( $tid ), $tax, $append );
	}
	return null;
}

/**
 * Replace a post's terms with a comma-separated incoming SET — but resolve
 * the whole set first (the old clear-then-assign flow wiped existing terms
 * even when every incoming name failed to resolve).
 *
 * Semantics match lvc_sync_term(): '' = no change, '-' = explicit clear.
 * The replace only happens when at least one incoming term resolved.
 *
 * @return string[] Warning messages (possibly empty).
 */
function lvc_sync_term_set( $post_id, $tax, $raw ) {
	$raw = trim( (string) $raw );
	if ( ! taxonomy_exists( $tax ) || '' === $raw ) {
		return array();
	}
	if ( '-' === $raw ) {
		if ( empty( $GLOBALS['lvc_sync_dry'] ) ) {
			wp_set_object_terms( $post_id, array(), $tax, false );
		}
		return array();
	}
	$warnings = array();
	$tids     = array();
	foreach ( preg_split( '/[\r\n,]+/', $raw ) as $name ) {
		$name = trim( (string) $name );
		if ( '' === $name || '-' === $name ) {
			continue;
		}
		$label = lvc_sync_label( $name );
		$slug  = sanitize_title( $label );
		$term  = get_term_by( 'slug', $slug, $tax );
		if ( $term ) {
			$tids[] = (int) $term->term_id;
			continue;
		}
		if ( ! empty( $GLOBALS['lvc_sync_allow_terms'] ) ) {
			if ( ! empty( $GLOBALS['lvc_sync_dry'] ) ) {
				$warnings[] = "dry_run: would CREATE term '{$slug}' in {$tax}";
				continue;
			}
			$res = wp_insert_term( $label, $tax, array( 'slug' => $slug ) );
			if ( is_wp_error( $res ) ) {
				$warnings[] = "term '{$slug}' ({$tax}) failed: " . $res->get_error_message();
				continue;
			}
			$tids[] = (int) $res['term_id'];
			continue;
		}
		$warnings[] = "unknown term '{$slug}' in {$tax} skipped — pass allow_term_create=true if deliberate";
	}
	if ( empty( $tids ) ) {
		$warnings[] = "no {$tax} terms resolved — existing terms preserved";
		return $warnings;
	}
	if ( empty( $GLOBALS['lvc_sync_dry'] ) ) {
		wp_set_object_terms( $post_id, array_values( array_unique( $tids ) ), $tax, false );
	}
	return $warnings;
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

	if ( ! $existing_id && '' === $slug && '' === $name ) {
		return array( 'ok' => false, 'error' => 'Row identifies nothing: needs wp_post_id, url, or property_name.' );
	}

	/*
	 * Publication gate (audit RRC-004): a NEW record missing core fields is
	 * quarantined as draft instead of going public incomplete. Updates keep
	 * whatever status the post already has — a sync can no longer republish
	 * a deliberately paused or off-market unit.
	 */
	$quarantine = array();
	if ( ! $existing_id ) {
		foreach ( array( 'property_name', 'area', 'bed_count', 'guests_max' ) as $req ) {
			if ( '' === trim( (string) lvc_sync_val( $v, $req ) ) ) {
				$quarantine[] = $req;
			}
		}
	}

	if ( ! empty( $GLOBALS['lvc_sync_dry'] ) ) {
		// Dry run: report what WOULD happen, including term prevalidation.
		$warnings = array();
		$tax_specs = array(
			array( 'area', lvc_sync_val( $v, 'area' ) ),
			array( 'collection', lvc_sync_val( $v, 'travel_experience' ) ),
			array( 'catering', lvc_sync_val( $v, 'catering_level' ) ),
		);
		foreach ( $tax_specs as $spec ) {
			$val = trim( (string) $spec[1] );
			if ( '' === $val || '-' === $val || ! taxonomy_exists( $spec[0] ) ) {
				continue;
			}
			$check_slug = sanitize_title( lvc_sync_label( $val ) );
			if ( ! get_term_by( 'slug', $check_slug, $spec[0] ) ) {
				$warnings[] = "unknown term '{$check_slug}' in {$spec[0]} would be skipped";
			}
		}
		foreach ( array_filter( array_map( 'trim', explode( ',', (string) lvc_sync_val( $v, 'amenities' ) ) ) ) as $tok ) {
			$check_slug = sanitize_title( lvc_sync_label( $tok ) );
			if ( ! get_term_by( 'slug', $check_slug, 'amenity' ) ) {
				$warnings[] = "unknown term '{$check_slug}' in amenity would be skipped";
			}
		}
		return array(
			'ok'         => true,
			'dry_run'    => true,
			'slug'       => $slug,
			'post_id'    => $existing_id,
			'action'     => $existing_id ? 'would update' : ( $quarantine ? 'would create as DRAFT (missing: ' . implode( ', ', $quarantine ) . ')' : 'would create' ),
			'warnings'   => $warnings,
		);
	}

	$postarr = array(
		'post_type'   => $cpt,
		'post_status' => $quarantine ? 'draft' : 'publish',
	);
	$action = $quarantine ? 'created as draft (missing: ' . implode( ', ', $quarantine ) . ')' : 'created';
	if ( $existing_id ) {
		$postarr['ID']          = $existing_id;
		$postarr['post_status'] = get_post_field( 'post_status', $existing_id );
		$action                 = 'updated';
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

	// ACF fields — set only those present in the payload. off_market rides
	// along; enforcement is inc/property/off-market.php.
	$acf = array(
		'community', 'lot', 'card_title', 'h1_title', 'villa_aliases',
		'bed_count', 'bath_count', 'guests_max', 'from_rate_tier', 'featured',
		'property_descr', 'indoor_living', 'outdoor_living', 'bedroom_desc',
		'travel_experience', 'catering_level', 'catering_detail', 'tags', 'gallery_squares',
		'off_market',
		'faq_q1', 'faq_a1', 'faq_q2', 'faq_a2', 'faq_q3', 'faq_a3', 'faq_q4', 'faq_a4',
	);
	if ( function_exists( 'update_field' ) ) {
		foreach ( $acf as $f ) {
			if ( array_key_exists( $f, $v ) ) {
				update_field( $f, $v[ $f ], $post_id );
			}
		}
		if ( '' === (string) lvc_sync_val( $v, 'card_title' ) && '' !== $name ) {
			// Guard (audit RRC-004 class): a partial update identified only by
			// wp_post_id has an empty $name — writing it would erase the
			// curated card title.
			update_field( 'card_title', $name, $post_id );
		}
	}

	// Taxonomy terms. Warnings collect into the row report.
	$term_warnings   = array();
	$term_warnings[] = lvc_sync_term( $post_id, 'area', lvc_sync_val( $v, 'area' ), false );

	$term_warnings = array_merge( $term_warnings, lvc_sync_term_set( $post_id, 'amenity', lvc_sync_val( $v, 'amenities' ) ) );

	$te = lvc_sync_val( $v, 'travel_experience' );
	if ( $te ) {
		$term_warnings[] = lvc_sync_term( $post_id, 'collection', lvc_sync_label( $te ), false );
	}
	$bc = (int) lvc_sync_val( $v, 'bed_count' );
	if ( $bc > 0 ) {
		// Code-derived name — safe to create.
		lvc_sync_term( $post_id, 'bedrooms', $bc . ' Bedrooms', false, true );
	}
	$cl = lvc_sync_val( $v, 'catering_level' );
	if ( $cl ) {
		$term_warnings[] = lvc_sync_term( $post_id, 'catering', lvc_sync_label( $cl ), false );
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

	/*
	 * Geography gate: no unit/villa may be PUBLISHED without an assigned
	 * area term (a misspelled area on a new row used to pass the presence
	 * check and publish unplaced). Quarantine + fail the row visibly.
	 */
	if ( 'publish' === get_post_field( 'post_status', $post_id ) && ! has_term( '', 'area', $post_id ) ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		return array(
			'ok'       => false,
			'slug'     => $slug,
			'post_id'  => (int) $post_id,
			'action'   => $action . ' — QUARANTINED to draft',
			'error'    => 'no area term resolved — published record demoted to draft until geography is fixed',
			'warnings' => array_values( array_filter( $term_warnings ) ),
		);
	}

	return array(
		'ok'       => true,
		'slug'     => $slug,
		'post_id'  => (int) $post_id,
		'action'   => $action,
		'url'      => get_permalink( $post_id ),
		'warnings' => array_values( array_filter( $term_warnings ) ),
	);
}
