<?php
/**
 * Luxury Villa Theme Core — core property ACF fields.
 * ─────────────────────────────────────────────────────────────────────────
 * Field NAMES are aligned 1:1 with the single-destination generator's sheet
 * columns (property_descr, indoor_living, …, faq_q1..faq_a4) so the sheet-sync
 * receiver maps straight in with no conversion. Brands extend this group via the
 * 'lvc_property_fields' filter rather than editing the core. No-op without ACF.
 *
 * Taxonomy-backed values (amenity, collection, catering, bedrooms, beach_access,
 * property_type, ideal_for) are assigned as TERMS by the sync — not stored here.
 * seo_title / meta_description are written to Rank Math post meta by the sync.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'lvc_register_property_fields' );

function lvc_register_property_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$cpt = lvc_config( 'cpt', 'villa' );

	$fields = apply_filters( 'lvc_property_fields', array(

		/* ── Identity ─────────────────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_identity', 'label' => 'Identity', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_community', 'label' => 'Community', 'name' => 'community', 'type' => 'text', 'instructions' => 'Stable community / development name (e.g. Las Palmas). community + lot = the permanent URL slug — survives marketing renames.' ),
		array( 'key' => 'field_lvc_lot', 'label' => 'Lot / Unit', 'name' => 'lot', 'type' => 'text', 'instructions' => 'Lot or unit number (e.g. 27).' ),
		array( 'key' => 'field_lvc_card_title', 'label' => 'Card / Display Title', 'name' => 'card_title', 'type' => 'text', 'instructions' => 'Marketing name shown on cards. Falls back to the post title.' ),
		array( 'key' => 'field_lvc_h1_title', 'label' => 'H1 (on-page heading)', 'name' => 'h1_title', 'type' => 'text', 'instructions' => 'SEO H1. Falls back to the post title.' ),
		array( 'key' => 'field_lvc_villa_aliases', 'label' => 'Aliases', 'name' => 'villa_aliases', 'type' => 'text', 'instructions' => 'Comma-separated former / marketing names → schema alternateName (catches searches for the old name).' ),

		/* ── Key Facts ────────────────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_facts', 'label' => 'Key Facts', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_bed_count', 'label' => 'Bedrooms', 'name' => 'bed_count', 'type' => 'number', 'min' => 0, 'max' => 50 ),
		array( 'key' => 'field_lvc_bath_count', 'label' => 'Bathrooms', 'name' => 'bath_count', 'type' => 'number', 'min' => 0, 'max' => 50, 'step' => '0.5' ),
		array( 'key' => 'field_lvc_guests_max', 'label' => 'Max Guests', 'name' => 'guests_max', 'type' => 'number', 'min' => 1, 'max' => 100 ),
		array(
			'key' => 'field_lvc_from_rate_tier', 'label' => 'From Rate Tier', 'name' => 'from_rate_tier', 'type' => 'select',
			'choices' => array( 'under-5k' => 'Under 5k', '5k-10k' => '5k-10k', '10k-20k' => '10k-20k', '20k-plus' => '20k-plus' ),
			'allow_null' => 1, 'return_format' => 'value',
		),
		array( 'key' => 'field_lvc_featured', 'label' => 'Featured', 'name' => 'featured', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0 ),

		/* ── Content (generated) ──────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_content', 'label' => 'Content', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_property_descr', 'label' => 'Overview', 'name' => 'property_descr', 'type' => 'wysiwyg', 'tabs' => 'visual', 'media_upload' => 0 ),
		array( 'key' => 'field_lvc_indoor_living', 'label' => 'Indoor Living', 'name' => 'indoor_living', 'type' => 'textarea', 'rows' => 4 ),
		array( 'key' => 'field_lvc_outdoor_living', 'label' => 'Outdoor Living', 'name' => 'outdoor_living', 'type' => 'textarea', 'rows' => 4 ),
		array( 'key' => 'field_lvc_bedroom_desc', 'label' => 'Bedrooms', 'name' => 'bedroom_desc', 'type' => 'textarea', 'rows' => 4 ),

		/* ── Experience & Service (generated) ─────────────────────── */
		array( 'key' => 'field_lvc_tab_service', 'label' => 'Experience & Service', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_travel_experience', 'label' => 'Travel Experience', 'name' => 'travel_experience', 'type' => 'text', 'instructions' => 'One of: beachfront, oceanfront, coastal-hillside, beach-town, island, golf-resort, marina-front. The sync derives the Collection term from this.' ),
		array( 'key' => 'field_lvc_catering_level', 'label' => 'Catering Level', 'name' => 'catering_level', 'type' => 'text', 'instructions' => 'e.g. self-catering, staffed, full-staff.' ),
		array( 'key' => 'field_lvc_catering_detail', 'label' => 'Catering Detail', 'name' => 'catering_detail', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_tags', 'label' => 'Tags', 'name' => 'tags', 'type' => 'text', 'instructions' => 'Comma-separated SEO tags.' ),

		/* ── Media ────────────────────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_media', 'label' => 'Media', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_hero_image', 'label' => 'Hero Image', 'name' => 'hero_image', 'type' => 'url', 'instructions' => 'Full-bleed image for the villa page hero. Falls back to the featured image, then the first gallery URL, when empty.' ),
		array( 'key' => 'field_lvc_feature_image', 'label' => 'Feature Image (Cards/Grid)', 'name' => 'feature_image', 'type' => 'url', 'instructions' => 'Image shown on villa cards across the site (homepage, area grids, related villas). Set this so the card image is stable — without it the card falls back to whichever URL happens to be first in the gallery, which changes whenever the gallery is re-ordered or re-synced.' ),
		array( 'key' => 'field_lvc_gallery', 'label' => 'Gallery URLs', 'name' => 'gallery_squares', 'type' => 'textarea', 'instructions' => 'One Cloudflare R2 image URL per line. The first is the hero fallback when no FIFU / featured image is set.' ),

		/* ── FAQ (flat — 1:1 with the generator) ──────────────────── */
		array( 'key' => 'field_lvc_tab_faq', 'label' => 'FAQ', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_faq_q1', 'label' => 'Q1', 'name' => 'faq_q1', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a1', 'label' => 'A1', 'name' => 'faq_a1', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_faq_q2', 'label' => 'Q2', 'name' => 'faq_q2', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a2', 'label' => 'A2', 'name' => 'faq_a2', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_faq_q3', 'label' => 'Q3', 'name' => 'faq_q3', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a3', 'label' => 'A3', 'name' => 'faq_a3', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_faq_q4', 'label' => 'Q4', 'name' => 'faq_q4', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a4', 'label' => 'A4', 'name' => 'faq_a4', 'type' => 'textarea', 'rows' => 2 ),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lvc_property_core',
		'title'    => lvc_config( 'cpt_singular', 'Villa' ) . ' — Core Fields',
		'fields'   => $fields,
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => $cpt ) ) ),
		'position' => 'normal',
		'active'   => true,
	) );
}
