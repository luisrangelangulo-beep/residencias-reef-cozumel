<?php
/**
 * Luxury Villa Theme Core — BRAND CONFIGURATION
 * ─────────────────────────────────────────────────────────────────────────
 * This is the ONE file you edit to spin up a new site (plus assets/brand.css).
 * Every template and include reads brand-specific values from here via
 * lvc_config('key'). Nothing brand-specific should be hardcoded anywhere else.
 *
 * You can also override any value without editing this file by hooking the
 * 'lvc_config' filter from a small site plugin or mu-plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_config' ) ) {
	/**
	 * Accessor for the brand configuration.
	 *
	 * @param string|null $key     Config key, or null for the whole array.
	 * @param mixed       $default Fallback when the key is missing.
	 * @return mixed
	 */
	function lvc_config( $key = null, $default = '' ) {
		static $config = null;

		if ( null === $config ) {
			$config = apply_filters( 'lvc_config', array(

				/* ── Brand identity ───────────────────────────────────── */
				'brand_name'     => 'Residencias Reef Cozumel',
				'brand_tagline'  => 'Luxury villas across the Riviera Maya — Cozumel, Tulum, Playa del Carmen & beyond.',
				'brand_logo_svg' => '', // Inline SVG markup; empty = render brand_name as text.

				/* ── Contact / inquiry routing ────────────────────────── */
				'support_email'  => '', // TODO: confirm real guest-inquiry inbox before launch.
				'owner_email'    => '', // Owner leads; empty = falls back to support_email.
				'phone'          => '', // e.g. '+1 (000) 000-0000'; empty = hide. TODO: confirm real number.
				'whatsapp_url'   => '', // e.g. 'https://wa.link/xxxx'; empty = hide. TODO: confirm real link.
				'response_time'  => 'within 24 hours',
				'region'         => 'Riviera Maya, Mexico', // Used in page schema (areaServed).

				/* ── Property model (CPT) ─────────────────────────────── */
				'cpt'              => 'villas',         // Already registered by CPT UI on the live site.
				'cpt_singular'     => 'Villa',
				'cpt_plural'       => 'Villas',
				'cpt_archive_slug' => 'villas',         // Matches live rewrite: has_archive => 'villas'.
				'cpt_rewrite_slug' => 'villas',         // Matches live rewrite: single => /villas/{slug}/.
				'register_cpt'     => false,            // CPT UI owns `villas` on the live site — do not re-register.

				/* ── Taxonomies: slug => [ plural label, singular label ] ─
				 * Live site only has `area` (hierarchical: Riviera Maya > Cozumel/
				 * Tulum/Playa Del Carmen/etc. > sub-areas) and `bedrooms`. No
				 * destination/collection/beach_access/amenity taxonomies exist here —
				 * `area`'s own hierarchy plays the destination+area role Los Cabos
				 * split across two flat taxonomies. */
				'taxonomies' => array(
					'area'     => array( 'Areas', 'Area' ),
					'bedrooms' => array( 'Bedrooms', 'Bedrooms' ),
				),
				'register_taxonomies' => false, // CPT UI owns these on the live site — do not re-register.

				/* ── Page slugs (nav + internal links) ────────────────── */
				'pages' => array(
					'contact'  => 'contact',
					'request'  => 'villa-request',
					'about'    => 'about',
					'how'      => 'how-it-works',
					'owners'   => 'list-your-villa',
					'magazine' => 'magazine',
				),

				/* ── Inquiry engine ───────────────────────────────────── */
				'inquiry_action' => 'lvc_inquiry', // AJAX action + nonce name.

				/* ── SEO posture ──────────────────────────────────────── */
				'theme_owns_schema'  => true, // Suppress Rank Math schema; theme emits JSON-LD.
				'noindex_thin_terms' => true, // noindex taxonomy terms under min_index_count.
				'min_index_count'    => 1,
				'geo'                => array( 'lat' => '', 'lng' => '' ), // Destination geo for schema.
			) );
		}

		if ( null === $key ) {
			return $config;
		}

		return array_key_exists( $key, $config ) ? $config[ $key ] : $default;
	}
}
