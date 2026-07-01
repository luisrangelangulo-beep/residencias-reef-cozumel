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
		$img = get_post_meta( $post_id, 'fifu_image_url', true );
		if ( ! $img ) {
			$img = get_the_post_thumbnail_url( $post_id, $size );
		}
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
 * ACF field with a graceful fallback chain when the plugin or value is absent.
 * Safe to call even if ACF is not active.
 */
if ( ! function_exists( 'lvc_field' ) ) {
	function lvc_field( $name, $post_id = null, $default = '' ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}
		$value = get_field( $name, $post_id );
		return ( null === $value || '' === $value || array() === $value ) ? $default : $value;
	}
}

/** The active brand name (for headings, schema, email subjects). */
if ( ! function_exists( 'lvc_brand' ) ) {
	function lvc_brand() {
		return (string) lvc_config( 'brand_name', get_bloginfo( 'name' ) );
	}
}
