<?php
/**
 * Luxury Villa Theme Core — Property CPT + taxonomy registration.
 * ─────────────────────────────────────────────────────────────────────────
 * Fully config-driven (theme-config.php). The CPT slug, labels, archive/rewrite
 * slugs, and the taxonomy set all come from lvc_config(). Registration of either
 * the CPT or the taxonomies can be turned off when CPT-UI / a plugin owns them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'lvc_register_property_model', 5 );

function lvc_register_property_model() {
	$cpt      = (string) lvc_config( 'cpt', 'villa' );
	$singular = (string) lvc_config( 'cpt_singular', 'Villa' );
	$plural   = (string) lvc_config( 'cpt_plural', 'Villas' );

	if ( lvc_config( 'register_cpt', true ) && ! post_type_exists( $cpt ) ) {
		register_post_type( $cpt, array(
			'labels' => array(
				'name'               => $plural,
				'singular_name'      => $singular,
				'menu_name'          => $plural,
				'add_new_item'       => 'Add New ' . $singular,
				'edit_item'          => 'Edit ' . $singular,
				'new_item'           => 'New ' . $singular,
				'view_item'          => 'View ' . $singular,
				'search_items'       => 'Search ' . $plural,
				'not_found'          => 'No ' . strtolower( $plural ) . ' found',
				'not_found_in_trash' => 'No ' . strtolower( $plural ) . ' found in Trash',
				'all_items'          => 'All ' . $plural,
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'has_archive'        => (string) lvc_config( 'cpt_archive_slug', 'luxury-villas' ),
			'rewrite'            => array(
				'slug'       => (string) lvc_config( 'cpt_rewrite_slug', 'luxury-villas' ),
				'with_front' => false,
			),
			'menu_icon'          => 'dashicons-admin-home',
			'supports'           => array( 'title', 'thumbnail', 'revisions' ),
			'hierarchical'       => false,
			'query_var'          => true,
		) );
	}

	if ( lvc_config( 'register_taxonomies', true ) ) {
		foreach ( (array) lvc_config( 'taxonomies', array() ) as $tax => $labels ) {
			if ( taxonomy_exists( $tax ) ) {
				continue;
			}
			$plural_l   = isset( $labels[0] ) ? $labels[0] : ucfirst( $tax );
			$singular_l = isset( $labels[1] ) ? $labels[1] : $plural_l;

			register_taxonomy( $tax, array( $cpt ), array(
				'labels' => array(
					'name'          => $plural_l,
					'singular_name' => $singular_l,
					'menu_name'     => $plural_l,
					'all_items'     => 'All ' . $plural_l,
					'edit_item'     => 'Edit ' . $singular_l,
					'view_item'     => 'View ' . $singular_l,
					'update_item'   => 'Update ' . $singular_l,
					'add_new_item'  => 'Add New ' . $singular_l,
					'new_item_name' => 'New ' . $singular_l . ' Name',
					'search_items'  => 'Search ' . $plural_l,
				),
				'public'            => true,
				'publicly_queryable'=> true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => $tax, 'with_front' => false ),
			) );
		}
	}
}

/**
 * Convenience: the active property post type. Use this in templates/queries
 * instead of a hardcoded 'villa' so brands can switch the CPT in one place.
 */
if ( ! function_exists( 'lvc_property_type' ) ) {
	function lvc_property_type() {
		return (string) lvc_config( 'cpt', 'villa' );
	}
}
