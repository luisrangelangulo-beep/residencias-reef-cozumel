<?php
/**
 * Luxury Villa Theme Core — shared template helpers.
 * ─────────────────────────────────────────────────────────────────────────
 * Small, brand-agnostic helpers used across templates. All read from
 * theme-config.php so nothing brand-specific is hardcoded in templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** URL of the property archive (e.g. /luxury-villas/). */
if ( ! function_exists( 'lvc_archive_url' ) ) {
	function lvc_archive_url() {
		$url = get_post_type_archive_link( lvc_config( 'cpt', 'villa' ) );
		return $url ?: home_url( '/' . trim( (string) lvc_config( 'cpt_archive_slug', 'luxury-villas' ), '/' ) . '/' );
	}
}

/** URL of a configured page by key (contact, request, about, how, owners, magazine). */
if ( ! function_exists( 'lvc_page_url' ) ) {
	function lvc_page_url( $key ) {
		$pages = (array) lvc_config( 'pages', array() );
		$slug  = isset( $pages[ $key ] ) ? $pages[ $key ] : $key;
		return home_url( '/' . trim( (string) $slug, '/' ) . '/' );
	}
}

/** Filterable WhatsApp URL (empty if not configured). */
if ( ! function_exists( 'lvc_whatsapp_url' ) ) {
	function lvc_whatsapp_url() {
		return apply_filters( 'lvc_whatsapp_url', (string) lvc_config( 'whatsapp_url', '' ) );
	}
}

/**
 * Best-available image URL for a property.
 * Order: FIFU meta → featured image → first URL found in an ACF gallery field.
 */
if ( ! function_exists( 'lvc_property_image' ) ) {
	function lvc_property_image( $post_id, $size = 'large' ) {
		/*
		 * Curated fields first. Without them the card image resolved to whichever
		 * URL happened to be first in the gallery — true for 99 of 150 villas —
		 * so cards swapped whenever a gallery was re-ordered or re-synced. The
		 * gallery fallback is kept last so nothing renders blank.
		 */
		foreach ( array( 'feature_image', 'hero_image' ) as $field ) {
			$curated = trim( (string) get_post_meta( $post_id, $field, true ) );
			if ( $curated ) {
				return esc_url( $curated );
			}
		}

		// FIFU (Featured Image From URL) is no longer installed; only 4 villas
		// still carry orphaned fifu_image_url meta. Dropped so the chain is
		// curated field -> WordPress featured image -> gallery.
		$img = get_the_post_thumbnail_url( $post_id, $size );
		if ( ! $img ) {
			foreach ( array( 'gallery_squares', 'gallery_slider', 'gallery' ) as $field ) {
				$gallery = (string) get_post_meta( $post_id, $field, true );
				if ( $gallery && preg_match( '/https?:\/\/[^\s"\'<>]+/i', $gallery, $m ) ) {
					$img = $m[0];
					break;
				}
			}
		}
		return $img ? esc_url( $img ) : '';
	}
}

/**
 * Legacy field-name aliases (canonical => legacy).
 *
 * The live property data was imported under the original RMOF generator schema
 * (`bedrooms`, `bathrooms`, `max_guests`, `property_description`,
 * `h1_property_title`), while theme-core templates, the Schema.org builder, and
 * the sheet-sync all use the newer canonical names (`bed_count`, `bath_count`,
 * `guests_max`, `property_descr`, `h1_title`). Until the existing villas are
 * re-synced to the canonical names, `lvc_field()` reads canonical-first and
 * falls back to the legacy name so cards, single templates, and schema populate
 * from either schema. Filterable so a fully-migrated brand can drop the shim.
 */
if ( ! function_exists( 'lvc_field_aliases' ) ) {
	function lvc_field_aliases() {
		return apply_filters( 'lvc_field_aliases', array(
			'h1_title'       => 'h1_property_title',
			'bed_count'      => 'bedrooms',
			'bath_count'     => 'bathrooms',
			'guests_max'     => 'max_guests',
			'property_descr' => 'property_description',
		) );
	}
}

/**
 * ACF field with a graceful fallback chain when the plugin or value is absent.
 * Reads the canonical field name first, then any legacy alias (see
 * lvc_field_aliases()). Safe to call even if ACF is not active.
 */
if ( ! function_exists( 'lvc_field' ) ) {
	function lvc_field( $name, $post_id = null, $default = '' ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}
		$value = get_field( $name, $post_id );
		if ( null === $value || '' === $value || array() === $value ) {
			$aliases = lvc_field_aliases();
			if ( isset( $aliases[ $name ] ) ) {
				$value = get_field( $aliases[ $name ], $post_id );
			}
		}
		return ( null === $value || '' === $value || array() === $value ) ? $default : $value;
	}
}

/** The active brand name (for headings, schema, email subjects). */
if ( ! function_exists( 'lvc_brand' ) ) {
	function lvc_brand() {
		return (string) lvc_config( 'brand_name', get_bloginfo( 'name' ) );
	}
}

/**
 * The most specific `area` term assigned to a property.
 *
 * Villas here are tagged at every level at once (e.g. a Soliman Bay villa
 * carries Riviera Maya + Tulum + Soliman Bay simultaneously, so its microarea
 * shows up on its own area-lander page). `get_the_terms()` does not guarantee
 * root-first-or-leaf-first order, so picking `[0]` can silently surface the
 * broadest term ("Riviera Maya") instead of the actual neighborhood — this
 * picks the term with the most ancestors instead, breaking ties by term_id
 * for a stable result.
 */
if ( ! function_exists( 'lvc_property_area_term' ) ) {
	function lvc_property_area_term( $post_id ) {
		$terms = get_the_terms( $post_id, 'area' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}
		$deepest       = null;
		$deepest_depth = -1;
		foreach ( $terms as $term ) {
			$depth = count( get_ancestors( $term->term_id, 'area' ) );
			if ( $depth > $deepest_depth || ( $depth === $deepest_depth && $deepest && $term->term_id < $deepest->term_id ) ) {
				$deepest       = $term;
				$deepest_depth = $depth;
			}
		}
		return $deepest;
	}
}
