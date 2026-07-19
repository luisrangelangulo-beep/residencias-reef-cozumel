<?php
/**
 * Single property — conversion-focused villa / condo detail page.
 *
 * Adds a stronger decision path: hero, gallery, at-a-glance facts, fit cards,
 * area context/internal links, features, service, FAQ, sticky inquiry, final CTA,
 * and smarter related fallbacks.
 *
 * @package ResidenciasReefCozumel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'lvc_single_term_links' ) ) {
	function lvc_single_term_links( $post_id, $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}
		$links = array();
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$links[] = array( 'name' => $term->name, 'url' => $link, 'slug' => $term->slug, 'term' => $term );
		}
		return $links;
	}
}

if ( ! function_exists( 'lvc_single_related_fallback' ) ) {
	function lvc_single_related_fallback( $post_id, $limit = 3 ) {
		$cpt     = lvc_config( 'cpt', 'villas' );
		$results = array();
		$seen    = array( (int) $post_id );

		$queries = array();
		$area = function_exists( 'lvc_property_area_term' ) ? lvc_property_area_term( $post_id ) : null;
		if ( $area && $area->parent ) {
			$queries[] = array( 'taxonomy' => 'area', 'field' => 'term_id', 'terms' => array( (int) $area->parent ) );
		}
		$collections = wp_get_post_terms( $post_id, 'collection', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $collections ) && $collections ) {
			$queries[] = array( 'taxonomy' => 'collection', 'field' => 'term_id', 'terms' => $collections );
		}
		$bedrooms = wp_get_post_terms( $post_id, 'bedrooms', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $bedrooms ) && $bedrooms ) {
			$queries[] = array( 'taxonomy' => 'bedrooms', 'field' => 'term_id', 'terms' => $bedrooms );
		}

		foreach ( $queries as $tax_query ) {
			if ( count( $results ) >= $limit ) {
				break;
			}
			$q = new WP_Query( array(
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'post__not_in'   => $seen,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'tax_query'      => array( $tax_query ),
			) );
			foreach ( $q->posts as $rid ) {
				$rid = (int) $rid;
				if ( in_array( $rid, $seen, true ) ) {
					continue;
				}
				$results[] = $rid;
				$seen[]    = $rid;
				if ( count( $results ) >= $limit ) {
					break;
				}
			}
			wp_reset_postdata();
		}

		if ( count( $results ) < $limit ) {
			$q = new WP_Query( array(
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => $limit - count( $results ),
				'post__not_in'   => $seen,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			) );
			foreach ( $q->posts as $rid ) {
				$results[] = (int) $rid;
			}
			wp_reset_postdata();
		}
		return array_slice( $results, 0, $limit );
	}
}

while ( have_posts() ) :
	the_post();
	$lvc_id       = get_the_ID();
	$lvc_img      = lvc_property_image( $lvc_id, 'full' );
	$lvc_h1       = lvc_field( 'h1_title', $lvc_id, get_the_title() );
	$lvc_name     = lvc_field( 'card_title', $lvc_id, get_the_title() );
	$lvc_beds     = lvc_field( 'bed_count', $lvc_id );
	$lvc_baths    = lvc_field( 'bath_count', $lvc_id );
	$lvc_guests   = lvc_field( 'guests_max', $lvc_id );
	$lvc_over     = lvc_field( 'property_descr', $lvc_id, get_the_content() );
	$lvc_indoor   = lvc_field( 'indoor_living', $lvc_id );
	$lvc_outdoor  = lvc_field( 'outdoor_living', $lvc_id );
	$lvc_bedrm    = lvc_field( 'bedroom_desc', $lvc_id );
	$lvc_cater    = lvc_field( 'catering_detail', $lvc_id );
	$lvc_features = lvc_field( 'villa_features_html', $lvc_id );
	$lvc_tags     = lvc_field( 'tags', $lvc_id );
	$lvc_tier     = lvc_field( 'from_rate_tier', $lvc_id );
	$lvc_area_obj = lvc_property_area_term( $lvc_id );
	$lvc_area_n   = $lvc_area_obj ? $lvc_area_obj->name : '';
	$lvc_area_url = $lvc_area_obj ? lvc_area_lander_url_by_term( $lvc_area_obj ) : '';

	$lvc_area_links       = lvc_single_term_links( $lvc_id, 'area' );
	$lvc_collection_links = lvc_single_term_links( $lvc_id, 'collection' );
	$lvc_bedroom_links    = lvc_single_term_links( $lvc_id, 'bedrooms' );

	$lvc_gallery_raw = (string) lvc_field( 'gallery_squares', $lvc_id );
	$lvc_gallery     = array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $lvc_gallery_raw ) ), function( $url ) {
		return (bool) preg_match( '#^https?://#i', $url );
	} ) );

	$lvc_feature_chips = array();
	foreach ( $lvc_collection_links as $term_link ) {
		$lvc_feature_chips[] = $term_link['name'];
	}
	if ( $lvc_tags ) {
		foreach ( array_filter( array_map( 'trim', explode( ',', $lvc_tags ) ) ) as $tag ) {
			$lvc_feature_chips[] = $tag;
		}
	}
	$lvc_feature_chips = array_values( array_unique( array_filter( $lvc_feature_chips ) ) );

	$lvc_amenity_terms = array();
	if ( taxonomy_exists( 'amenity' ) ) {
		$lvc_amenity_terms = get_the_terms( $lvc_id, 'amenity' );
		if ( is_wp_error( $lvc_amenity_terms ) ) {
			$lvc_amenity_terms = array();
		}
	}

	$lvc_hero_facts = array_filter( array(
		$lvc_beds ? $lvc_beds . ' Bedrooms' : '',
		$lvc_baths ? $lvc_baths . ' Baths' : '',
		$lvc_guests ? 'Sleeps ' . $lvc_guests : '',
		$lvc_area_n,
	) );

	$lvc_glance = array_filter( array(
		array( 'label' => 'Bedrooms', 'value' => $lvc_beds ),
		array( 'label' => 'Bathrooms', 'value' => $lvc_baths ),
		array( 'label' => 'Max Guests', 'value' => $lvc_guests ),
		array( 'label' => 'Area', 'value' => $lvc_area_n ),
		array( 'label' => 'Rate Tier', 'value' => $lvc_tier ? strtoupper( str_replace( '-', ' ', $lvc_tier ) ) : '' ),
	), function ( $item ) {
		return ! empty( $item['value'] );
	} );

	$lvc_area_context = '';
	if ( $lvc_area_n ) {
		$lvc_area_context = $lvc_area_n . ' works best when the location fits the way your group wants to travel. We help compare beach access, restaurants, privacy, transfers, service level, and nearby alternatives before you commit.';
	}

	if ( function_exists( 'lvc_schema_property' ) ) {
		lvc_schema_property( $lvc_id );
	}

	// FAQ — flat faq_q1..faq_a4. If none exist, show safe generic conversion FAQs and emit schema for those only.
	$lvc_faq = array();
	for ( $i = 1; $i <= 4; $i++ ) {
		$q = lvc_field( 'faq_q' . $i, $lvc_id );
		$a = lvc_field( 'faq_a' . $i, $lvc_id );
		if ( $q && $a ) {
			$lvc_faq[] = array( $q, $a );
		}
	}
	$lvc_generated_faq = false;
	if ( ! $lvc_faq ) {
		$lvc_generated_faq = true;
		$lvc_faq = array(
			array( 'How do I request availability for ' . $lvc_name . '?', 'Send your dates, group size, bedroom needs, and service priorities. We will confirm availability, current rate details, inclusions, and realistic alternatives if this property is not the best fit.' ),
			array( 'Can chef service or airport transfers be arranged?', 'Concierge services vary by property and destination. We can help coordinate chef service, groceries, transfers, tours, spa, diving, or other requests when available.' ),
			array( 'Is this property better than a Cozumel condo?', 'A Cozumel condo can be a good fit for simpler beachfront stays. A private villa is usually better for larger groups, more privacy, private pools, chef service, celebrations, and higher-service trips.' ),
			array( 'What should I include in my inquiry?', 'Include your dates, number of adults and children, bedroom count, preferred area, approximate budget, and any must-haves such as beachfront access, chef service, or walking distance to restaurants.' ),
		);
	}
	if ( $lvc_generated_faq && function_exists( 'lvc_jsonld' ) ) {
		$lvc_faq_qas = array();
		foreach ( $lvc_faq as $qa ) {
			$lvc_faq_qas[] = array( '@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $qa[1] ) ) );
		}
		lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $lvc_faq_qas ) );
	}
	?>

	<style>
		.lvc-single-modern{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}
		.lvc-single-modern *{box-sizing:border-box}.lvc-single-wrap{width:min(100%,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-single-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-single-section{padding:clamp(4rem,7vw,7rem) 0}.lvc-single-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lvc-single-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lvc-single-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.65rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lvc-single-title em{font-style:italic;color:var(--lvc-accent)}.lvc-single-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.2vw,1.08rem);font-weight:300;line-height:1.82}.lvc-single-copy p{margin:0 0 1rem}.lvc-single-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center}.lvc-single-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lvc-single-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
		.lvc-single-hero{position:relative;min-height:min(760px,86vh);display:flex;align-items:flex-end;isolation:isolate;background:var(--lvc-bg-deep);overflow:hidden}.lvc-single-hero__img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.lvc-single-hero:before{content:'';position:absolute;inset:0;z-index:1;background:linear-gradient(90deg,rgba(10,12,15,.94),rgba(10,12,15,.7) 52%,rgba(10,12,15,.35)),linear-gradient(0deg,rgba(10,12,15,.92),rgba(10,12,15,.18) 58%,rgba(10,12,15,.55))}.lvc-single-hero__inner{position:relative;z-index:2;padding:clamp(7rem,10vw,10rem) 0 clamp(3rem,6vw,5rem)}.lvc-single-hero h1{max-width:980px;margin:0;font-family:var(--lvc-font-display);font-size:clamp(2.45rem,5vw,5rem);font-weight:200;line-height:1.08;color:var(--lvc-text)}.lvc-single-hero__facts{list-style:none;margin:1.25rem 0 0;padding:0;display:flex;flex-wrap:wrap;gap:.65rem}.lvc-single-hero__facts li{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.035);padding:.55rem .75rem;color:var(--lvc-soft);font-size:.75rem;text-transform:uppercase;letter-spacing:.08em}.lvc-single-hero__actions{margin-top:1.65rem}
		.lvc-single-gallery{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;padding:8px;background:var(--lvc-bg)}.lvc-single-gallery figure{margin:0;aspect-ratio:1;overflow:hidden;background:var(--lvc-card)}.lvc-single-gallery img{width:100%;height:100%;object-fit:cover;transition:transform .45s ease}.lvc-single-gallery figure:hover img{transform:scale(1.04)}
		.lvc-single-glance{margin-top:-3rem;position:relative;z-index:3}.lvc-single-glance__grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0;background:linear-gradient(180deg,rgba(16,21,28,.98),rgba(10,12,15,.94));border:1px solid rgba(255,255,255,.12);box-shadow:0 24px 70px rgba(0,0,0,.28)}.lvc-single-glance__item{padding:1.15rem 1rem;border-right:1px solid var(--lvc-border)}.lvc-single-glance__item:last-child{border-right:0}.lvc-single-glance__item span{display:block;color:var(--lvc-muted);font-size:.66rem;letter-spacing:.14em;text-transform:uppercase}.lvc-single-glance__item strong{display:block;margin-top:.35rem;color:var(--lvc-text);font-family:var(--lvc-font-display);font-size:1.35rem;font-weight:300;line-height:1.1}
		.lvc-single-body{display:grid;grid-template-columns:minmax(0,1fr) minmax(330px,390px);gap:clamp(2rem,5vw,4rem);align-items:start}.lvc-single-main{min-width:0}.lvc-single-sidebar{position:sticky;top:92px}.lvc-single-inquiry{background:linear-gradient(180deg,rgba(16,21,28,.98),rgba(10,12,15,.94));border:1px solid var(--lvc-border);padding:1.55rem;box-shadow:0 24px 70px rgba(0,0,0,.22)}.lvc-single-inquiry h2{font-size:1.55rem;margin:0 0 .5rem}.lvc-single-inquiry__meta{margin:.85rem 0 1.15rem;padding:0;list-style:none;display:grid;gap:.35rem;color:var(--lvc-muted);font-size:.85rem}.lvc-single-inquiry__micro{color:var(--lvc-muted);font-size:.8rem;line-height:1.6;margin:.8rem 0 0}.lvc-single-content-block{margin-top:clamp(2.4rem,4vw,3.6rem)}.lvc-single-content-block:first-child{margin-top:0}.lvc-single-content-block h2{font-size:clamp(1.65rem,3vw,2.45rem);margin-bottom:1rem}.lvc-single-prose{max-width:900px}.lvc-single-prose p{margin:0 0 1rem;color:var(--lvc-soft);line-height:1.86}.lvc-single-prose ul{margin:1rem 0;padding-left:1.2rem;color:var(--lvc-soft)}
		.lvc-single-fit-grid,.lvc-single-service-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.lvc-single-fit-card,.lvc-single-service-card{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.25rem;min-height:170px}.lvc-single-fit-card span,.lvc-single-service-card span{display:block;color:var(--lvc-accent);font-size:.68rem;letter-spacing:.16em;text-transform:uppercase;margin-bottom:.6rem}.lvc-single-fit-card h3,.lvc-single-service-card h3{margin:0 0 .55rem;font-family:var(--lvc-font-display);font-size:1.18rem;font-weight:300;color:var(--lvc-text)}.lvc-single-fit-card p,.lvc-single-service-card p{margin:0;color:var(--lvc-soft);font-size:.9rem;line-height:1.65}
		.lvc-single-chip-list{list-style:none;margin:1rem 0 0;padding:0;display:flex;flex-wrap:wrap;gap:.55rem}.lvc-single-chip-list a,.lvc-single-chip-list span{display:inline-flex;border:1px solid var(--lvc-border);background:rgba(255,255,255,.025);color:var(--lvc-soft)!important;padding:.55rem .8rem;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}.lvc-single-chip-list a:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important}.lvc-single-area-box{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:clamp(1.5rem,3vw,2.2rem)}.lvc-single-area-box .lvc-single-btns{margin-top:1.1rem}.lvc-single-feature-list{list-style:none;margin:1.2rem 0 0;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:.45rem 1.6rem}.lvc-single-feature-list li{position:relative;padding-left:1.5rem;color:var(--lvc-soft);line-height:1.55}.lvc-single-feature-list li:before{content:'';position:absolute;left:0;top:.6em;width:.5rem;height:.28rem;border-left:2px solid var(--lvc-accent);border-bottom:2px solid var(--lvc-accent);transform:rotate(-45deg)}
		.lvc-single-faq{display:grid;gap:.75rem}.lvc-single-faq details{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1rem 1.15rem}.lvc-single-faq summary{cursor:pointer;color:var(--lvc-text);font-family:var(--lvc-font-display);font-weight:300}.lvc-single-faq p{margin:.75rem 0 0;color:var(--lvc-soft);line-height:1.7}.lvc-single-final{background:var(--lvc-bg-deep);text-align:center;border-top:1px solid rgba(255,255,255,.12)}.lvc-single-final .lvc-single-copy{max-width:700px;margin:1rem auto 1.5rem}.lvc-single-final .lvc-single-btns{justify-content:center}.lvc-single-related{border-top:1px solid var(--lvc-border)}.lvc-single-related .lvc-grid{margin-top:2rem}.lvc-single-mobilebar{display:none}
		@media(max-width:1100px){.lvc-single-body{grid-template-columns:1fr}.lvc-single-sidebar{position:static}.lvc-single-glance__grid{grid-template-columns:repeat(3,minmax(0,1fr))}.lvc-single-gallery{grid-template-columns:repeat(3,minmax(0,1fr))}.lvc-single-fit-grid,.lvc-single-service-grid{grid-template-columns:1fr}}
		@media(max-width:720px){.lvc-single-wrap,.lvc-single-narrow{width:calc(100% - 2rem)}.lvc-single-hero{min-height:auto}.lvc-single-hero__inner{padding:6rem 0 4rem}.lvc-single-hero h1{font-size:clamp(2.25rem,11vw,3.35rem)}.lvc-single-glance{margin-top:0}.lvc-single-glance__grid{grid-template-columns:1fr}.lvc-single-glance__item{border-right:0;border-bottom:1px solid var(--lvc-border)}.lvc-single-gallery{grid-template-columns:repeat(2,minmax(0,1fr))}.lvc-single-btns{display:grid;grid-template-columns:1fr}.lvc-single-mobilebar{position:fixed;bottom:0;left:0;right:0;z-index:40;display:flex;align-items:center;justify-content:space-between;gap:1rem;background:var(--lvc-card);border-top:1px solid var(--lvc-border);padding:.8rem 1rem}.lvc-single-mobilebar span{color:var(--lvc-text);font-size:.86rem;line-height:1.2}.lvc-single-modern{padding-bottom:74px}}
	</style>

	<main class="lvc-single-modern">
		<section class="lvc-single-hero" aria-label="<?php echo esc_attr( $lvc_h1 ); ?>">
			<?php if ( $lvc_img ) : ?><img class="lvc-single-hero__img" src="<?php echo esc_url( $lvc_img ); ?>" alt="<?php echo esc_attr( $lvc_name ); ?>"><?php endif; ?>
			<div class="lvc-single-wrap lvc-single-hero__inner">
				<?php if ( $lvc_area_n ) : ?><span class="lvc-single-kicker"><?php echo esc_html( $lvc_area_n ); ?></span><?php endif; ?>
				<h1><?php echo esc_html( $lvc_h1 ); ?></h1>
				<?php if ( $lvc_hero_facts ) : ?><ul class="lvc-single-hero__facts"><?php foreach ( $lvc_hero_facts as $fact ) : ?><li><?php echo esc_html( $fact ); ?></li><?php endforeach; ?></ul><?php endif; ?>
				<div class="lvc-single-btns lvc-single-hero__actions"><a class="lvc-single-btn" href="#inquiry">Request Availability</a><?php if ( $lvc_area_url ) : ?><a class="lvc-single-btn lvc-single-btn--ghost" href="<?php echo esc_url( $lvc_area_url ); ?>">Explore <?php echo esc_html( $lvc_area_n ); ?></a><?php endif; ?></div>
			</div>
		</section>

		<?php if ( $lvc_gallery ) : ?>
			<section class="lvc-single-gallery" aria-label="<?php echo esc_attr( $lvc_name ); ?> gallery">
				<?php foreach ( array_slice( $lvc_gallery, 0, 12 ) as $g ) : ?><figure><img src="<?php echo esc_url( $g ); ?>" alt="<?php echo esc_attr( $lvc_name ); ?>" loading="lazy" decoding="async"></figure><?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ( $lvc_glance ) : ?>
			<section class="lvc-single-glance"><div class="lvc-single-wrap"><div class="lvc-single-glance__grid"><?php foreach ( $lvc_glance as $item ) : ?><div class="lvc-single-glance__item"><span><?php echo esc_html( $item['label'] ); ?></span><strong><?php echo esc_html( $item['value'] ); ?></strong></div><?php endforeach; ?></div></div></section>
		<?php endif; ?>

		<section class="lvc-single-section">
			<div class="lvc-single-wrap lvc-single-body">
				<article class="lvc-single-main">
					<?php if ( $lvc_over ) : ?><section class="lvc-single-content-block"><span class="lvc-single-kicker">Villa Overview</span><div class="lvc-single-prose lvc-single-copy"><?php echo wp_kses_post( wpautop( $lvc_over ) ); ?></div></section><?php endif; ?>

					<section class="lvc-single-content-block">
						<span class="lvc-single-kicker">Why This Property Works</span>
						<h2 class="lvc-single-title">A quick fit check before you inquire</h2>
						<div class="lvc-single-fit-grid">
							<article class="lvc-single-fit-card"><span>Best For</span><h3><?php echo esc_html( $lvc_guests ? 'Groups up to ' . $lvc_guests : 'Private stays' ); ?></h3><p>Use this property when the bedroom count, area, privacy level, and service expectations match the way your group wants to travel.</p></article>
							<article class="lvc-single-fit-card"><span>What to Compare</span><h3>Location and layout</h3><p>Before booking, compare beach access, drive times, bedroom layout, pool/privacy, staff, chef options, and nearby alternatives.</p></article>
							<article class="lvc-single-fit-card"><span>Good to Know</span><h3>Confirm inclusions first</h3><p>Staff, chef service, housekeeping, taxes, fees, and minimum stays vary by property and season. We confirm details before you commit.</p></article>
						</div>
					</section>

					<?php
					$lvc_living = array(
						'Indoor Living'  => $lvc_indoor,
						'Outdoor Living' => $lvc_outdoor,
						'Bedroom Layout' => $lvc_bedrm,
					);
					foreach ( $lvc_living as $lvc_heading => $lvc_text ) :
						if ( ! $lvc_text ) {
							continue;
						} ?>
						<section class="lvc-single-content-block"><h2 class="lvc-single-title"><?php echo esc_html( $lvc_heading ); ?></h2><div class="lvc-single-prose"><?php echo wp_kses_post( wpautop( $lvc_text ) ); ?></div></section>
					<?php endforeach; ?>

					<?php if ( $lvc_feature_chips || $lvc_features || $lvc_amenity_terms ) : ?>
						<section class="lvc-single-content-block">
							<span class="lvc-single-kicker">Features &amp; Stay Style</span>
							<h2 class="lvc-single-title">What guests usually compare</h2>
							<?php if ( $lvc_features ) : ?><div class="lvc-single-prose lvc-features"><?php echo wp_kses_post( $lvc_features ); ?></div><?php endif; ?>
							<?php if ( $lvc_amenity_terms ) : ?><ul class="lvc-single-feature-list"><?php foreach ( $lvc_amenity_terms as $a ) : ?><li><?php echo esc_html( $a->name ); ?></li><?php endforeach; ?></ul><?php endif; ?>
							<?php if ( $lvc_feature_chips ) : ?><ul class="lvc-single-chip-list"><?php foreach ( array_slice( $lvc_feature_chips, 0, 14 ) as $chip ) : ?><li><span><?php echo esc_html( $chip ); ?></span></li><?php endforeach; ?></ul><?php endif; ?>
						</section>
					<?php endif; ?>

					<?php if ( $lvc_area_n ) : ?>
						<section class="lvc-single-content-block lvc-single-area-box">
							<span class="lvc-single-kicker">Area Context</span>
							<h2 class="lvc-single-title">About staying in <em><?php echo esc_html( $lvc_area_n ); ?></em></h2>
							<p class="lvc-single-copy"><?php echo esc_html( $lvc_area_context ); ?></p>
							<?php if ( $lvc_area_links ) : ?><ul class="lvc-single-chip-list"><?php foreach ( $lvc_area_links as $area_link ) : ?><li><a href="<?php echo esc_url( $area_link['url'] ); ?>"><?php echo esc_html( $area_link['name'] ); ?></a></li><?php endforeach; ?></ul><?php endif; ?>
							<div class="lvc-single-btns"><a class="lvc-single-btn" href="#inquiry">Ask if this area fits your trip</a><?php if ( $lvc_area_url ) : ?><a class="lvc-single-btn lvc-single-btn--ghost" href="<?php echo esc_url( $lvc_area_url ); ?>">More <?php echo esc_html( $lvc_area_n ); ?> Villas</a><?php endif; ?></div>
						</section>
					<?php endif; ?>

					<section class="lvc-single-content-block">
						<span class="lvc-single-kicker">Service &amp; Planning</span>
						<h2 class="lvc-single-title">Before arrival, confirm the details that matter</h2>
						<div class="lvc-single-service-grid">
							<article class="lvc-single-service-card"><span>Availability</span><h3>Dates and minimum stay</h3><p>Seasonal minimums and availability can change. We confirm the current rules for your exact dates.</p></article>
							<article class="lvc-single-service-card"><span>Inclusions</span><h3>Staff and chef options</h3><p>Housekeeping, chef service, groceries, and staff vary by villa. We verify what is included and what is extra.</p></article>
							<article class="lvc-single-service-card"><span>Concierge</span><h3>Transfers, tours, and extras</h3><p>Airport transfers, spa, diving, tours, and activities can be planned when available for the property and area.</p></article>
						</div>
						<?php if ( $lvc_cater ) : ?><div class="lvc-single-prose" style="margin-top:1.5rem"><?php echo wp_kses_post( wpautop( $lvc_cater ) ); ?></div><?php endif; ?>
					</section>

					<?php if ( $lvc_faq ) : ?>
						<section class="lvc-single-content-block"><span class="lvc-single-kicker">Good to Know</span><h2 class="lvc-single-title">Questions before booking <?php echo esc_html( $lvc_name ); ?></h2><div class="lvc-single-faq"><?php foreach ( $lvc_faq as $qa ) : ?><details><summary><?php echo esc_html( $qa[0] ); ?></summary><p><?php echo wp_kses_post( wp_strip_all_tags( $qa[1] ) ); ?></p></details><?php endforeach; ?></div></section>
					<?php endif; ?>
				</article>

				<aside class="lvc-single-sidebar" id="inquiry">
					<div class="lvc-single-inquiry">
						<span class="lvc-single-kicker">Request Availability</span>
						<h2 class="lvc-single-title"><?php echo esc_html( $lvc_name ); ?></h2>
						<p class="lvc-single-copy">Share your dates and group details. We will confirm availability, rate details, inclusions, and similar alternatives if this property is not the best fit.</p>
						<ul class="lvc-single-inquiry__meta"><?php foreach ( $lvc_hero_facts as $fact ) : ?><li><?php echo esc_html( $fact ); ?></li><?php endforeach; ?></ul>
						<?php get_template_part( 'template-parts/inquiry-form', null, array( 'property_name' => get_the_title(), 'submit_label' => 'Request Availability' ) ); ?>
						<?php if ( $lvc_wa ) : ?><p class="lvc-single-inquiry__micro"><a href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Prefer WhatsApp?</a> Send your dates, group size, and villa name.</p><?php endif; ?>
					</div>
				</aside>
			</div>
		</section>

		<section class="lvc-single-section lvc-single-final">
			<div class="lvc-single-narrow"><span class="lvc-single-kicker">Villa Match</span><h2 class="lvc-single-title">Not sure if <em><?php echo esc_html( $lvc_name ); ?></em> is the right fit?</h2><p class="lvc-single-copy">Tell us your dates, group size, bedroom needs, and priorities. We can compare this property with nearby villas, Tulum area options, or simpler Cozumel condo stays.</p><div class="lvc-single-btns"><a class="lvc-single-btn" href="#inquiry">Request Availability</a><?php if ( $lvc_area_url ) : ?><a class="lvc-single-btn lvc-single-btn--ghost" href="<?php echo esc_url( $lvc_area_url ); ?>">Explore <?php echo esc_html( $lvc_area_n ); ?></a><?php endif; ?></div></div>
		</section>

		<?php
		$lvc_related = function_exists( 'lvc_related_properties' ) ? lvc_related_properties( $lvc_id, 3 ) : array();
		if ( count( $lvc_related ) < 3 ) {
			$lvc_more_related = lvc_single_related_fallback( $lvc_id, 3 );
			$lvc_related      = array_values( array_unique( array_merge( $lvc_related, $lvc_more_related ) ) );
			$lvc_related      = array_slice( $lvc_related, 0, 3 );
		}
		if ( $lvc_related ) : ?>
			<section class="lvc-single-section lvc-single-related"><div class="lvc-single-wrap"><span class="lvc-single-kicker">Keep Comparing</span><h2 class="lvc-single-title">Similar <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></h2><div class="lvc-grid lvc-grid--3"><?php foreach ( $lvc_related as $rid ) { get_template_part( 'template-parts/card-property', null, array( 'id' => $rid ) ); } ?></div></div></section>
		<?php endif; ?>

		<div class="lvc-single-mobilebar"><span><?php echo esc_html( $lvc_name ); ?></span><a class="lvc-btn" href="#inquiry">Request</a></div>
	</main>
	<?php
endwhile;

get_footer();
