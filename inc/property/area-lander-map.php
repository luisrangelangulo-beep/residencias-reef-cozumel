<?php
/**
 * Residencias Reef Cozumel — Area Lander page-slug map.
 * ─────────────────────────────────────────────────────────────────────────
 * Single source of truth for "which WP Page slug maps to which `area` term",
 * shared by page-templates/area-lander.php and template-parts/editorial-sidebar.php.
 *
 * IMPORTANT: keys are the ACTUAL live WP Page slugs (migrate-in-place = preserve
 * existing URLs); values are the `area` term slugs. The previous version used
 * aspirational "{area}-luxury-villas" keys that did not exist as live pages, so
 * the template's fallback silently resolved 5 landers to the wrong/empty term
 * (Tankah Bay landed on the empty duplicate term and showed 0 villas). Fixed.
 *
 * Only the 13 non-empty area terms get a dedicated lander; empty sub-areas
 * (Downtown Playa del Carmen, and the 0-count duplicate Tankah Bay #111 under
 * Tulum) are intentionally excluded.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_area_lander_map' ) ) {
	function lvc_area_lander_map() {
		return array(
			// live page slug                => area term slug
			'riviera-maya-villa-rentals'      => 'riviera-maya',
			'cozumel'                         => 'cozumel',
			'residencias-reef-condos-cozumel' => 'residencias-reef-cozumel',
			'playa-del-carmen'                => 'playa-del-carmen',
			'playacar'                        => 'playacar',
			'puerto-aventuras'                => 'puerto-aventuras',
			'akumal'                          => 'akumal',
			'tulum-villa-rentals'             => 'tulum', // GSC keeper (1422 impr vs 0 for /tulum/; 301 /tulum/ here)
			'sian-kaan'                       => 'sian-kaan',
			'soliman-bay'                     => 'soliman-bay',
			'tulum-town-jungle-villas'        => 'town-jungle',
			'tulum-beach-zone-villas'         => 'tulum-beach-zone',
			'tankah-bay'                      => 'tankah-bay-riviera-maya',
		);
	}
}

/**
 * Resolve the editable WordPress Page attached to an area term slug.
 *
 * Area cards are editorial links to Page landers, so their manually selected
 * feature images live on the Page rather than on the taxonomy term.
 */
if ( ! function_exists( 'lvc_area_lander_page_id' ) ) {
	function lvc_area_lander_page_id( $area_slug ) {
		$page_slugs = array_keys(
			array_filter(
				lvc_area_lander_map(),
				static function ( $mapped_area_slug ) use ( $area_slug ) {
					return $mapped_area_slug === $area_slug;
				}
			)
		);

		if ( empty( $page_slugs ) ) {
			return 0;
		}

		$page = get_page_by_path( reset( $page_slugs ), OBJECT, 'page' );
		return $page instanceof WP_Post ? (int) $page->ID : 0;
	}
}

/**
 * Best image for an area card.
 *
 * Precedence: lander Page card image, Page featured image/Page hero fallback,
 * taxonomy hero, then a villa assigned to the area.
 */
if ( ! function_exists( 'lvc_area_card_image' ) ) {
	function lvc_area_card_image( $area_slug ) {
		$page_id = lvc_area_lander_page_id( $area_slug );
		if ( $page_id ) {
			$page_image = lvc_page_feature_image( $page_id );
			if ( $page_image ) {
				return $page_image;
			}
		}

		$term = get_term_by( 'slug', $area_slug, 'area' );
		if ( $term instanceof WP_Term ) {
			foreach ( array( 'area_hero_image_url', 'hero_image_url' ) as $field ) {
				$term_image = lvc_field( $field, 'area_' . $term->term_id, '' );
				if ( ! $term_image ) {
					$term_image = lvc_field( $field, 'term_' . $term->term_id, '' );
				}
				if ( $term_image ) {
					return $term_image;
				}
			}
		}

		$query = new WP_Query(
			array(
				'post_type'      => lvc_config( 'cpt', 'villas' ),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array(
					array(
						'taxonomy' => 'area',
						'field'    => 'slug',
						'terms'    => $area_slug,
					),
				),
			)
		);
		$image = $query->have_posts() ? lvc_property_image( $query->posts[0], 'large' ) : '';
		wp_reset_postdata();
		return $image;
	}
}

/**
 * Front-end URL for an `area` term slug, via the lander map above (falls back
 * to the term's own archive URL if it somehow isn't in the map).
 */
if ( ! function_exists( 'lvc_area_lander_url' ) ) {
	function lvc_area_lander_url( $area_slug ) {
		static $flipped = null;
		if ( null === $flipped ) {
			$flipped = array_flip( lvc_area_lander_map() );
		}
		if ( isset( $flipped[ $area_slug ] ) ) {
			return home_url( '/' . $flipped[ $area_slug ] . '/' );
		}
		$term = get_term_by( 'slug', $area_slug, 'area' );
		$link = $term ? get_term_link( $term ) : false;
		return ( $link && ! is_wp_error( $link ) ) ? $link : home_url( '/' );
	}
}
