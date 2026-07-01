<?php
/**
 * Luxury Villa Theme Core — taxonomy TERM ACF fields.
 * ─────────────────────────────────────────────────────────────────────────
 * Gives archive/landing terms a hero image, intro copy, and (for places) geo +
 * hierarchy — so destination/area/collection pages can be skinned and ranked.
 * Image URLs are Cloudflare R2 (no media-library upload). No-op without ACF.
 *
 * The same field NAMES (hero_image_url, intro, …) are reused across groups with
 * distinct keys — ACF scopes term meta per taxonomy, so there is no collision.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'lvc_register_term_fields' );

function lvc_register_term_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	/* ── Place terms: destination + area — hero, intro, geo, hierarchy ── */
	acf_add_local_field_group( array(
		'key'    => 'group_lvc_place_terms',
		'title'  => 'Place — Hero, Intro & Geo',
		'fields' => array(
			array( 'key' => 'field_lvc_term_hero', 'label' => 'Hero Image URL', 'name' => 'hero_image_url', 'type' => 'url', 'instructions' => 'Cloudflare R2 image for the archive hero / social share.' ),
			array( 'key' => 'field_lvc_term_intro', 'label' => 'Intro / Landing Copy', 'name' => 'intro', 'type' => 'wysiwyg', 'tabs' => 'visual', 'media_upload' => 0 ),
			array( 'key' => 'field_lvc_term_lat', 'label' => 'Latitude', 'name' => 'geo_lat', 'type' => 'text', 'instructions' => 'For schema areaServed / geo.' ),
			array( 'key' => 'field_lvc_term_lng', 'label' => 'Longitude', 'name' => 'geo_lng', 'type' => 'text' ),
			array( 'key' => 'field_lvc_term_parent_dest', 'label' => 'Parent Destination', 'name' => 'parent_destination', 'type' => 'text', 'instructions' => 'Areas only — the destination slug this area sits under (e.g. dominican-republic). Used for breadcrumbs/nav.' ),
		),
		'location' => array(
			array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'destination' ) ),
			array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'area' ) ),
		),
	) );

	/* ── Landing terms: the collection-style pages — hero + intro only ── */
	$landing = array( 'collection', 'bedrooms', 'beach_access' );
	$location = array();
	foreach ( $landing as $tax ) {
		$location[] = array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => $tax ) );
	}

	acf_add_local_field_group( array(
		'key'    => 'group_lvc_landing_terms',
		'title'  => 'Landing — Hero & Intro',
		'fields' => array(
			array( 'key' => 'field_lvc_lterm_hero', 'label' => 'Hero Image URL', 'name' => 'hero_image_url', 'type' => 'url', 'instructions' => 'Cloudflare R2 image for the landing hero.' ),
			array( 'key' => 'field_lvc_lterm_intro', 'label' => 'Intro / Landing Copy', 'name' => 'intro', 'type' => 'wysiwyg', 'tabs' => 'visual', 'media_upload' => 0 ),
		),
		'location' => $location,
	) );
}
