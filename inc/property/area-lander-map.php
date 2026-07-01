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
