<?php
/**
 * Luxury Villa Theme Core — Homepage Settings options page.
 * ─────────────────────────────────────────────────────────────────────────
 * front-page.php has no post of its own to hold editable fields, so this
 * gives it a dedicated "Homepage" admin screen instead of borrowing the
 * Riviera Maya area term's fields. Image URLs are Cloudflare R2 (no
 * media-library upload). No-op without ACF.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'lvc_register_homepage_settings' );

function lvc_register_homepage_settings() {
	if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_options_page( array(
		'page_title' => 'Homepage Settings',
		'menu_title' => 'Homepage',
		'menu_slug'  => 'lvc-homepage-settings',
		'capability' => 'manage_options',
		'icon_url'   => 'dashicons-admin-home',
		'position'   => 59,
		'redirect'   => false,
	) );

	acf_add_local_field_group( array(
		'key'    => 'group_lvc_homepage_settings',
		'title'  => 'Homepage Hero',
		'fields' => array(
			array(
				'key'          => 'field_lvc_home_hero_url',
				'label'        => 'Hero Image URL',
				'name'         => 'home_hero_image_url',
				'type'         => 'url',
				'instructions' => 'Cloudflare R2 image for the homepage hero background. Leave empty to fall back to the Riviera Maya area hero image, then a Riviera Maya villa photo.',
			),
		),
		'location' => array(
			array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'lvc-homepage-settings' ) ),
		),
	) );

	acf_add_local_field_group( array(
		'key'    => 'group_lvc_archive_hero_settings',
		'title'  => 'Villas Archive Hero',
		'fields' => array(
			array(
				'key'          => 'field_lvc_archive_hero_url',
				'label'        => 'Hero Image URL',
				'name'         => 'archive_hero_image_url',
				'type'         => 'url',
				'instructions' => 'Cloudflare R2 image for the /villas/ archive hero background.',
			),
		),
		'location' => array(
			array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'lvc-homepage-settings' ) ),
		),
	) );
}
