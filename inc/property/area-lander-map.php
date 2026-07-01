<?php
/**
 * Residencias Reef Cozumel — Area Lander page-slug map.
 * ─────────────────────────────────────────────────────────────────────────
 * Single source of truth for "which WP Page slug maps to which `area` term",
 * shared by page-templates/area-lander.php and template-parts/editorial-sidebar.php
 * (ported from Los Cabos, which duplicated this list in both files — kept here
 * once instead). Only the 13 non-empty area terms get a dedicated lander;
 * sub-areas that are empty (Downtown Playa del Carmen, and the 0-count
 * duplicate Tankah Bay under Tulum) are excluded per the confirmed scope.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_area_lander_map' ) ) {
	function lvc_area_lander_map() {
		return array(
			'riviera-maya-luxury-villas'    => 'riviera-maya',
			'cozumel-luxury-villas'         => 'cozumel',
			'residencias-reef-cozumel'      => 'residencias-reef-cozumel',
			'playa-del-carmen-luxury-villas'=> 'playa-del-carmen',
			'playacar-luxury-villas'        => 'playacar',
			'puerto-aventuras-luxury-villas'=> 'puerto-aventuras',
			'akumal-luxury-villas'          => 'akumal',
			'tulum-luxury-villas'           => 'tulum',
			'sian-kaan-luxury-villas'       => 'sian-kaan',
			'soliman-bay-luxury-villas'     => 'soliman-bay',
			'town-jungle-luxury-villas'     => 'town-jungle',
			'tulum-beach-zone-luxury-villas'=> 'tulum-beach-zone',
			'tankah-bay-luxury-villas'      => 'tankah-bay-riviera-maya',
		);
	}
}
