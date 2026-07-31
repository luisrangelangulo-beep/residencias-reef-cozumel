<?php
/**
 * Front page — Residencias Reef Cozumel.
 * Conversion-focused direct booking homepage.
 *
 * Positions Residencias Reef/Cozumel as the trust hook while creating a clear
 * upgrade path into higher-value Riviera Maya and Tulum private villa stays.
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_cpt  = lvc_config( 'cpt', 'villas' );
$lvc_req  = lvc_page_url( 'request' );
$lvc_arch = lvc_archive_url();
$lvc_wa   = lvc_whatsapp_url();

if ( ! function_exists( 'lvc_area_image' ) ) {
	function lvc_area_image( $slug ) {
		if ( function_exists( 'lvc_area_card_image' ) ) {
			return lvc_area_card_image( $slug );
		}

		$term = get_term_by( 'slug', $slug, 'area' );
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
		$q = new WP_Query( array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => $slug ) ),
		) );
		$img = $q->have_posts() ? lvc_property_image( $q->posts[0], 'large' ) : '';
		wp_reset_postdata();
		return $img;
	}
}

if ( ! function_exists( 'lvc_home_term_image' ) ) {
	function lvc_home_term_image( $taxonomy, $slug ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term instanceof WP_Term ) {
			foreach ( array( $taxonomy . '_feature_image_url', 'feature_image_url', $taxonomy . '_hero_image_url', 'hero_image_url' ) as $field ) {
				$img = lvc_field( $field, $taxonomy . '_' . $term->term_id, '' );
				if ( $img ) {
					return $img;
				}
			}
		}

		$q = new WP_Query( array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slug ) ),
		) );
		$img = $q->have_posts() ? lvc_property_image( $q->posts[0], 'large' ) : '';
		wp_reset_postdata();
		return $img;
	}
}

if ( ! function_exists( 'lvc_home_term_description' ) ) {
	function lvc_home_term_description( $taxonomy, $slug, $fallback = '' ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term instanceof WP_Term ) {
			$description = trim( wp_strip_all_tags( term_description( $term->term_id, $taxonomy ) ) );
			if ( $description ) {
				return wp_trim_words( $description, 28, '…' );
			}
		}
		return $fallback;
	}
}

if ( ! function_exists( 'lvc_home_rows' ) ) {
	function lvc_home_rows( $key, $defaults, $columns = 1 ) {
		$value = trim( lvc_site_content( $key, '' ) );
		if ( '' === $value ) {
			return $defaults;
		}
		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$row = array_map( 'trim', explode( '|', $line, $columns ) );
			$row = array_pad( $row, $columns, '' );
			$rows[] = $row;
		}
		return $rows ? $rows : $defaults;
	}
}

$lvc_home_page_id  = (int) get_option( 'page_on_front' );
$lvc_home_hero_img = lvc_site_content( 'home_hero_image' );
if ( ! $lvc_home_hero_img && $lvc_home_page_id ) {
	$lvc_home_hero_img = lvc_page_hero_image( $lvc_home_page_id );
}
if ( ! $lvc_home_hero_img ) {
	$lvc_home_hero_img = lvc_field( 'home_hero_image_url', 'option' );
}
if ( ! $lvc_home_hero_img ) {
	$lvc_home_hero_term = get_term_by( 'slug', 'riviera-maya', 'area' );
	// ACF needs a term reference string; passing the WP_Term object returns null,
	// so this fallback never fired.
	$lvc_home_hero_img  = $lvc_home_hero_term ? lvc_field( 'hero_image_url', 'term_' . $lvc_home_hero_term->term_id ) : '';
}
if ( ! $lvc_home_hero_img ) {
	$lvc_home_hero_img = lvc_area_image( 'riviera-maya' );
}

$lvc_home_hero_kicker     = lvc_site_content( 'home_hero_kicker', lvc_brand() );
$lvc_home_hero_title      = lvc_site_content( 'home_hero_title', 'Residencias Reef Cozumel Beachfront Condos' );
$lvc_home_hero_accent     = lvc_site_content( 'home_hero_accent', '& Handpicked Riviera Maya Villas' );
$lvc_home_hero_intro      = lvc_site_content( 'home_hero_intro', 'Start with relaxed beachfront condos in Cozumel, or let us match your group with private villas in Tulum, Soliman Bay, Tulum Town, Playa del Carmen, Akumal, Puerto Aventuras, and beyond.' );
$lvc_home_primary_label   = lvc_site_content( 'home_primary_label', 'Submit Your Villa Request' );
$lvc_home_secondary_label = lvc_site_content( 'home_secondary_label', 'Browse Villas & Condos' );
$lvc_home_featured_title  = lvc_site_content( 'home_featured_title', 'Cozumel condos & selected Riviera Maya villas' );
$lvc_home_featured_intro  = lvc_site_content( 'home_featured_intro', 'Start with a focused selection, then use the full villa archive or an area page to continue browsing.' );
$lvc_home_match_title     = lvc_site_content( 'home_match_title', 'Tell us your dates. We will shortlist the right stay.' );
$lvc_home_match_intro     = lvc_site_content( 'home_match_intro', 'Share your group size, dates, preferred area, and service needs. We will help decide whether a simple Cozumel condo or a higher-service private villa makes more sense.' );
$lvc_home_match_label     = lvc_site_content( 'home_match_label', 'Get Villa Matches' );
$lvc_home_proof_points    = lvc_home_rows( 'home_proof_points', array(
	array( 'Cozumel condos', 'for simple beachfront stays' ),
	array( 'Private villas', 'for families & groups' ),
	array( 'Tulum areas', 'matched by trip style' ),
	array( 'Chef, transfers & tours', 'on request' ),
), 2 );
$lvc_home_position_title  = lvc_site_content( 'home_position_title', 'Start with Cozumel. Upgrade into the right Riviera Maya villa.' );
$lvc_home_position_intro  = lvc_site_content( 'home_position_intro', 'Residencias Reef Cozumel is a useful entry point for couples, divers, and smaller groups who want a relaxed beachfront condo. But many travelers need more space, service, privacy, or a better location for a family trip, celebration, retreat, or luxury stay.' );
$lvc_home_position_points = lvc_home_rows( 'home_position_points', array(
	array( 'Condos for simpler Cozumel stays with beach access and practical nightly rates.' ),
	array( 'Private villas for larger groups, chef service, private pools, events, and higher-service trips.' ),
	array( 'Clear guidance across Cozumel, Tulum, Soliman Bay, Tulum Town, Playa del Carmen, Akumal, and Puerto Aventuras.' ),
) );
$lvc_home_paths_title     = lvc_site_content( 'home_paths_title', 'Choose the stay that fits the real trip' );
$lvc_home_paths_intro     = lvc_site_content( 'home_paths_intro', 'A beachfront condo can be perfect for a low-friction Cozumel stay. A private villa is usually the better fit when the group, budget, and service expectations are higher.' );
$lvc_home_paths_cards     = lvc_home_rows( 'home_paths_cards', array(
	array( 'Cozumel Hook', 'Residencias Reef condos', 'Best for couples, small families, divers, and guests who want beach access, a kitchen, pool access, and a quieter island base without paying for a full private villa.', 'View Cozumel Stays' ),
	array( 'Money Path', 'Private Riviera Maya villas', 'Better for families, larger groups, celebrations, retreats, and guests who want private pools, more bedrooms, chef service, staff, and a more exclusive setting.', 'Request Villa Matches' ),
), 4 );
$lvc_home_upgrade_title   = lvc_site_content( 'home_upgrade_title', 'When a private villa is the better fit' );
$lvc_home_upgrade_intro   = lvc_site_content( 'home_upgrade_intro', 'Condos are easy and affordable. Villas are where space, privacy, service, and trip value become much stronger.' );
$lvc_home_upgrade_cards   = lvc_home_rows( 'home_upgrade_cards', array(
	array( '8+ Guests', 'More bedrooms and privacy', 'Groups usually need larger layouts, multiple living areas, private pools, and fewer shared spaces.' ),
	array( 'Chef Service', 'Meals become part of the stay', 'Private villas work better for breakfast service, celebrations, dinners, groceries, and custom dining.' ),
	array( 'Special Occasions', 'Better for celebrations', 'Birthdays, family reunions, retreats, and milestone trips need a property that supports the full experience.' ),
), 3 );
$lvc_home_area_title      = lvc_site_content( 'home_area_title', 'Choose the right Riviera Maya area' );
$lvc_home_area_intro      = lvc_site_content( 'home_area_intro', 'The right villa starts with the right location. These are the areas most guests compare first.' );
$lvc_home_collection_title = lvc_site_content( 'home_collection_title', 'High-intent villa collections' );
$lvc_home_collection_intro = lvc_site_content( 'home_collection_intro', 'These are the commercial pages guests use when they already know the kind of trip they want.' );
$lvc_home_tulum_title      = lvc_site_content( 'home_tulum_title', 'Tulum is not one market: match the area to the trip' );
$lvc_home_tulum_intro      = lvc_site_content( 'home_tulum_intro', 'For higher-value villa inquiries, the most important question is often whether the group should stay beachfront, close to restaurants, in a quieter bay, or in town.' );
$lvc_home_compare_title    = lvc_site_content( 'home_compare_title', 'Cozumel condo or private villa?' );
$lvc_home_compare_intro    = lvc_site_content( 'home_compare_intro', 'This comparison helps guests self-select, which protects the low-cost condo traffic while pushing qualified groups toward better villa inquiries.' );
$lvc_home_compare_condo_title = lvc_site_content( 'home_compare_condo_title', 'Choose a Cozumel condo if...' );
$lvc_home_compare_condo_points = lvc_home_rows( 'home_compare_condo_points', array(
	array( 'You are a couple, small family, or diving-focused group.' ),
	array( 'You want a simple beachfront base at a lower nightly rate.' ),
	array( 'You do not need chef service, a large private pool, or full villa staff.' ),
) );
$lvc_home_compare_villa_title = lvc_site_content( 'home_compare_villa_title', 'Choose a private villa if...' );
$lvc_home_compare_villa_points = lvc_home_rows( 'home_compare_villa_points', array(
	array( 'You need more bedrooms, privacy, and living space.' ),
	array( 'You want chef service, staff, groceries, transfers, or celebration planning.' ),
	array( 'Your group is comparing Tulum, Soliman Bay, Akumal, Playa del Carmen, or Puerto Aventuras.' ),
) );
$lvc_home_steps_title      = lvc_site_content( 'home_steps_title', 'A simpler way to book a private villa' );
$lvc_home_steps            = lvc_home_rows( 'home_steps', array(
	array( 'Share your trip details', 'Tell us your dates, group size, bedroom needs, preferred area, and service needs.' ),
	array( 'Review matched villas', 'We help compare realistic options based on location, layout, service level, privacy, and fit.' ),
	array( 'Plan the stay', 'Concierge planning can include airport transfers, private chef, groceries, tours, spa, diving, and activities.' ),
), 2 );
$lvc_home_concierge_title  = lvc_site_content( 'home_concierge_title', 'Beyond the villa: complete stay planning' );
$lvc_home_concierge_intro  = lvc_site_content( 'home_concierge_intro', 'Luxury villa trips work best when the details are handled before arrival.' );
$lvc_home_concierge_cards  = lvc_home_rows( 'home_concierge_cards', array(
	array( 'Airport Transfers', 'Private transportation from Cancún International Airport and Cozumel arrival points.' ),
	array( 'Private Chef', 'In-villa dining, celebrations, breakfast service, and group meals.' ),
	array( 'Diving & Snorkeling', 'Cozumel reefs, cenotes, Riviera Maya snorkeling, and Caribbean boat days.' ),
	array( 'Tours & Activities', 'Mayan ruins, beach clubs, fishing, cenotes, ATVs, and family activities.' ),
	array( 'Spa & Wellness', 'In-villa massage, wellness sessions, yoga, and relaxation services.' ),
), 2 );
$lvc_home_final_title      = lvc_site_content( 'home_final_title', 'Tell us what your group needs. We will help narrow the search.' );
$lvc_home_final_intro      = lvc_site_content( 'home_final_intro', 'Share your dates, preferred area, group size, and villa priorities. We will help identify whether a Cozumel condo or a stronger Riviera Maya villa option makes the most sense.' );
$lvc_home_final_primary_label = lvc_site_content( 'home_final_primary_label', 'Request Villa Matches' );
$lvc_home_final_secondary_label = lvc_site_content( 'home_final_secondary_label', 'Chat on WhatsApp' );

$lvc_area_cards = array(
	array( 'Cozumel', 'cozumel', '/cozumel/', 'Residencias Reef condos and island stays for divers, couples, and guests who want a relaxed beachfront base.' ),
	array( 'Tulum', 'tulum', '/tulum-villa-rentals/', 'Private villas across Tulum Beach, Soliman Bay, Tankah Bay, Tulum Town, Aldea Zama, La Veleta, and Sian Ka\'an.' ),
	array( 'Playa del Carmen', 'playa-del-carmen', '/playa-del-carmen/', 'Beachfront and Playacar villas close to dining, beach clubs, nightlife, shopping, and easy arrival logistics.' ),
	array( 'Akumal', 'akumal', '/akumal/', 'Bayfront and beachfront villas for families, snorkeling trips, quieter beach stays, and multi-generational groups.' ),
);

$lvc_tulum_areas = array(
	array( 'Soliman Bay', 'soliman-bay', '/soliman-bay/', 'Quiet beachfront villas north of Tulum, often chosen for family trips, calm water, chef service, and more privacy than the main beach zone.' ),
	array( 'Tulum Beach Zone', 'tulum-beach-zone', '/tulum-beach-zone-villas/', 'For guests who want the restaurants, beach clubs, wellness scene, and boutique-hotel atmosphere close by.' ),
	array( 'Tulum Town & Jungle', 'town-jungle', '/tulum-town-jungle-villas/', 'Tulum Town, Aldea Zama, La Veleta, and jungle settings with easier logistics, restaurant access, and flexible options for groups.' ),
	array( 'Tankah Bay', 'tankah-bay-riviera-maya', '/tankah-bay/', 'Beachfront and bayfront villas between Tulum and Akumal, with a quieter residential feel and good access to snorkeling and cenotes.' ),
	array( 'Sian Ka’an', 'sian-kaan', '/sian-kaan/', 'Remote beachfront and nature-focused stays for groups prioritizing privacy, seclusion, and the Sian Ka’an setting.' ),
);

$lvc_collection_filters = array(
	array( 'All Villas', $lvc_arch ),
	array( 'Cozumel', home_url( '/cozumel/' ) ),
	array( 'Tulum', home_url( '/tulum-villa-rentals/' ) ),
	array( 'Soliman Bay', home_url( '/soliman-bay/' ) ),
	array( 'Tulum Town', home_url( '/tulum-town-jungle-villas/' ) ),
	array( 'Playa del Carmen', home_url( '/playa-del-carmen/' ) ),
	array( 'Akumal', home_url( '/akumal/' ) ),
	array( 'Puerto Aventuras', home_url( '/puerto-aventuras/' ) ),
);

$lvc_collections = array(
	array( 'Large-Group Villas', 'large-groups', home_url( '/collections/large-groups/' ), 'Villas that sleep 12+ for reunions, weddings, and multi-family trips.', 'https://pub-cad2340af7894206a8fbca2d29a27967.r2.dev/chef%20and%20services/happy-multiracial-families-and-children-playing-to-2026-03-09-03-24-29-utc.webp' ),
	array( 'Villas with a Private Chef', 'private-chef', home_url( '/collections/private-chef/' ), 'In-villa chef service across the Riviera Maya.' ),
	array( 'Beachfront Villas', 'beachfront', home_url( '/collections/beachfront/' ), 'Direct beach and oceanfront access.' ),
	array( 'Family Villas', 'family-villas', home_url( '/collections/family-villas/' ), 'Space, safety, and easy logistics for families.' ),
);
?>

<style>
	.lcv-home-modern{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}
	.lcv-home-modern *{box-sizing:border-box}.lcv-home-wrap{width:min(100%,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lcv-home-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lcv-home-section{padding:clamp(4rem,7vw,7rem) 0}.lcv-home-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lcv-home-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lcv-home-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.65rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lcv-home-title em{font-style:italic;color:var(--lvc-accent)}.lcv-home-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.25vw,1.08rem);font-weight:300;line-height:1.82}.lcv-home-head{text-align:center;margin:0 auto clamp(2rem,4vw,3rem);max-width:900px}.lcv-home-head .lcv-home-copy{max-width:760px;margin:1rem auto 0}.lcv-home-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center}.lcv-home-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lcv-home-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
	.lcv-home-hero{position:relative;min-height:min(720px,82vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--home-hero-img,none) center/cover no-repeat}.lcv-home-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 48%,rgba(10,12,15,.48)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.25) 52%,rgba(10,12,15,.64))}.lcv-home-hero__grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.42fr);gap:clamp(2rem,5vw,5rem);align-items:end}.lcv-home-hero h1{max-width:920px;margin:0;font-family:var(--lvc-font-display);font-size:clamp(2.55rem,5vw,5.15rem);font-weight:200;line-height:1.08;color:var(--lvc-text)}.lcv-home-hero h1 em{display:block;margin-top:.25rem;font-style:italic;color:var(--lvc-accent)}.lcv-home-hero__sub{max-width:780px;margin:1.35rem 0 0;color:rgba(243,243,241,.84);font-size:clamp(1rem,1.3vw,1.13rem);line-height:1.78}.lcv-home-hero__actions{margin-top:1.8rem}.lcv-home-match{background:linear-gradient(180deg,rgba(16,21,28,.95),rgba(10,12,15,.88));border:1px solid rgba(255,255,255,.14);padding:1.5rem;box-shadow:0 24px 70px rgba(0,0,0,.36)}.lcv-home-match h2{margin:0 0 .6rem;font-family:var(--lvc-font-display);font-weight:300;font-size:1.25rem;color:var(--lvc-text)}.lcv-home-match p{margin:0;color:var(--lvc-soft);font-size:.9rem;line-height:1.7}.lcv-home-match__facts{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;margin:1.1rem 0}.lcv-home-match__fact{border:1px solid var(--lvc-border);background:rgba(255,255,255,.035);padding:.85rem}.lcv-home-match__fact strong{display:block;font-family:var(--lvc-font-display);font-size:1.45rem;font-weight:300;color:var(--lvc-text);line-height:1}.lcv-home-match__fact span{display:block;margin-top:.35rem;color:var(--lvc-muted);font-size:.66rem;letter-spacing:.12em;text-transform:uppercase}
	.lcv-proof{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border);padding:1.15rem 0}.lvc-proof__inner{display:flex;flex-wrap:wrap;justify-content:center;gap:.8rem}.lvc-proof__item{border:1px solid var(--lvc-border);padding:.6rem .85rem;color:var(--lvc-soft);font-size:.78rem;text-transform:uppercase}.lvc-proof__item strong{color:var(--lvc-accent);font-weight:500}.lcv-intro-grid{display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);gap:clamp(2rem,5vw,5rem);align-items:center}.lcv-home-panel{border-left:1px solid var(--lvc-border);padding-left:clamp(1.5rem,3vw,3rem)}.lcv-home-panel ul{list-style:none;margin:1.35rem 0 0;padding:0;display:grid;gap:.8rem}.lcv-home-panel li{position:relative;padding-left:1.25rem;color:var(--lvc-soft);line-height:1.65}.lcv-home-panel li:before{content:'\2713';position:absolute;left:0;color:var(--lvc-accent)}
	.lcv-path-grid,.lcv-compare{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.lcv-upgrade-grid,.lcv-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.lcv-concierge-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.9rem}.lcv-path-card,.lcv-upgrade-card,.lcv-step,.lcv-concierge-card,.lcv-compare-card{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.35rem}.lcv-path-card h3,.lcv-upgrade-card h3,.lcv-step h3,.lcv-concierge-card h3,.lcv-compare-card h3{margin:0 0 .6rem;font-family:var(--lvc-font-display);font-weight:300;color:var(--lvc-text)}.lcv-path-card p,.lcv-upgrade-card p,.lcv-step p,.lcv-concierge-card p{margin:0;color:var(--lvc-soft);line-height:1.65;font-size:.92rem}.lcv-upgrade-card span,.lcv-step__num{display:block;color:var(--lvc-accent);font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;margin-bottom:.6rem}.lcv-compare-card ul{margin:0;padding:0;list-style:none;display:grid;gap:.7rem}.lcv-compare-card li{position:relative;padding-left:1.1rem;color:var(--lvc-soft);line-height:1.55}.lcv-compare-card li:before{content:'\2022';position:absolute;left:0;color:var(--lvc-accent)}
	.lcv-filter-pills{display:flex;flex-wrap:wrap;justify-content:center;gap:.65rem;margin:0 auto 2.2rem;max-width:1040px}.lcv-filter-pill{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--lvc-border);background:rgba(255,255,255,.025);color:var(--lvc-soft)!important;padding:.62rem .9rem;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}.lcv-filter-pill:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important;background:var(--lvc-accent-soft)}.lcv-villa-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.35rem}.lcv-home-pagination{display:flex;justify-content:center;align-items:center;gap:.45rem;margin-top:2.5rem;flex-wrap:wrap}.lcv-home-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:42px;padding:.55rem .8rem;border:1px solid var(--lvc-border);color:var(--lvc-soft);background:rgba(255,255,255,.02)}.lcv-home-pagination .page-numbers.current{background:var(--lvc-accent);border-color:var(--lvc-accent);color:#fff}
	.lcv-area-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.lcv-tulum-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:1rem}.lcv-tulum-grid>.lcv-area-tile{grid-column:span 2}.lcv-tulum-grid>.lcv-area-tile:nth-last-child(2){grid-column:1/span 3}.lcv-tulum-grid>.lcv-area-tile:last-child{grid-column:4/span 3}.lcv-area-tile{position:relative;min-height:330px;display:flex;align-items:flex-end;padding:1.35rem;border:1px solid var(--lvc-border);background:var(--lvc-card) var(--area-img,none) center/cover no-repeat;overflow:hidden}.lcv-tulum-grid .lcv-area-tile{min-height:285px}.lcv-area-tile:before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,12,15,.12),rgba(10,12,15,.92))}.lcv-area-tile__body{position:relative;z-index:1}.lcv-area-tile h3{margin:0;font-family:var(--lvc-font-display);font-size:1.35rem;font-weight:300;color:var(--lvc-text)}.lcv-area-tile p{margin:.55rem 0 0;color:var(--lvc-soft);font-size:.86rem;line-height:1.55}.lcv-area-tile span{display:block;margin-top:.85rem;color:var(--lvc-accent);font-size:.82rem}.lcv-final-cta{background:var(--lvc-bg-deep);border-top:1px solid rgba(255,255,255,.12);text-align:center}.lcv-final-cta .lcv-home-copy{max-width:680px;margin:1rem auto 1.6rem}.lcv-final-cta .lcv-home-btns{justify-content:center}
	.lcv-home-compare{text-align:center;max-width:760px;margin:2.2rem auto 0;color:var(--lvc-soft);font-size:.95rem;line-height:1.7}.lcv-home-compare a{color:var(--lvc-accent);text-decoration:underline;text-underline-offset:3px}.lcv-home-compare a:hover{color:var(--lvc-accent-hover)}
	@media(max-width:1100px){.lcv-home-hero__grid,.lcv-intro-grid,.lcv-path-grid,.lcv-compare{grid-template-columns:1fr}.lcv-area-grid,.lcv-villa-grid,.lcv-tulum-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.lcv-tulum-grid>.lcv-area-tile,.lcv-tulum-grid>.lcv-area-tile:nth-last-child(2),.lcv-tulum-grid>.lcv-area-tile:last-child{grid-column:auto}.lcv-tulum-grid>.lcv-area-tile:last-child{grid-column:1/-1}.lcv-upgrade-grid,.lcv-concierge-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.lcv-home-panel{border-left:0;padding-left:0}}@media(max-width:720px){.lcv-home-wrap,.lcv-home-narrow{width:calc(100% - 2rem)}.lcv-home-hero{min-height:auto;padding:6rem 0 4rem}.lcv-home-hero h1{font-size:clamp(2.3rem,12vw,3.5rem)}.lcv-home-btns{display:grid;grid-template-columns:1fr}.lcv-home-match__facts,.lcv-area-grid,.lcv-steps,.lcv-concierge-grid,.lcv-villa-grid,.lcv-path-grid,.lcv-upgrade-grid,.lcv-tulum-grid,.lcv-compare{grid-template-columns:1fr}.lcv-tulum-grid>.lcv-area-tile:last-child{grid-column:auto}.lvc-proof__item{width:100%;text-align:center}.lcv-filter-pills{justify-content:flex-start}}
</style>

<main class="lvc-home-modern">
	<section class="lcv-home-hero" <?php echo $lvc_home_hero_img ? 'style="--home-hero-img:url(\'' . esc_url( $lvc_home_hero_img ) . '\')"' : ''; ?> aria-label="Residencias Reef Cozumel and private Riviera Maya villa rentals"><div class="lcv-home-wrap lcv-home-hero__grid"><div><span class="lcv-home-kicker"><?php echo esc_html( $lvc_home_hero_kicker ); ?></span><h1><?php echo esc_html( $lvc_home_hero_title ); ?> <em><?php echo esc_html( $lvc_home_hero_accent ); ?></em></h1><p class="lcv-home-hero__sub"><?php echo esc_html( $lvc_home_hero_intro ); ?></p><div class="lcv-home-btns lcv-home-hero__actions"><a class="lcv-home-btn" href="<?php echo esc_url( $lvc_req ); ?>"><?php echo esc_html( $lvc_home_primary_label ); ?></a><a class="lcv-home-btn lcv-home-btn--ghost" href="<?php echo esc_url( $lvc_arch ); ?>"><?php echo esc_html( $lvc_home_secondary_label ); ?></a></div></div><aside class="lcv-home-match"><h2><?php echo esc_html( $lvc_home_match_title ); ?></h2><p><?php echo esc_html( $lvc_home_match_intro ); ?></p><div class="lcv-home-match__facts"><div class="lcv-home-match__fact"><strong>Direct</strong><span>No OTA markup</span></div><div class="lcv-home-match__fact"><strong>Local</strong><span>Villa guidance</span></div></div><a class="lcv-home-btn" href="<?php echo esc_url( $lvc_req ); ?>"><?php echo esc_html( $lvc_home_match_label ); ?></a></aside></div></section>
	<?php if ( function_exists( 'lvc_render_filter_bar' ) ) { lvc_render_filter_bar(); } ?>

	<section class="lcv-proof"><div class="lcv-home-wrap lvc-proof__inner"><?php foreach ( $lvc_home_proof_points as $point ) : ?><div class="lvc-proof__item"><strong><?php echo esc_html( $point[0] ); ?></strong> <?php echo esc_html( $point[1] ); ?></div><?php endforeach; ?></div></section>

	<section class="lcv-home-section" id="feat"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Featured Stays</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_featured_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_featured_intro ); ?></p></header><nav class="lcv-filter-pills" aria-label="Villa collection filters"><?php foreach ( $lvc_collection_filters as $filter ) : ?><a class="lcv-filter-pill" href="<?php echo esc_url( $filter[1] ); ?>"><?php echo esc_html( $filter[0] ); ?></a><?php endforeach; ?></nav><?php $lvc_cozumel = new WP_Query( array( 'post_type' => $lvc_cpt, 'posts_per_page' => 3, 'post_status' => 'publish', 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => 'date', 'order' => 'DESC', 'tax_query' => array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => 'cozumel', 'include_children' => true ) ) ) ); $lvc_featured_ids = array_map( 'intval', $lvc_cozumel->posts ); $lvc_more = new WP_Query( array( 'post_type' => $lvc_cpt, 'posts_per_page' => 6, 'post_status' => 'publish', 'fields' => 'ids', 'no_found_rows' => true, 'post__not_in' => $lvc_featured_ids, 'orderby' => 'date', 'order' => 'DESC' ) ); $lvc_featured_ids = array_merge( $lvc_featured_ids, array_map( 'intval', $lvc_more->posts ) ); $lvc_villas = new WP_Query( array( 'post_type' => $lvc_cpt, 'posts_per_page' => 9, 'post_status' => 'publish', 'post__in' => $lvc_featured_ids, 'orderby' => 'post__in', 'no_found_rows' => true ) ); if ( $lvc_villas->have_posts() ) : ?><div class="lcv-villa-grid"><?php while ( $lvc_villas->have_posts() ) : $lvc_villas->the_post(); get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); endwhile; ?></div><div class="lcv-home-btns" style="justify-content:center;margin-top:2rem"><a class="lcv-home-btn lcv-home-btn--ghost" href="<?php echo esc_url( $lvc_arch ); ?>">View All Villas &amp; Condos</a></div><?php wp_reset_postdata(); endif; ?></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap lcv-intro-grid"><div><span class="lcv-home-kicker">Direct Booking Collection</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_position_title ); ?></h2></div><div class="lcv-home-panel lcv-home-copy"><p><?php echo esc_html( $lvc_home_position_intro ); ?></p><ul><?php foreach ( $lvc_home_position_points as $point ) : ?><li><?php echo esc_html( $point[0] ); ?></li><?php endforeach; ?></ul></div></div></section>

	<section class="lcv-home-section"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Two Booking Paths</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_paths_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_paths_intro ); ?></p></header><div class="lcv-path-grid"><?php foreach ( $lvc_home_paths_cards as $index => $card ) : ?><article class="lcv-path-card"><span class="lcv-home-kicker"><?php echo esc_html( $card[0] ); ?></span><h3><?php echo esc_html( $card[1] ); ?></h3><p><?php echo esc_html( $card[2] ); ?></p><a class="lcv-home-btn<?php echo 0 === $index ? ' lcv-home-btn--ghost' : ''; ?>" href="<?php echo esc_url( 0 === $index ? home_url( '/cozumel/' ) : $lvc_req ); ?>"><?php echo esc_html( $card[3] ); ?></a></article><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">When to Upgrade</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_upgrade_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_upgrade_intro ); ?></p></header><div class="lcv-upgrade-grid"><?php foreach ( $lvc_home_upgrade_cards as $card ) : ?><article class="lcv-upgrade-card"><span><?php echo esc_html( $card[0] ); ?></span><h3><?php echo esc_html( $card[1] ); ?></h3><p><?php echo esc_html( $card[2] ); ?></p></article><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Where to Stay</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_area_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_area_intro ); ?></p></header><div class="lcv-area-grid"><?php foreach ( $lvc_area_cards as $area ) : $area_img = lvc_area_image( $area[1] ); $area_desc = lvc_home_term_description( 'area', $area[1], $area[3] ); ?><a class="lcv-area-tile" href="<?php echo esc_url( home_url( $area[2] ) ); ?>" style="<?php echo $area_img ? '--area-img:url(\'' . esc_url( $area_img ) . '\')' : ''; ?>"><div class="lcv-area-tile__body"><h3><?php echo esc_html( $area[0] ); ?></h3><p><?php echo esc_html( $area_desc ); ?></p><span>Explore <?php echo esc_html( $area[0] ); ?> &rarr;</span></div></a><?php endforeach; ?></div><p class="lcv-home-compare">Not sure which to choose? Compare <a href="<?php echo esc_url( home_url( '/cozumel-vs-tulum-vs-playa-del-carmen-villa-rentals/' ) ); ?>">Cozumel vs Tulum vs Playa del Carmen</a> or <a href="<?php echo esc_url( home_url( '/tulum-vs-playa-del-carmen-comparison-guide/' ) ); ?>">Tulum vs Playa del Carmen</a>.</p></div></section>

	<section class="lcv-home-section"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Browse by Collection</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_collection_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_collection_intro ); ?></p></header><div class="lcv-area-grid"><?php foreach ( $lvc_collections as $collection ) : $collection_img = lvc_home_term_image( 'collection', $collection[1] ); if ( ! $collection_img && ! empty( $collection[4] ) ) { $collection_img = $collection[4]; } $collection_desc = lvc_home_term_description( 'collection', $collection[1], $collection[3] ); ?><a class="lcv-area-tile" href="<?php echo esc_url( $collection[2] ); ?>" style="<?php echo $collection_img ? '--area-img:url(\'' . esc_url( $collection_img ) . '\')' : ''; ?>"><div class="lcv-area-tile__body"><h3><?php echo esc_html( $collection[0] ); ?></h3><p><?php echo esc_html( $collection_desc ); ?></p><span>Explore collection &rarr;</span></div></a><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Tulum Villa Areas</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_tulum_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_tulum_intro ); ?></p></header><div class="lcv-tulum-grid"><?php foreach ( $lvc_tulum_areas as $area ) : $area_img = lvc_area_image( $area[1] ); $area_desc = lvc_home_term_description( 'area', $area[1], $area[3] ); ?><a class="lcv-area-tile" href="<?php echo esc_url( home_url( $area[2] ) ); ?>" style="<?php echo $area_img ? '--area-img:url(\'' . esc_url( $area_img ) . '\')' : ''; ?>"><div class="lcv-area-tile__body"><h3><?php echo esc_html( $area[0] ); ?></h3><p><?php echo esc_html( $area_desc ); ?></p><span>Explore <?php echo esc_html( $area[0] ); ?> villas &rarr;</span></div></a><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Cozumel or Villa Upgrade</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_compare_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_compare_intro ); ?></p></header><div class="lcv-compare"><article class="lcv-compare-card"><h3><?php echo esc_html( $lvc_home_compare_condo_title ); ?></h3><ul><?php foreach ( $lvc_home_compare_condo_points as $point ) : ?><li><?php echo esc_html( $point[0] ); ?></li><?php endforeach; ?></ul></article><article class="lcv-compare-card"><h3><?php echo esc_html( $lvc_home_compare_villa_title ); ?></h3><ul><?php foreach ( $lvc_home_compare_villa_points as $point ) : ?><li><?php echo esc_html( $point[0] ); ?></li><?php endforeach; ?></ul></article></div></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">How It Works</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_steps_title ); ?></h2></header><div class="lcv-steps"><?php foreach ( $lvc_home_steps as $index => $step ) : ?><div class="lcv-step"><span class="lcv-step__num"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><h3><?php echo esc_html( $step[0] ); ?></h3><p><?php echo esc_html( $step[1] ); ?></p></div><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Concierge Services</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_concierge_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_concierge_intro ); ?></p></header><div class="lcv-concierge-grid"><?php foreach ( $lvc_home_concierge_cards as $card ) : ?><div class="lcv-concierge-card"><h3><?php echo esc_html( $card[0] ); ?></h3><p><?php echo esc_html( $card[1] ); ?></p></div><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section lcv-final-cta"><div class="lcv-home-narrow"><span class="lcv-home-kicker">Start Planning</span><h2 class="lcv-home-title"><?php echo esc_html( $lvc_home_final_title ); ?></h2><p class="lcv-home-copy"><?php echo esc_html( $lvc_home_final_intro ); ?></p><div class="lcv-home-btns"><a class="lcv-home-btn" href="<?php echo esc_url( $lvc_req ); ?>"><?php echo esc_html( $lvc_home_final_primary_label ); ?></a><?php if ( $lvc_wa ) : ?><a class="lcv-home-btn lcv-home-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $lvc_home_final_secondary_label ); ?></a><?php endif; ?></div></div></section>
</main>
<?php get_footer(); ?>
