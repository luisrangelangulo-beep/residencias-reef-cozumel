<?php
/**
 * Shared ACF controls for WordPress Pages.
 *
 * Every page uses the same media contract, including custom page templates.
 * The fields use external URLs because the portfolio stores imagery on R2/CDN.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'lvc_register_page_fields' );

function lvc_register_page_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'    => 'group_lvc_page_media',
		'title'  => 'Page Hero & Media',
		'fields' => array(
			array(
				'key'          => 'field_lvc_page_hero_image',
				'label'        => 'Hero Image URL',
				'name'         => 'hero_image_url',
				'type'         => 'url',
				'instructions' => 'Primary wide hero image for this page. Use a WebP or JPG URL from R2/CDN; recommended minimum width: 2000px.',
				'placeholder'  => 'https://…',
			),
			array(
				'key'          => 'field_lvc_page_feature_image',
				'label'        => 'Feature / Card Image URL',
				'name'         => 'feature_image_url',
				'type'         => 'url',
				'instructions' => 'Optional image for cards, previews, and social/editorial placements. It also acts as the final hero fallback.',
				'placeholder'  => 'https://…',
			),
			array(
				'key'          => 'field_lvc_page_hero_intro',
				'label'        => 'Hero Introduction',
				'name'         => 'hero_intro',
				'type'         => 'textarea',
				'rows'         => 3,
				'instructions' => 'Short introduction displayed beneath the page H1. The standard WordPress Excerpt remains the fallback.',
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
		),
		'position'   => 'normal',
		'menu_order' => 1,
		'active'     => true,
	) );
}

