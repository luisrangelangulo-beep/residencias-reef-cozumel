<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private CPT that stores every inquiry submission, independent of email
 * delivery. Previously the inbox was the only record of a lead; if SMTP
 * failed, throttled, or landed in spam, the lead was gone with no way to
 * recover it. This also gives a real pipeline view (volume, source
 * property, conversion) instead of that data only living in an inbox.
 */
add_action( 'init', 'lvc_register_inquiry_cpt', 5 );
if ( ! function_exists( 'lvc_register_inquiry_cpt' ) ) {
	function lvc_register_inquiry_cpt() {
		register_post_type(
			'inquiry',
			array(
				'labels'              => array(
					'name'               => 'Inquiries',
					'singular_name'      => 'Inquiry',
					'menu_name'          => 'Inquiries',
					'name_admin_bar'     => 'Inquiry',
					'all_items'          => 'All Inquiries',
					'view_item'          => 'View Inquiry',
					'search_items'       => 'Search Inquiries',
					'not_found'          => 'No inquiries found',
					'not_found_in_trash' => 'No inquiries found in Trash',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-email-alt2',
				'menu_position'       => 26,
				'supports'            => array( 'title' ),
				// Least-privilege PII access: inquiries hold guest names,
				// emails, and phone numbers, so they use their own capability
				// set instead of inheriting 'post' caps. Without this an
				// Editor or Author account can read every lead. Only
				// manage_options (admins) is granted these caps below, and
				// dynamically — see the user_has_cap filter — so removing this
				// code removes the access with no orphaned capability rows.
				'capability_type'     => array( 'lvc_inquiry', 'lvc_inquiries' ),
				'capabilities'        => array(
					'create_posts' => 'do_not_allow', // only created programmatically on submit
				),
				'map_meta_cap'        => true,
			)
		);
	}
}

/*
 * Grant the custom inquiry capabilities to administrators only. Granted
 * dynamically (not persisted to the DB role) so removing this code removes
 * the access — no orphaned capability rows to clean up later, and no
 * activation/deactivation hook to maintain.
 */
add_filter(
	'user_has_cap',
	function ( $allcaps ) {
		if ( ! empty( $allcaps['manage_options'] ) ) {
			foreach ( array(
				'edit_lvc_inquiry',
				'read_lvc_inquiry',
				'delete_lvc_inquiry',
				'edit_lvc_inquiries',
				'edit_others_lvc_inquiries',
				'read_private_lvc_inquiries',
				'delete_lvc_inquiries',
				'delete_private_lvc_inquiries',
				'delete_published_lvc_inquiries',
				'delete_others_lvc_inquiries',
				'edit_private_lvc_inquiries',
				'edit_published_lvc_inquiries',
				'publish_lvc_inquiries',
			) as $cap ) {
				$allcaps[ $cap ] = true;
			}
		}
		return $allcaps;
	}
);

/**
 * Persist one inquiry submission as an `inquiry` post. Called before
 * wp_mail() so the lead survives even if delivery fails.
 *
 * @param array $data Sanitized inquiry fields (see ajax-handler.php).
 * @return int Post ID, or 0 on failure.
 */
if ( ! function_exists( 'lvc_save_inquiry' ) ) {
	function lvc_save_inquiry( array $data ) {
		$title_bits = array( $data['name'] ?? 'Unknown' );
		if ( ! empty( $data['property'] ) ) {
			$title_bits[] = $data['property'];
		}
		if ( ! empty( $data['checkin'] ) ) {
			$title_bits[] = $data['checkin'];
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'inquiry',
				'post_status' => 'publish',
				'post_title'  => implode( ' — ', $title_bits ),
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		$meta_map = array(
			'type'       => $data['type'] ?? '',
			'name'       => $data['name'] ?? '',
			'email'      => $data['email'] ?? '',
			'phone'      => $data['phone'] ?? '',
			'checkin'    => $data['checkin'] ?? '',
			'checkout'   => $data['checkout'] ?? '',
			'guests'     => $data['guests'] ?? 0,
			'budget'     => $data['budget'] ?? '',
			'message'    => $data['message'] ?? '',
			'property'   => $data['property'] ?? '',
			'source_url' => $data['source_url'] ?? '',
			'ip'         => $data['ip'] ?? '',
			'site'       => $data['site'] ?? '',
			'mail_failed' => 0,
		);

		foreach ( $meta_map as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Extra brand/site-specific fields (destination, area, bedrooms,
		// preferred_area, listing_url, ...) captured generically by the
		// ajax handler's lvc_inquiry_extra_fields filter.
		if ( ! empty( $data['extra'] ) && is_array( $data['extra'] ) ) {
			foreach ( $data['extra'] as $key => $value ) {
				if ( ! isset( $meta_map[ $key ] ) ) {
					update_post_meta( $post_id, $key, $value );
				}
			}
		}

		return (int) $post_id;
	}
}

/* ── Admin list: show the fields that actually matter for triage ────────── */
add_filter(
	'manage_inquiry_posts_columns',
	function ( $columns ) {
		return array(
			'cb'          => $columns['cb'] ?? '',
			'title'       => 'Lead',
			'lvc_dates'   => 'Dates',
			'lvc_guests'  => 'Guests',
			'lvc_budget'  => 'Budget',
			'lvc_status'  => 'Status',
			'date'        => 'Submitted',
		);
	}
);

add_action(
	'manage_inquiry_posts_custom_column',
	function ( $column, $post_id ) {
		switch ( $column ) {
			case 'lvc_dates':
				$checkin  = get_post_meta( $post_id, 'checkin', true );
				$checkout = get_post_meta( $post_id, 'checkout', true );
				echo esc_html( ( $checkin ?: '—' ) . ' → ' . ( $checkout ?: '—' ) );
				break;
			case 'lvc_guests':
				echo esc_html( get_post_meta( $post_id, 'guests', true ) ?: '—' );
				break;
			case 'lvc_budget':
				echo esc_html( get_post_meta( $post_id, 'budget', true ) ?: '—' );
				break;
			case 'lvc_status':
				if ( get_post_meta( $post_id, 'mail_failed', true ) ) {
					echo '<span style="color:#b32d2e;font-weight:600;">⚠ Email failed — lead saved</span>';
				} else {
					echo '<span style="color:#2e7d32;">✓ Sent</span>';
				}
				break;
		}
	},
	10,
	2
);

/*
 * PII retention: inquiries older than the retention window are deleted daily
 * — long enough for repeat-guest context and any billing dispute, short
 * enough to honor data-minimization. Default 24 months, matching RMOF / THV /
 * PMVR. The window is filterable so a brand in a stricter jurisdiction can
 * shorten it without editing core:
 *     add_filter( 'lvc_inquiry_retention_months', fn() => 12 );
 * A window of 0 (or negative) disables purging entirely.
 */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'lvc_purge_old_inquiries' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lvc_purge_old_inquiries' );
		}
	}
);

add_action(
	'lvc_purge_old_inquiries',
	function () {
		$months = (int) apply_filters( 'lvc_inquiry_retention_months', 24 );
		if ( $months < 1 ) {
			return; // Retention disabled.
		}

		$old = get_posts(
			array(
				'post_type'      => 'inquiry',
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'date_query'     => array(
					array( 'before' => $months . ' months ago' ),
				),
			)
		);
		foreach ( $old as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}
);
