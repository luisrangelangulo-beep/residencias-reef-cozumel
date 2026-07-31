<?php
/**
 * Legacy Residencias Reef unit URLs → canonical /villas/ pages.
 *
 * The pre-rebuild site exposed the five units at /residencias-reef-{unit}/
 * and nested under /cozumel-vacation-rentals/… (audit RRC-010). One-hop 301
 * by unit number, resolved against the LIVE post so a future slug change
 * cannot strand the redirect.
 *
 * Two request shapes reach here, so the guard checks both:
 *  - a plain 404 (unknown root-level or nested path);
 *  - a PHANTOM ATTACHMENT query — WP parses any unresolved nested path as
 *    "attachment under the parent slug", and AIOSEO's attachment-URL
 *    redirect then wins the race and sends the visitor to a generic page,
 *    losing the unit. Hooked at `wp` priority 0 to run before it.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_legacy_unit_redirect' ) ) {
	function lvc_legacy_unit_redirect() {
		static $ran = false;
		if ( $ran ) {
			return;
		}

		$phantom_attachment = '' !== (string) get_query_var( 'attachment' ) && ! get_queried_object();
		if ( ! is_404() && ! $phantom_attachment ) {
			return; // Gate may still pass on the later hook — don't latch yet.
		}
		$ran = true;

		$path = strtolower( trim( (string) parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' ) );

		/*
		 * Confirmed one-to-one routes from the July 2026 GSC 404 export.
		 * Keep this explicit: guessing between similarly named historical
		 * villas risks sending guests and search equity to the wrong property.
		 */
		$legacy_routes = array(
			'about-us-2'                                                  => '/about-us/',
			'terms-and-conditions'                                        => '/rental-policies/',
			'casa-la-playa-cozumel-beachfront-luxury-rental'              => '/villas/casa-la-playa-cozumel/',
			'playa-del-carmen/casa-gigi'                                  => '/villas/villa-gigi-playacar-phase-1/',
			'playa-del-carmen/casa-los-charcos-playacar-luxury-home'      => '/villas/casa-los-charcos-playacar-phase-1/',
			'playa-del-carmen/casa-martini-playacar-rental'               => '/villas/casa-martini-playacar-phase-1/',
			'playa-del-carmen/villa-brianna-playacar-luxury-private-home-rental' => '/villas/villa-brianna-playacar/',
			'playa-del-carmen/casa-nikki-rental'                          => '/villas/casa-nikki-playacar-phase-1/',
			'playa-del-carmen/casa-clara-playacar-rental'                 => '/villas/casa-clara-playacar-phase-1/',
			'playa-del-carmen/villa-turquesa-playacar-luxury-rental'      => '/villas/villa-turquesa-playacar-phase-1/',
			'riviera-maya-villa-rentals/villa-tulumar-tankah-rental'      => '/villas/villa-tulumar-tankah-bay-tulum/',
			'riviera-maya-villa-rentals/casa-tira-tulum-veleta'          => '/villas/casa-tira-tulum-la-veleta/',
			'riviera-maya-villa-rentals/villa-88-tulum-veleta'           => '/villas/villa-88-tulum-la-veleta/',
		);
		if ( isset( $legacy_routes[ $path ] ) ) {
			wp_safe_redirect( home_url( $legacy_routes[ $path ] ), 301 );
			exit;
		}

		// The historical routes were nested (/cozumel-vacation-rentals/residencias-
		// reef-condo-6100/), so match the LAST segment regardless of nesting —
		// core's redirect_guess_404_permalink otherwise sends near-miss variants
		// (e.g. …condo-5220 vs the real …condo-5220-cozumel slug) to the bare
		// /villas/ archive, losing the unit.
		$last = basename( $path );
		// residencias-reef-6100, residencias-reef-condo-6100, reef-condo-6100,
		// residencias-reef-condo-5220-cozumel …
		if ( ! preg_match( '#^(?:residencias-)?reef-(?:cozumel-)?(?:condo-)?(\d{4})(?:-cozumel)?$#', $last, $m ) ) {
			return;
		}
		$unit = $m[1];
		$hit  = get_posts( array(
			'post_type'      => 'villas',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			's'              => 'Residencias Reef ' . $unit,
		) );
		if ( ! $hit ) {
			// Fallback: slug scan (search can miss when the number is only in the slug).
			global $wpdb;
			$hit = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'villas' AND post_status = 'publish' AND post_name LIKE %s LIMIT 1",
				'%' . $wpdb->esc_like( $unit ) . '%'
			) );
		}
		if ( $hit ) {
			wp_safe_redirect( get_permalink( (int) $hit[0] ), 301 );
			exit;
		}
	}
}

// `wp` fires after the main query is resolved but before any plugin's
// template_redirect handlers (AIOSEO's attachment redirect included).
add_action( 'wp', 'lvc_legacy_unit_redirect', 0 );
// Backstop for anything that only becomes a 404 at template time.
add_action( 'template_redirect', 'lvc_legacy_unit_redirect', 1 );
