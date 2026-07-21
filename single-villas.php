<?php
/**
 * Residencias Reef Cozumel — Single Villa Template.
 *
 * Layout ported from Los Cabos's single-villas.php (same section structure,
 * same rrc-/lvc- component classes). Data logic re-matched to this site's
 * actual live fields/taxonomy: only `area` (hierarchical: Riviera Maya >
 * Cozumel/Tulum/Playa Del Carmen/etc. > sub-areas) + `bedrooms` exist here —
 * no separate `destination`/`amenities`/`beach_access` taxonomies.
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$villa_id = get_the_ID();

// ACF fields — names match the live "Property fields" group on this site.
$h1_title             = get_field( 'h1_property_title' ) ? get_field( 'h1_property_title' ) : get_the_title();
$bedrooms             = get_field( 'bedrooms' );
$bathrooms            = get_field( 'bathrooms' );
$max_guests           = get_field( 'max_guests' );
$view_type            = get_field( 'view_type' );
$community            = get_field( 'community' );
$property_description = get_field( 'property_description' );
$indoor_living        = get_field( 'indoor_living' );
$outdoor_living       = get_field( 'outdoor_living' );
$bedroom_description  = get_field( 'bedroom_description' );
$destination_description = get_field( 'destination_description' );
$gallery_slider       = get_field( 'gallery_slider' );
$gallery_squares      = get_field( 'gallery_squares' );

// `area` is hierarchical and plays both the "destination" and "area" role:
// e.g. Cozumel (parent) > Residencias Reef Cozumel (child, this villa's term).
// Villas are tagged at every level at once, so pick the most specific one
// (e.g. "Soliman Bay", not "Riviera Maya" — get_the_terms() order isn't reliable).
$area_term = lvc_property_area_term( $villa_id );

$region_term = null; // top-level ancestor, e.g. "Riviera Maya".
$area_ancestors = array();
if ( $area_term ) {
	$ancestor_ids = get_ancestors( $area_term->term_id, 'area' );
	foreach ( array_reverse( $ancestor_ids ) as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, 'area' );
		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			$area_ancestors[] = $ancestor;
		}
	}
	$region_term = ! empty( $area_ancestors ) ? $area_ancestors[0] : $area_term;
}

$area_name    = $area_term ? $area_term->name : '';
$region_name  = $region_term ? $region_term->name : lvc_config( 'region', '' );
$location_line = ( $area_name && $area_name !== $region_name ) ? $area_name . ', ' . $region_name : ( $area_name ?: $region_name );

$area_url = $area_term ? lvc_area_lander_url_by_term( $area_term ) : lvc_archive_url();

$best_for_text = (int) $max_guests >= 10 ? 'Best for families and groups' : 'Best for couples and families';
$beach_positioning = $area_name ?: $region_name;

$lvc_parse_gallery_urls = static function( $raw ) {
	if ( ! $raw || ! is_string( $raw ) ) {
		return array();
	}
	$urls = array();
	foreach ( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $raw ) ) ) as $url ) {
		if ( preg_match( '#^https?://#i', $url ) ) {
			$urls[] = $url;
		}
	}
	return array_values( array_unique( $urls ) );
};

$gallery_slider_urls = $lvc_parse_gallery_urls( $gallery_slider );
$gallery_square_urls = $lvc_parse_gallery_urls( $gallery_squares );
$all_gallery_urls    = array_values( array_unique( array_merge( $gallery_slider_urls, $gallery_square_urls ) ) );
$photo_count         = count( $all_gallery_urls );

// Hero prefers its own field; lvc_property_image() leads with feature_image,
// which is the card crop and not necessarily the right full-bleed shot.
$hero_image = trim( (string) get_post_meta( $villa_id, 'hero_image', true ) );
if ( ! $hero_image ) {
	$hero_image = lvc_property_image( $villa_id, 'full' );
}
if ( ! $hero_image && $all_gallery_urls ) {
	$hero_image = $all_gallery_urls[0];
}

$property_url = get_permalink();
$whatsapp_url = lvc_whatsapp_url();

// True only when an editor has written real positioning copy. The hero lede
// falls back to a generated sentence; the positioning section must not repeat
// that same sentence further down the page.
$has_real_positioning = trim( (string) $destination_description ) !== '';
$intro_quote = $destination_description ? wp_strip_all_tags( $destination_description ) : ( $h1_title . ' gives your group a private base in ' . ( $location_line ?: 'the Riviera Maya' ) . ' with space to gather, concierge support, and easy access to beaches and dining.' );

$hero_chips = array_filter( array_unique( array(
	$bedrooms ? (int) $bedrooms . ' Bedrooms' : '',
	$max_guests ? 'Sleeps ' . (int) $max_guests : '',
	$bathrooms ? $bathrooms . ' Bathrooms' : '',
	$beach_positioning,
) ) );

// Related villas: same area first, then the region ancestor, then site-wide.
$related_villas = null;
$related_tier   = '';
$related_args   = array(
	'post_type'      => lvc_config( 'cpt', 'villas' ),
	'post_status'    => 'publish',
	'posts_per_page' => 8,
	'post__not_in'   => array( $villa_id ),
);

/**
 * Pick the related villas for a tier, rotating the window by villa ID rather
 * than always returning the newest.
 *
 * With 'orderby' => 'date' every villa in an area linked to the same newest
 * villas. Measured across all 150 published villas: only 42 ever received a
 * related-villa link, 108 received none, and a single villa collected 108.
 * Rotating the starting point spreads that link equity far more evenly.
 *
 * Deterministic: the same villa always shows the same set, so pages stay
 * cache-stable and Googlebot sees consistent links between crawls.
 *
 * @param array $args Tier args; tax_query and posts_per_page are read.
 * @return WP_Query|null Null when the tier has no candidates.
 */
$rrc_related_rotate = static function ( array $args ) use ( $villa_id ) {
	$limit     = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 8;
	$pool_args = array(
		'post_type'      => lvc_config( 'cpt', 'villas' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post__not_in'   => array( $villa_id ),
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);
	if ( ! empty( $args['tax_query'] ) ) {
		$pool_args['tax_query'] = $args['tax_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$pool = get_posts( $pool_args );
	if ( empty( $pool ) ) {
		return null;
	}

	sort( $pool, SORT_NUMERIC );
	$total  = count( $pool );
	$take   = min( $limit, $total );
	$offset = abs( (int) $villa_id ) % $total;
	$picked = array();
	for ( $i = 0; $i < $take; $i++ ) {
		$picked[] = $pool[ ( $offset + $i ) % $total ];
	}

	$query = new WP_Query(
		array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'post_status'    => 'publish',
			'post__in'       => $picked,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $picked ),
			'no_found_rows'  => true,
		)
	);

	return $query->have_posts() ? $query : null;
};
if ( $area_term ) {
	$area_query = $rrc_related_rotate( array_merge( $related_args, array(
		'tax_query' => array( array( 'taxonomy' => 'area', 'field' => 'term_id', 'terms' => $area_term->term_id ) ),
	) ) );
	if ( $area_query && $area_query->have_posts() ) {
		$related_villas = $area_query;
		$related_tier   = 'area';
	}
}
if ( ! $related_villas && $region_term && $region_term->term_id !== ( $area_term ? $area_term->term_id : 0 ) ) {
	$region_query = $rrc_related_rotate( array_merge( $related_args, array(
		'tax_query' => array( array( 'taxonomy' => 'area', 'field' => 'term_id', 'terms' => $region_term->term_id, 'include_children' => true ) ),
	) ) );
	if ( $region_query && $region_query->have_posts() ) {
		$related_villas = $region_query;
		$related_tier   = 'region';
	}
}
if ( ! $related_villas ) {
	$fallback_query = $rrc_related_rotate( $related_args );
	if ( $fallback_query && $fallback_query->have_posts() ) {
		$related_villas = $fallback_query;
		$related_tier   = 'all';
	}
}

$lvc_related_image = static function( $post_id ) {
	return lvc_property_image( $post_id, 'medium_large' );
};

$schema_images = $all_gallery_urls;
if ( $hero_image ) {
	array_unshift( $schema_images, $hero_image );
}
$schema_images = array_values( array_unique( array_filter( $schema_images ) ) );

$geo_block = null;
if ( $area_term && function_exists( 'get_field' ) ) {
	$geo_lat = get_field( 'geo_lat', 'area_' . $area_term->term_id );
	$geo_lng = get_field( 'geo_lng', 'area_' . $area_term->term_id );
	if ( $geo_lat && $geo_lng ) {
		$geo_block = array( '@type' => 'GeoCoordinates', 'latitude' => (string) $geo_lat, 'longitude' => (string) $geo_lng );
	}
}

$contains_place = null;
if ( $max_guests || $bedrooms || $bathrooms ) {
	$contains_place = array( '@type' => 'Accommodation', 'additionalType' => 'EntirePlace' );
	if ( $max_guests ) {
		$contains_place['occupancy'] = array( '@type' => 'QuantitativeValue', 'value' => (int) $max_guests );
	}
	if ( $bedrooms ) {
		$contains_place['numberOfBedrooms'] = (int) $bedrooms;
	}
	if ( $bathrooms ) {
		$contains_place['numberOfBathroomsTotal'] = (float) $bathrooms;
	}
}

$schema = array(
	'@context'    => 'https://schema.org',
	'@type'       => 'VacationRental',
	'identifier'  => (string) $villa_id,
	'name'        => $h1_title,
	'url'         => $property_url,
	'image'       => $schema_images,
	'description' => wp_strip_all_tags( $property_description ? $property_description : $intro_quote ),
	'address'     => array( '@type' => 'PostalAddress', 'addressLocality' => $area_name ?: $region_name, 'addressRegion' => 'Quintana Roo', 'addressCountry' => 'MX' ),
);
if ( $bedrooms ) {
	$schema['numberOfRooms']    = (int) $bedrooms;
	$schema['numberOfBedrooms'] = (int) $bedrooms;
}
if ( $bathrooms ) {
	$schema['numberOfBathroomsTotal'] = (float) $bathrooms;
}
if ( $max_guests ) {
	$schema['occupancy'] = array( '@type' => 'QuantitativeValue', 'value' => (int) $max_guests );
}
if ( $region_name ) {
	$schema['containedInPlace'] = array( '@type' => 'Place', 'name' => $region_name );
}
if ( $geo_block ) {
	$schema['geo'] = $geo_block;
}
if ( $contains_place ) {
	$schema['containsPlace'] = $contains_place;
}

get_header();
?>

<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

<style>
	.rrc-villa,.rrc-villa *{box-sizing:border-box}.rrc-villa{background:var(--lvc-bg);color:var(--lvc-text);overflow:hidden;font-family:var(--lvc-font-body)}.rrc-villa a{text-decoration:none;color:inherit}.rrc-container{width:min(1420px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.rrc-narrow{width:min(1080px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.rrc-section{padding:clamp(4rem,7vw,7rem) 0}.rrc-section-sm{padding:clamp(3rem,5vw,5rem) 0}.rrc-eyebrow{display:block;color:var(--lvc-accent);font-size:.68rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin:0 0 .8rem}.rrc-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(1.9rem,3.3vw,3.1rem);line-height:1.08;font-weight:400;letter-spacing:-.04em}.rrc-title em{font-style:italic;color:var(--lvc-accent)}.rrc-copy{color:var(--lvc-soft);font-size:.97rem;line-height:1.85}.rrc-copy p+p{margin-top:1rem}.rrc-btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:.85rem 1.2rem;border:1px solid transparent;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;transition:.25s ease}.rrc-btn--primary{background:var(--lvc-accent);color:#04070a}.rrc-btn--primary:hover{background:var(--lvc-accent-hover);color:#04070a}.rrc-btn--outline{border-color:rgba(255,255,255,.18);background:rgba(255,255,255,.035);color:var(--lvc-text)}.rrc-btn--outline:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)}
	.rrc-hero{position:relative;min-height:82vh;display:flex;align-items:end;padding:clamp(7rem,9vw,9rem) 0 clamp(3rem,5vw,4.5rem);isolation:isolate}.rrc-hero__bg{position:absolute;inset:0;background-size:cover;background-position:center;z-index:-2}.rrc-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.92),rgba(10,12,15,.7) 42%,rgba(10,12,15,.22)),linear-gradient(0deg,rgba(10,12,15,.94),rgba(10,12,15,.1) 48%,rgba(10,12,15,.56))}.rrc-hero__grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:clamp(2rem,5vw,4.5rem);align-items:end}.rrc-kicker{display:flex;flex-wrap:wrap;gap:.55rem;align-items:center;color:var(--lvc-accent);font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;margin-bottom:1rem}.rrc-kicker span:not(:last-child):after{content:'/';margin-left:.55rem;color:rgba(255,255,255,.28)}.rrc-kicker a{color:inherit;text-decoration:none;border-bottom:1px solid transparent;transition:border-color .2s,opacity .2s}.rrc-kicker a:hover{border-bottom-color:currentColor;opacity:.85}.rrc-related__more{display:flex;flex-wrap:wrap;gap:.75rem;margin-top:2rem}.rrc-hero h1{margin:0;max-width:820px;font-family:var(--lvc-font-display);font-size:clamp(2.45rem,4.8vw,4.7rem);line-height:1.02;font-weight:400;letter-spacing:-.06em;text-shadow:0 20px 60px rgba(0,0,0,.45)}.rrc-hero__lede{max-width:680px;margin:1.15rem 0 0;color:rgba(243,243,241,.86);font-size:clamp(1rem,1.4vw,1.16rem);line-height:1.7}.rrc-hero__actions{display:flex;flex-wrap:wrap;gap:.8rem;margin-top:1.6rem}.rrc-photo-link{position:absolute;right:clamp(1rem,3vw,2rem);top:clamp(5.7rem,8vw,7rem);background:rgba(10,12,15,.6);border:1px solid rgba(255,255,255,.18);backdrop-filter:blur(10px);padding:.55rem .85rem;font-size:.68rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.rrc-snapshot{background:linear-gradient(180deg,rgba(16,21,28,.94),rgba(10,12,15,.9));border:1px solid rgba(255,255,255,.18);box-shadow:0 24px 60px rgba(0,0,0,.38);padding:1.35rem}.rrc-snapshot h2{margin:.25rem 0 1rem;font-family:var(--lvc-font-display);font-size:1.22rem;font-weight:500}.rrc-stats{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}.rrc-stat{padding:.75rem;border:1px solid var(--lvc-border);background:rgba(255,255,255,.035)}.rrc-stat strong{display:block;font-family:var(--lvc-font-display);font-size:1.45rem;line-height:1.05;font-weight:400}.rrc-stat span{display:block;margin-top:.25rem;color:var(--lvc-muted);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase}.rrc-snapshot p{margin:1rem 0 0;color:var(--lvc-soft);font-size:.82rem;line-height:1.6}
	.rrc-chipline{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border);padding:.9rem 0}.rrc-chipline__inner{display:flex;flex-wrap:wrap;gap:.55rem}.rrc-chip{border:1px solid var(--lvc-border);background:rgba(255,255,255,.03);padding:.5rem .7rem;color:var(--lvc-soft);font-size:.68rem;letter-spacing:.1em;text-transform:uppercase}.rrc-breadcrumb{border-bottom:1px solid var(--lvc-border);padding:.85rem 0}.rrc-breadcrumb ol{display:flex;gap:.5rem;flex-wrap:wrap;list-style:none;margin:0;padding:0}.rrc-breadcrumb li{color:var(--lvc-muted);font-size:.72rem}.rrc-breadcrumb li:not(:last-child):after{content:'›';margin-left:.5rem;color:rgba(255,255,255,.25)}
	.rrc-fit{background:var(--lvc-bg-alt);border-bottom:1px solid var(--lvc-border)}.rrc-fit__box{display:grid;grid-template-columns:minmax(0,.95fr) minmax(0,1.05fr);gap:clamp(1.7rem,4vw,3.5rem);align-items:center}.rrc-fit__box--solo{grid-template-columns:1fr}.rrc-fit__quote{margin:0;font-family:var(--lvc-font-body);font-size:clamp(1.02rem,1.45vw,1.22rem);line-height:1.7;font-weight:400;letter-spacing:normal;color:var(--lvc-soft)}.rrc-fit__quote:before{content:'\201C';color:var(--lvc-accent)}.rrc-fit__quote:after{content:'\201D';color:var(--lvc-accent)}.rrc-fit__panel{border-left:1px solid rgba(255,255,255,.18);padding-left:clamp(1.4rem,3vw,2.6rem)}.rrc-fit__target{color:var(--lvc-accent);font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;margin-bottom:.7rem}.rrc-fit__panel p{margin:0;color:var(--lvc-soft);line-height:1.75}.rrc-fit__actions{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.3rem}
	.rrc-overview{background:var(--lvc-bg)}.rrc-overview__grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:clamp(2rem,5vw,4.5rem);align-items:start}.rrc-detail-card{position:sticky;top:95px;background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.25rem}.rrc-detail-row{display:flex;justify-content:space-between;gap:1rem;padding:.8rem 0;border-bottom:1px solid var(--lvc-border)}.rrc-detail-row:first-child{padding-top:0}.rrc-detail-row:last-child{border-bottom:0;padding-bottom:0}.rrc-detail-row span{color:var(--lvc-muted);font-size:.64rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.rrc-detail-row strong{color:var(--lvc-text);text-align:right;font-size:.88rem}
	.rrc-spaces{background:var(--lvc-bg)}.rrc-space-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:2rem}.rrc-space{background:linear-gradient(180deg,var(--lvc-card),rgba(16,21,28,.72));border:1px solid var(--lvc-border);padding:1.5rem;min-height:215px}.rrc-space__num{color:var(--lvc-accent);font-size:.67rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.rrc-space h3{margin:.75rem 0 .75rem;font-family:var(--lvc-font-display);font-size:1.28rem;font-weight:500}.rrc-space p{margin:0;color:var(--lvc-soft);font-size:.9rem;line-height:1.72}.rrc-assist{background:linear-gradient(90deg,var(--lvc-accent-soft),rgba(16,21,28,.98));border-top:1px solid var(--lvc-accent-soft);border-bottom:1px solid var(--lvc-accent-soft);padding:2rem 0}.rrc-assist__inner{display:flex;justify-content:space-between;gap:2rem;align-items:center}.rrc-assist h2{margin:0 0 .35rem;font-family:var(--lvc-font-display);font-size:1.6rem;font-weight:500}.rrc-assist p{margin:0;color:var(--lvc-soft);max-width:680px;line-height:1.65}.rrc-assist__actions{display:flex;gap:.75rem;flex-wrap:wrap;flex-shrink:0}
	.rrc-photo-grid{background:var(--lvc-bg);padding-bottom:clamp(4rem,7vw,7rem)}.rrc-square-gallery{background:var(--lvc-bg);padding:clamp(4rem,7vw,7rem) 0}.rrc-square-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:2.5rem}.rrc-square-grid figure{margin:0;aspect-ratio:1;overflow:hidden;border-radius:2px}.rrc-square-grid img{width:100%;height:100%;object-fit:cover;display:block}.rrc-photo-slider{position:relative;margin-top:2rem}.rrc-photo-grid__grid{display:grid;grid-auto-flow:column;grid-auto-columns:min(86vw,980px);gap:1rem;overflow-x:auto;overscroll-behavior-x:contain;scroll-snap-type:x mandatory;padding-bottom:1rem;scrollbar-width:thin}.rrc-photo-grid figure{margin:0;height:clamp(420px,55vw,720px);overflow:hidden;border:1px solid var(--lvc-border);background:var(--lvc-card);scroll-snap-align:start}.rrc-photo-grid img{width:100%;height:100%;object-fit:cover;display:block}.rrc-gallery-arrow{position:absolute;top:50%;z-index:5;width:48px;height:48px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.18);border-radius:50%;background:rgba(10,12,15,.78);color:var(--lvc-text);font-size:1.7rem;line-height:1;cursor:pointer;transform:translateY(-50%);backdrop-filter:blur(12px);transition:background .2s ease,border-color .2s ease,color .2s ease}.rrc-gallery-arrow:hover{background:var(--lvc-accent);border-color:var(--lvc-accent);color:#04070a}.rrc-gallery-arrow--prev{left:1rem}.rrc-gallery-arrow--next{right:1rem}.rrc-gallery-hint{margin:.65rem 0 0;color:var(--lvc-muted);font-size:.76rem;letter-spacing:.06em;text-transform:uppercase}
	.rrc-included{background:var(--lvc-bg)}.rrc-included__grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:2rem}.rrc-included__box{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.5rem}.rrc-included__box--primary{border-color:var(--lvc-accent-soft);background:linear-gradient(180deg,var(--lvc-accent-soft),var(--lvc-card))}.rrc-included__box h3{margin:0 0 1rem;font-family:var(--lvc-font-display);font-size:1.18rem;font-weight:500}.rrc-included__box ul{list-style:none;margin:0;padding:0;display:grid;gap:.6rem}.rrc-included__box li{padding:.68rem .75rem;border:1px solid var(--lvc-border);background:rgba(255,255,255,.025);color:var(--lvc-soft);font-size:.84rem}
	.rrc-inquiry{background:radial-gradient(circle at 50% 0,var(--lvc-accent-soft),transparent 42%),var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.rrc-inquiry__box{width:min(760px,calc(100% - 2rem));margin:0 auto;background:linear-gradient(180deg,var(--lvc-card),rgba(10,12,15,.84));border:1px solid rgba(255,255,255,.18);box-shadow:0 24px 70px rgba(0,0,0,.35);padding:clamp(2rem,4vw,3rem)}.rrc-inquiry__header{text-align:center;margin-bottom:1.8rem}.rrc-inquiry__header h2{margin:0;font-family:var(--lvc-font-display);font-size:clamp(1.9rem,3.3vw,2.65rem);font-weight:400}.rrc-inquiry__header p{margin:.6rem 0 0;color:var(--lvc-muted)}.rrc-inquiry__wa-alt{text-align:center;color:var(--lvc-muted);font-size:.82rem;margin-top:1rem}.rrc-inquiry__wa-alt a{color:var(--lvc-accent)}
	.rrc-location{background:var(--lvc-accent);color:#04070a}.rrc-location__grid{display:grid;grid-template-columns:minmax(0,.75fr) minmax(0,1fr);gap:clamp(2rem,5vw,4rem);align-items:start}.rrc-location .rrc-eyebrow{color:rgba(4,7,10,.62)}.rrc-location h2{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.4rem);font-weight:400;letter-spacing:-.05em}.rrc-location p{margin:1rem 0 0;line-height:1.75;color:rgba(4,7,10,.78)}.rrc-location__facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1px;background:rgba(4,7,10,.18);border:1px solid rgba(4,7,10,.18)}.rrc-location__fact{background:rgba(255,255,255,.24);padding:1rem}.rrc-location__fact span{display:block;color:rgba(4,7,10,.58);font-size:.62rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.rrc-location__fact strong{display:block;margin-top:.35rem;color:#04070a}
	.rrc-related{background:var(--lvc-bg)}.rrc-related__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:2rem}.rrc-related-card{background:var(--lvc-card);border:1px solid var(--lvc-border);overflow:hidden;transition:.25s ease}.rrc-related-card:hover{transform:translateY(-3px);border-color:var(--lvc-accent)}.rrc-related-card__image{height:220px;background-size:cover;background-position:center;background-color:var(--lvc-bg-alt);display:flex;align-items:center;justify-content:center;color:var(--lvc-muted);font-size:.72rem;letter-spacing:.12em;text-transform:uppercase}.rrc-related-card__body{padding:1.15rem}.rrc-related-card h3{margin:0 0 .35rem;font-family:var(--lvc-font-display);font-size:1.18rem;font-weight:500}.rrc-related-card p{margin:0 0 .75rem;color:var(--lvc-muted);font-size:.82rem}.rrc-related-card__meta{display:flex;gap:.7rem;flex-wrap:wrap;border-top:1px solid var(--lvc-border);padding-top:.75rem;color:var(--lvc-soft);font-size:.74rem}
	.rrc-sticky{position:fixed;left:0;right:0;bottom:0;z-index:999;background:rgba(10,12,15,.94);border-top:1px solid rgba(255,255,255,.18);backdrop-filter:blur(14px);transform:translateY(105%);transition:.3s ease}.rrc-sticky.is-visible{transform:translateY(0)}.rrc-sticky__inner{width:min(1420px,calc(100% - 2rem));margin:0 auto;padding:.8rem 0;display:flex;justify-content:space-between;gap:1rem;align-items:center}.rrc-sticky__name{font-family:var(--lvc-font-display);font-weight:500}.rrc-sticky__meta{color:var(--lvc-muted);font-size:.74rem}.rrc-sticky__actions{display:flex;gap:.65rem}
	@media(max-width:1050px){.rrc-hero__grid,.rrc-fit__box,.rrc-overview__grid,.rrc-location__grid{grid-template-columns:1fr}.rrc-snapshot,.rrc-detail-card{position:static;max-width:620px}.rrc-related__grid{grid-template-columns:repeat(2,1fr)}.rrc-fit__panel{border-left:0;padding-left:0}.rrc-space-grid{grid-template-columns:1fr 1fr}.rrc-square-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:680px){.rrc-container,.rrc-narrow{width:calc(100% - 2rem)}.rrc-hero{min-height:auto;padding-top:7rem}.rrc-photo-link{top:1rem;right:1rem}.rrc-hero h1{font-size:clamp(2.25rem,11vw,3.2rem)}.rrc-stats,.rrc-space-grid,.rrc-included__grid,.rrc-location__facts,.rrc-related__grid{grid-template-columns:1fr}.rrc-sticky__inner{flex-direction:column;align-items:stretch}.rrc-hero__actions,.rrc-fit__actions,.rrc-assist__actions,.rrc-sticky__actions{display:grid;grid-template-columns:1fr}.rrc-sticky__info{display:none}.rrc-photo-grid__grid{grid-auto-columns:88vw}.rrc-photo-grid figure{height:clamp(320px,72vw,520px)}.rrc-gallery-arrow{width:42px;height:42px;font-size:1.35rem}.rrc-gallery-arrow--prev{left:.5rem}.rrc-gallery-arrow--next{right:.5rem}.rrc-square-grid{grid-template-columns:1fr}}
</style>

<main class="rrc-villa">
	<section class="rrc-hero" aria-label="<?php echo esc_attr( $h1_title ); ?>">
		<?php if ( $hero_image ) : ?><div class="rrc-hero__bg" style="background-image:url('<?php echo esc_url( $hero_image ); ?>');" role="img" aria-label="<?php echo esc_attr( $h1_title . ' in ' . $location_line ); ?>"></div><?php endif; ?>
		<?php if ( $photo_count ) : ?><a href="#gallery" class="rrc-photo-link"><?php echo esc_html( $photo_count ); ?> Photos</a><?php endif; ?>
		<div class="rrc-container rrc-hero__grid">
			<div class="rrc-hero__copy">
				<?php
				// The kicker reads as a breadcrumb, so make it behave like one.
				// The real breadcrumb sits below the hero, out of view on load.
				$rrc_kicker_area_url = $area_term ? lvc_area_lander_url_by_term( $area_term ) : '';
				?>
				<div class="rrc-kicker"><span><a href="<?php echo esc_url( lvc_archive_url() ); ?>">All Villas</a></span><?php if ( $location_line ) : ?><span><?php if ( $rrc_kicker_area_url ) : ?><a href="<?php echo esc_url( $rrc_kicker_area_url ); ?>"><?php echo esc_html( $location_line ); ?></a><?php else : ?><?php echo esc_html( $location_line ); ?><?php endif; ?></span><?php endif; ?><span><?php echo esc_html( $h1_title ); ?></span></div>
				<h1><?php echo esc_html( $h1_title ); ?></h1>
				<?php if ( $intro_quote ) : ?><p class="rrc-hero__lede"><?php echo esc_html( $intro_quote ); ?></p><?php endif; ?>
				<div class="rrc-hero__actions"><a href="#inquiry" class="rrc-btn rrc-btn--primary">Request Availability</a><?php if ( $whatsapp_url ) : ?><a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="rrc-btn rrc-btn--outline">WhatsApp a Specialist</a><?php endif; ?></div>
			</div>
			<aside class="rrc-snapshot" aria-label="Villa quick facts">
				<span class="rrc-eyebrow">Villa Snapshot</span><h2>Fast fit check</h2>
				<div class="rrc-stats">
					<?php if ( $bedrooms ) : ?><div class="rrc-stat"><strong><?php echo esc_html( $bedrooms ); ?></strong><span>Bedrooms</span></div><?php endif; ?>
					<?php if ( $max_guests ) : ?><div class="rrc-stat"><strong><?php echo esc_html( $max_guests ); ?></strong><span>Guests</span></div><?php endif; ?>
					<?php if ( $bathrooms ) : ?><div class="rrc-stat"><strong><?php echo esc_html( $bathrooms ); ?></strong><span>Bathrooms</span></div><?php endif; ?>
					<?php if ( $beach_positioning ) : ?><div class="rrc-stat"><strong><?php echo esc_html( $beach_positioning ); ?></strong><span>Setting</span></div><?php endif; ?>
				</div>
				<p>Send your dates and group profile. We will confirm availability, fit, and comparable villas before you commit.</p>
			</aside>
		</div>
	</section>

	<?php if ( ! empty( $hero_chips ) || $best_for_text ) : ?><section class="rrc-chipline" aria-label="Booking highlights"><div class="rrc-container rrc-chipline__inner"><?php foreach ( $hero_chips as $chip ) : ?><span class="rrc-chip"><?php echo esc_html( $chip ); ?></span><?php endforeach; ?><?php if ( $best_for_text ) : ?><span class="rrc-chip"><?php echo esc_html( $best_for_text ); ?></span><?php endif; ?></div></section><?php endif; ?>

	<nav class="rrc-breadcrumb" aria-label="Breadcrumb"><div class="rrc-container"><ol><li><a href="<?php echo esc_url( home_url() ); ?>">Home</a></li><li><a href="<?php echo esc_url( lvc_archive_url() ); ?>">Villas</a></li><?php if ( $area_name && $area_url ) : ?><li><a href="<?php echo esc_url( $area_url ); ?>"><?php echo esc_html( $area_name ); ?></a></li><?php endif; ?><li aria-current="page"><?php echo esc_html( $h1_title ); ?></li></ol></div></nav>

	<section class="rrc-fit rrc-section-sm" aria-label="Villa positioning"><div class="rrc-narrow rrc-fit__box<?php echo $has_real_positioning ? '' : ' rrc-fit__box--solo'; ?>"><?php if ( $has_real_positioning ) : ?><p class="rrc-fit__quote"><?php echo esc_html( $intro_quote ); ?></p><?php endif; ?><div class="rrc-fit__panel"><?php if ( $best_for_text ) : ?><div class="rrc-fit__target"><?php echo esc_html( $best_for_text ); ?></div><?php endif; ?><p>The fastest way to avoid a poor villa match is to tell us who is traveling, what matters most, and how flexible your dates are. We will help you compare this villa against the strongest alternatives in <?php echo esc_html( $region_name ?: 'the Riviera Maya' ); ?>.</p><div class="rrc-fit__actions"><a href="#inquiry" class="rrc-btn rrc-btn--primary">Request Availability</a><?php if ( $whatsapp_url ) : ?><a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="rrc-btn rrc-btn--outline">Ask a Question</a><?php endif; ?></div></div></div></section>

	<section class="rrc-overview rrc-section" id="description"><div class="rrc-container rrc-overview__grid"><div><span class="rrc-eyebrow">About This Villa</span><h2 class="rrc-title"><?php echo esc_html( $h1_title ); ?> <em>at a glance</em></h2><?php if ( $property_description ) : ?><div class="rrc-copy" style="margin-top:1.4rem;"><?php echo wp_kses_post( wpautop( $property_description ) ); ?></div><?php endif; ?></div><aside class="rrc-detail-card" aria-label="Villa details"><?php if ( $location_line ) : ?><div class="rrc-detail-row"><span>Location</span><strong><?php echo esc_html( $location_line ); ?></strong></div><?php endif; ?><?php if ( $community ) : ?><div class="rrc-detail-row"><span>Community</span><strong><?php echo esc_html( $community ); ?></strong></div><?php endif; ?><?php if ( $bedrooms ) : ?><div class="rrc-detail-row"><span>Bedrooms</span><strong><?php echo esc_html( $bedrooms ); ?></strong></div><?php endif; ?><?php if ( $bathrooms ) : ?><div class="rrc-detail-row"><span>Bathrooms</span><strong><?php echo esc_html( $bathrooms ); ?></strong></div><?php endif; ?><?php if ( $max_guests ) : ?><div class="rrc-detail-row"><span>Max Guests</span><strong><?php echo esc_html( $max_guests ); ?></strong></div><?php endif; ?><?php if ( $view_type ) : ?><div class="rrc-detail-row"><span>Setting</span><strong><?php echo esc_html( $view_type ); ?></strong></div><?php endif; ?></aside></div></section>

	<?php if ( ! empty( $gallery_square_urls ) ) : ?><section class="rrc-square-gallery" aria-label="Villa photo preview"><div class="rrc-container" style="text-align:center;"><span class="rrc-eyebrow">Photo Tour</span><h2 class="rrc-title">Explore the <em>spaces</em></h2><div class="rrc-square-grid"><?php foreach ( array_slice( $gallery_square_urls, 0, 6 ) as $index => $image_url ) : ?><figure><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $h1_title . ' square photo ' . ( $index + 1 ) ); ?>" loading="lazy" decoding="async"></figure><?php endforeach; ?></div></div></section><?php endif; ?>

	<?php $space_blocks = array_filter( array(
		$indoor_living ? array( 'label' => 'Indoor Living', 'text' => $indoor_living ) : null,
		$outdoor_living ? array( 'label' => 'Outdoor Living', 'text' => $outdoor_living ) : null,
		$bedroom_description ? array( 'label' => 'Bedrooms', 'text' => $bedroom_description ) : null,
		$view_type ? array( 'label' => 'Setting', 'text' => $view_type ) : null,
	) ); ?>
	<?php if ( $space_blocks ) : ?>
	<section class="rrc-spaces rrc-section" aria-label="Villa spaces"><div class="rrc-container"><span class="rrc-eyebrow">How the villa lives</span><h2 class="rrc-title">Designed around <em>group travel</em></h2><div class="rrc-space-grid"><?php foreach ( array_values( $space_blocks ) as $index => $block ) : ?><div class="rrc-space"><span class="rrc-space__num">0<?php echo (int) $index + 1; ?></span><h3><?php echo esc_html( $block['label'] ); ?></h3><p><?php echo wp_kses_post( $block['text'] ); ?></p></div><?php endforeach; ?></div></div></section>
	<?php endif; ?>

	<section class="rrc-assist" aria-label="Booking assistance"><div class="rrc-container" style="display:flex;justify-content:space-between;gap:2rem;align-items:center;flex-wrap:wrap;"><div><h2>Need help deciding if this villa fits?</h2><p>Share your dates, group profile, and priorities. We will confirm availability, explain the trade-offs, and suggest stronger alternatives if this is not the best match.</p></div><div class="rrc-assist__actions"><a href="#inquiry" class="rrc-btn rrc-btn--primary">Check Your Dates</a><?php if ( $whatsapp_url ) : ?><a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="rrc-btn rrc-btn--outline">Talk to Us</a><?php endif; ?></div></div></section>

	<?php if ( ! empty( $gallery_slider_urls ) ) : ?><section class="rrc-photo-grid" id="gallery" aria-label="Full villa photo gallery"><div class="rrc-container"><span class="rrc-eyebrow">Full Gallery</span><h2 class="rrc-title">A closer look at <em><?php echo esc_html( $h1_title ); ?></em></h2><div class="rrc-photo-slider"><button class="rrc-gallery-arrow rrc-gallery-arrow--prev" type="button" aria-label="Previous gallery photo" data-rrc-gallery-prev>&lsaquo;</button><div class="rrc-photo-grid__grid" data-rrc-gallery-track><?php foreach ( $gallery_slider_urls as $index => $image_url ) : ?><figure><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $h1_title . ' photo ' . ( $index + 1 ) ); ?>" loading="lazy" decoding="async"></figure><?php endforeach; ?></div><button class="rrc-gallery-arrow rrc-gallery-arrow--next" type="button" aria-label="Next gallery photo" data-rrc-gallery-next>&rsaquo;</button></div><p class="rrc-gallery-hint">Use the arrows or scroll sideways to view more photos.</p></div></section><?php endif; ?>

	<section class="rrc-included rrc-section" aria-label="Direct booking benefits"><div class="rrc-narrow"><span class="rrc-eyebrow">Direct Booking Benefits</span><h2 class="rrc-title">What&rsquo;s included in the <em>planning process</em></h2><div class="rrc-included__grid"><div class="rrc-included__box rrc-included__box--primary"><h3>Included with direct booking</h3><ul><li>Pre-arrival planning and villa guidance</li><li>Local concierge support before and during your stay</li><li>Direct booking assistance with clear next steps</li></ul></div><div class="rrc-included__box"><h3>Available on request</h3><ul><li>Private chef and in-villa dining</li><li>Airport transfers</li><li>Tours, excursions, and boat days</li><li>Grocery pre-stocking, spa, and wellness services</li></ul></div></div></div></section>

	<section class="rrc-inquiry rrc-section" id="inquiry" aria-label="Inquiry form"><div class="rrc-inquiry__box"><header class="rrc-inquiry__header"><span class="rrc-eyebrow">Direct Booking &middot; No Platform Fees</span><h2>Plan Your Stay</h2><p>Send your dates and group details. Our team will respond with availability, fit, and next steps.</p></header><?php get_template_part( 'template-parts/inquiry-form', null, array( 'property_name' => get_the_title(), 'submit_label' => 'Request Availability' ) ); ?><?php if ( $whatsapp_url ) : ?><p class="rrc-inquiry__wa-alt">Prefer to chat? <a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer">Message us on WhatsApp &rarr;</a></p><?php endif; ?></div></section>

	<?php if ( $location_line ) : ?><section class="rrc-location rrc-section-sm" aria-label="Location and area context"><div class="rrc-container rrc-location__grid"><div><span class="rrc-eyebrow">Location</span><h2><?php echo esc_html( $area_name ?: $region_name ); ?></h2><p><?php echo esc_html( $location_line ); ?> places your group close to the beaches, dining, and outdoor experiences that make <?php echo esc_html( $region_name ?: 'this destination' ); ?> work so well for private villa stays.</p></div><div><div class="rrc-location__facts"><?php if ( $bedrooms ) : ?><div class="rrc-location__fact"><span>Bedrooms</span><strong><?php echo esc_html( $bedrooms ); ?></strong></div><?php endif; ?><?php if ( $max_guests ) : ?><div class="rrc-location__fact"><span>Max Guests</span><strong><?php echo esc_html( $max_guests ); ?></strong></div><?php endif; ?></div><?php if ( $view_type ) : ?><p><?php echo esc_html( $view_type ); ?></p><?php endif; ?></div></div></section><?php endif; ?>

	<?php if ( $related_villas && $related_villas->have_posts() ) : ?><section class="rrc-related rrc-section" aria-label="Related villas"><div class="rrc-container"><span class="rrc-eyebrow">Explore More</span><h2 class="rrc-title"><?php if ( 'area' === $related_tier && $area_name ) : ?>More villas in <em><?php echo esc_html( $area_name ); ?></em><?php elseif ( 'region' === $related_tier && $region_name ) : ?>Similar villas in <em><?php echo esc_html( $region_name ); ?></em><?php else : ?>More <em>villas</em><?php endif; ?></h2><div class="rrc-related__grid"><?php while ( $related_villas->have_posts() ) : $related_villas->the_post(); $related_id = get_the_ID(); $related_name = get_field( 'h1_property_title', $related_id ) ? get_field( 'h1_property_title', $related_id ) : get_the_title(); $related_bedrooms = get_field( 'bedrooms', $related_id ); $related_guests = get_field( 'max_guests', $related_id ); $related_image = $lvc_related_image( $related_id ); $related_area_obj = lvc_property_area_term( $related_id ); $related_area = $related_area_obj ? $related_area_obj->name : ''; ?><a href="<?php the_permalink(); ?>" class="rrc-related-card"><div class="rrc-related-card__image" <?php if ( $related_image ) : ?>style="background-image:url('<?php echo esc_url( $related_image ); ?>');"<?php endif; ?> role="img" aria-label="<?php echo esc_attr( $related_name ); ?>"><?php if ( ! $related_image ) : ?>View Villa<?php endif; ?></div><div class="rrc-related-card__body"><h3><?php echo esc_html( $related_name ); ?></h3><?php if ( $related_area ) : ?><p><?php echo esc_html( $related_area ); ?></p><?php endif; ?><div class="rrc-related-card__meta"><?php if ( $related_bedrooms ) : ?><span><?php echo esc_html( $related_bedrooms ); ?> Bedrooms</span><?php endif; ?><?php if ( $related_guests ) : ?><span>Sleeps <?php echo esc_html( $related_guests ); ?></span><?php endif; ?></div></div></a><?php endwhile; wp_reset_postdata(); ?></div>
	<?php
	/**
	 * Contextual browse links: from a villa a visitor can step up to its area
	 * or out to the full collection. Previously the related grid dead-ended.
	 */
	$rrc_more_links = array();
	if ( $area_term ) {
		$rrc_area_url = lvc_area_lander_url_by_term( $area_term );
		if ( $rrc_area_url ) {
			$rrc_more_links[] = array( 'label' => 'All villas in ' . $area_term->name, 'url' => $rrc_area_url );
		}
	}
	$rrc_more_links[] = array( 'label' => 'Browse all villas', 'url' => lvc_archive_url() );
	?>
	<div class="rrc-related__more"><?php foreach ( $rrc_more_links as $rrc_ml ) : ?><a class="rrc-btn rrc-btn--outline" href="<?php echo esc_url( $rrc_ml['url'] ); ?>"><?php echo esc_html( $rrc_ml['label'] ); ?> &rarr;</a><?php endforeach; ?></div>
	</div></section><?php endif; ?>

	<?php
	/*
	 * Ownership cross-link.
	 *
	 * A guest reading a villa page in this building is the warmest buyer lead that
	 * exists for it — they have already chosen the destination and the address.
	 * This link existed years ago ("buy a piece of paradise") and was dropped in
	 * the rebuild; the two sites have had no connection since.
	 *
	 * Deliberately quiet and placed last. It must never compete with the booking
	 * inquiry, which is what this page is for.
	 *
	 * Filterable so it can be repointed or removed without touching the template.
	 */
	$rrc_own = apply_filters( 'lvc_ownership_url', 'https://www.cozumel-real-estate.com/development/residencias-reef/' );
	?>
	<?php if ( $rrc_own ) : ?>
		<section class="rrc-own rrc-section-sm" aria-label="Ownership at this property">
			<div class="rrc-narrow">
				<span class="rrc-eyebrow">Ownership</span>
				<h2 class="rrc-title">Thinking beyond a stay?</h2>
				<p>
					Residences here are privately owned, and most change hands quietly between
					owners rather than being listed publicly. If owning at Residencias Reef is
					something you would consider, we handle that side too.
				</p>
				<p>
					<a href="<?php echo esc_url( $rrc_own ); ?>" class="rrc-btn rrc-btn--outline" rel="noopener">
						See Residencias Reef for sale &rarr;
					</a>
				</p>
			</div>
		</section>
	<?php endif; ?>

	<div class="rrc-sticky" id="rrcSticky" aria-label="Quick villa actions"><div class="rrc-sticky__inner"><div class="rrc-sticky__info"><div class="rrc-sticky__name"><?php echo esc_html( $h1_title ); ?></div><div class="rrc-sticky__meta"><?php echo esc_html( trim( implode( ' · ', array_filter( array( $bedrooms ? $bedrooms . ' bedrooms' : '', $max_guests ? 'sleeps ' . $max_guests : '', $location_line ) ) ) ) ); ?></div></div><div class="rrc-sticky__actions"><?php if ( $whatsapp_url ) : ?><a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="rrc-btn rrc-btn--outline">WhatsApp</a><?php endif; ?><a href="#inquiry" class="rrc-btn rrc-btn--primary">Inquire Now</a></div></div></div>
</main>

<script>
(function(){var sticky=document.getElementById('rrcSticky');var inquiry=document.getElementById('inquiry');var toggleSticky=function(){if(!sticky){return;}var y=window.scrollY||window.pageYOffset||0;sticky.classList.toggle('is-visible',y>700&&(!inquiry||inquiry.getBoundingClientRect().top>250));};window.addEventListener('scroll',toggleSticky,{passive:true});toggleSticky();var checkinInput=document.querySelector('input[name="checkin"]');var checkoutInput=document.querySelector('input[name="checkout"]');if(checkinInput&&checkoutInput){checkinInput.addEventListener('change',function(){if(this.value){checkoutInput.min=this.value;if(checkoutInput.value&&checkoutInput.value<=this.value){checkoutInput.value='';}}});}var galleryTrack=document.querySelector('[data-rrc-gallery-track]');var galleryPrev=document.querySelector('[data-rrc-gallery-prev]');var galleryNext=document.querySelector('[data-rrc-gallery-next]');if(galleryTrack&&galleryPrev&&galleryNext){var scrollGallery=function(direction){var distance=Math.max(galleryTrack.clientWidth*.72,420);galleryTrack.scrollBy({left:direction*distance,behavior:'smooth'});};galleryPrev.addEventListener('click',function(){scrollGallery(-1);});galleryNext.addEventListener('click',function(){scrollGallery(1);});}})();
</script>

<?php get_footer(); ?>
