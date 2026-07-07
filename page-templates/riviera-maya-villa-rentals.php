<?php
/**
 * Dedicated page template for /riviera-maya-villa-rentals/.
 * Broad Riviera Maya villa landing page with stronger SEO and inquiry path.
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

if ( ! function_exists( 'lvc_rm_area_image' ) ) {
	function lvc_rm_area_image( $slug ) {
		$q = new WP_Query( array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => $slug ) ),
		) );
		$img = $q->have_posts() ? lvc_property_image( $q->posts[0], 'large' ) : '';
		wp_reset_postdata();
		return $img;
	}
}

if ( ! function_exists( 'lvc_rm_area_url' ) ) {
	function lvc_rm_area_url( $slug, $fallback = '/' ) {
		if ( function_exists( 'lvc_area_lander_url' ) ) {
			return lvc_area_lander_url( $slug );
		}
		$term = get_term_by( 'slug', $slug, 'area' );
		$link = $term ? get_term_link( $term ) : false;
		return ( $link && ! is_wp_error( $link ) ) ? $link : home_url( $fallback );
	}
}

$lvc_hero_img = lvc_rm_area_image( 'riviera-maya' );
if ( ! $lvc_hero_img ) {
	$lvc_hero_img = lvc_rm_area_image( 'tulum' );
}

$lvc_destinations = array(
	array( 'Cozumel', 'cozumel', 'Beachfront condos and island stays for divers, couples, and smaller groups.' ),
	array( 'Tulum', 'tulum', 'Private villas across the beach zone, town, Soliman Bay, Tankah Bay, and Sian Ka’an.' ),
	array( 'Playa del Carmen', 'playa-del-carmen', 'Villas close to restaurants, beach clubs, shopping, nightlife, and easy arrival logistics.' ),
	array( 'Akumal', 'akumal', 'Quieter bayfront villas for families, snorkeling, and relaxed beach stays.' ),
	array( 'Puerto Aventuras', 'puerto-aventuras', 'Gated marina-style stays with villas, boating access, and family-friendly logistics.' ),
);

$lvc_tulum_areas = array(
	array( 'Soliman Bay', 'soliman-bay', 'Quiet beachfront villas north of Tulum, often best for family trips, calm water, and privacy.' ),
	array( 'Tulum Beach Zone', 'tulum-beach-zone', 'For guests who want restaurants, beach clubs, wellness, and the boutique beach atmosphere nearby.' ),
	array( 'Tulum Town', 'town-jungle', 'Better value and practical logistics for groups who want restaurants and flexible villa options.' ),
	array( 'Tankah Bay', 'tankah-bay-riviera-maya', 'Beachfront and bayfront villas between Tulum and Akumal with a quieter residential feel.' ),
	array( 'Sian Ka’an', 'sian-kaan', 'Remote, nature-forward private stays for guests prioritizing privacy and scenery.' ),
);

$lvc_filters = array(
	array( 'All Villas', $lvc_arch ),
	array( 'Tulum Villas', lvc_rm_area_url( 'tulum', '/tulum-villa-rentals/' ) ),
	array( 'Soliman Bay', lvc_rm_area_url( 'soliman-bay', '/soliman-bay/' ) ),
	array( 'Tulum Beach Zone', lvc_rm_area_url( 'tulum-beach-zone', '/tulum-beach-zone-villas/' ) ),
	array( 'Tulum Town', lvc_rm_area_url( 'town-jungle', '/tulum-town-jungle-villas/' ) ),
	array( 'Cozumel', lvc_rm_area_url( 'cozumel', '/cozumel/' ) ),
	array( 'Playa del Carmen', lvc_rm_area_url( 'playa-del-carmen', '/playa-del-carmen/' ) ),
	array( 'Akumal', lvc_rm_area_url( 'akumal', '/akumal/' ) ),
);

$lvc_faqs = array(
	array( 'q' => 'What is the best area for a Riviera Maya villa rental?', 'a' => 'It depends on the trip. Tulum and Soliman Bay are strong for private villa stays, Akumal works well for quieter family beach trips, Playa del Carmen is better for restaurants and nightlife, and Cozumel works best for simpler island condo stays and diving.' ),
	array( 'q' => 'Should I book a Cozumel condo or a private Riviera Maya villa?', 'a' => 'Choose a Cozumel condo for a simpler beachfront stay, especially for couples, divers, or smaller groups. Choose a private villa for larger families, chef service, private pools, events, privacy, and a higher-service trip.' ),
	array( 'q' => 'Can you help with chef service, transfers, and activities?', 'a' => 'Yes. Concierge support can include private chef service, groceries, airport transfers, tours, diving, spa, wellness, and activity planning when available for the selected villa.' ),
	array( 'q' => 'How do I request villa options?', 'a' => 'Send your dates, group size, bedroom needs, preferred area, budget range, and must-haves. We will help shortlist realistic villa options and alternatives.' ),
);

if ( function_exists( 'lvc_jsonld' ) ) {
	$lvc_faq_qas = array();
	foreach ( $lvc_faqs as $lvc_faq_item ) {
		$lvc_faq_qas[] = array( '@type' => 'Question', 'name' => $lvc_faq_item['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $lvc_faq_item['a'] ) );
	}
	lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $lvc_faq_qas ) );
}

$lvc_villas = new WP_Query( array( 'post_type' => $lvc_cpt, 'post_status' => 'publish', 'posts_per_page' => 9, 'orderby' => 'date', 'order' => 'DESC' ) );
?>

<style>
	.lvc-rm{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}.lvc-rm *{box-sizing:border-box}.lvc-rm-wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-rm-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-rm-section{padding:clamp(4rem,7vw,7rem) 0}.lvc-rm-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lvc-rm-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lvc-rm-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.65rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lvc-rm-title em{font-style:italic;color:var(--lvc-accent)}.lvc-rm-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.2vw,1.08rem);font-weight:300;line-height:1.82}.lvc-rm-head{text-align:center;margin:0 auto clamp(2rem,4vw,3rem);max-width:900px}.lvc-rm-head .lvc-rm-copy{max-width:760px;margin:1rem auto 0}.lvc-rm-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center}.lvc-rm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lvc-rm-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
	.lvc-rm-hero{position:relative;min-height:min(720px,82vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--rm-hero-img,none) center/cover no-repeat}.lvc-rm-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 48%,rgba(10,12,15,.48)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.25) 52%,rgba(10,12,15,.64))}.lvc-rm-hero__grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.42fr);gap:clamp(2rem,5vw,5rem);align-items:end}.lvc-rm-hero h1{max-width:930px;margin:0;font-family:var(--lvc-font-display);font-size:clamp(2.55rem,5vw,5.15rem);font-weight:200;line-height:1.08;color:var(--lvc-text)}.lvc-rm-hero h1 em{display:block;margin-top:.25rem;font-style:italic;color:var(--lvc-accent)}.lvc-rm-hero__sub{max-width:790px;margin:1.35rem 0 0;color:rgba(243,243,241,.84);font-size:clamp(1rem,1.3vw,1.13rem);line-height:1.78}.lvc-rm-panel{background:linear-gradient(180deg,rgba(16,21,28,.95),rgba(10,12,15,.88));border:1px solid rgba(255,255,255,.14);padding:1.5rem;box-shadow:0 24px 70px rgba(0,0,0,.36)}.lvc-rm-panel h2{margin:0 0 .6rem;font-family:var(--lvc-font-display);font-weight:300;font-size:1.25rem;color:var(--lvc-text)}.lvc-rm-panel p{margin:0;color:var(--lvc-soft);font-size:.9rem;line-height:1.7}
	.lvc-rm-intro{display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);gap:clamp(2rem,5vw,5rem);align-items:center}.lvc-rm-panelcopy{border-left:1px solid var(--lvc-border);padding-left:clamp(1.5rem,3vw,3rem)}.lvc-rm-panelcopy ul{list-style:none;margin:1.35rem 0 0;padding:0;display:grid;gap:.8rem}.lvc-rm-panelcopy li{position:relative;padding-left:1.25rem;color:var(--lvc-soft);line-height:1.65}.lvc-rm-panelcopy li:before{content:'✓';position:absolute;left:0;color:var(--lvc-accent)}.lvc-rm-paths,.lvc-rm-compare{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.lvc-rm-path,.lvc-rm-compare-card{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.6rem}.lvc-rm-path h3,.lvc-rm-compare-card h3{margin:0 0 .7rem;font-family:var(--lvc-font-display);font-size:1.45rem;font-weight:300;color:var(--lvc-text)}.lvc-rm-path p{margin:0 0 1.2rem;color:var(--lvc-soft);line-height:1.72}.lvc-rm-compare-card ul{margin:0;padding:0;list-style:none;display:grid;gap:.7rem}.lvc-rm-compare-card li{position:relative;padding-left:1.1rem;color:var(--lvc-soft);line-height:1.55}.lvc-rm-compare-card li:before{content:'•';position:absolute;left:0;color:var(--lvc-accent)}
	.lvc-rm-cardgrid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1rem}.lvc-rm-area{position:relative;min-height:285px;display:flex;align-items:flex-end;padding:1.25rem;border:1px solid var(--lvc-border);background:var(--lvc-card) var(--area-img,none) center/cover no-repeat;overflow:hidden}.lvc-rm-area:before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,12,15,.12),rgba(10,12,15,.92))}.lvc-rm-area__body{position:relative;z-index:1}.lvc-rm-area h3{margin:0;font-family:var(--lvc-font-display);font-size:1.25rem;font-weight:300;color:var(--lvc-text)}.lvc-rm-area p{margin:.55rem 0 0;color:var(--lvc-soft);font-size:.84rem;line-height:1.55}.lvc-rm-area span{display:block;margin-top:.85rem;color:var(--lvc-accent);font-size:.82rem}
	.lvc-rm-filters{display:flex;flex-wrap:wrap;justify-content:center;gap:.65rem;margin:0 auto 2.2rem;max-width:1120px}.lvc-rm-filter{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--lvc-border);background:rgba(255,255,255,.025);color:var(--lvc-soft)!important;padding:.62rem .9rem;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}.lvc-rm-filter:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important;background:var(--lvc-accent-soft)}.lvc-rm-villas{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.35rem}.lvc-rm-faq{display:grid;gap:.75rem}.lvc-rm-faq details{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1rem 1.15rem}.lvc-rm-faq summary{cursor:pointer;color:var(--lvc-text);font-family:var(--lvc-font-display);font-weight:300}.lvc-rm-faq p{margin:.75rem 0 0;color:var(--lvc-soft);line-height:1.7}.lvc-rm-final{background:var(--lvc-bg-deep);border-top:1px solid rgba(255,255,255,.12);text-align:center}.lvc-rm-final .lvc-rm-copy{max-width:700px;margin:1rem auto 1.6rem}.lvc-rm-final .lvc-rm-btns{justify-content:center}
	@media(max-width:1200px){.lvc-rm-cardgrid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:900px){.lvc-rm-hero__grid,.lvc-rm-intro,.lvc-rm-paths,.lvc-rm-compare{grid-template-columns:1fr}.lvc-rm-villas{grid-template-columns:repeat(2,minmax(0,1fr))}.lvc-rm-panelcopy{border-left:0;padding-left:0}}@media(max-width:720px){.lvc-rm-wrap,.lvc-rm-narrow{width:calc(100% - 2rem)}.lvc-rm-hero{min-height:auto;padding:6rem 0 4rem}.lvc-rm-hero h1{font-size:clamp(2.3rem,12vw,3.5rem)}.lvc-rm-btns{display:grid;grid-template-columns:1fr}.lvc-rm-villas,.lvc-rm-cardgrid{grid-template-columns:1fr}.lvc-rm-filters{justify-content:flex-start}}
</style>

<main class="lvc-rm">
	<section class="lvc-rm-hero" <?php echo $lvc_hero_img ? 'style="--rm-hero-img:url(\'' . esc_url( $lvc_hero_img ) . '\')"' : ''; ?> aria-label="Riviera Maya villa rentals">
		<div class="lvc-rm-wrap lvc-rm-hero__grid"><div><span class="lvc-rm-kicker">Residencias Reef Cozumel</span><h1>Riviera Maya Villa Rentals <em>matched to your group</em></h1><p class="lvc-rm-hero__sub">Private villas, beachfront estates, and selected Cozumel condo stays across Tulum, Soliman Bay, Akumal, Playa del Carmen, Puerto Aventuras, and Cozumel. Compare areas first, then request the villas that fit your dates, group size, and service needs.</p><div class="lvc-rm-btns" style="margin-top:1.8rem"><a class="lvc-rm-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><a class="lvc-rm-btn lvc-rm-btn--ghost" href="#villas">Browse Villas</a></div></div><aside class="lvc-rm-panel"><h2>Start with the area, not only the grid.</h2><p>The best villa choice depends on location, bedroom layout, beach access, service level, arrival logistics, and group priorities.</p><div class="lvc-rm-btns" style="margin-top:1rem"><a class="lvc-rm-btn" href="<?php echo esc_url( $lvc_req ); ?>">Tell Us Your Trip</a></div></aside></div>
	</section>

	<section class="lvc-rm-section lvc-rm-section--alt"><div class="lvc-rm-wrap lvc-rm-intro"><div><span class="lvc-rm-kicker">Villa Strategy</span><h2 class="lvc-rm-title">Cozumel is the start. <em>The right villa is the goal.</em></h2></div><div class="lvc-rm-panelcopy lvc-rm-copy"><p>Residencias Reef and Cozumel attract travelers looking for a relaxed Caribbean stay, especially divers, couples, and smaller groups. Many travelers, however, need a private Riviera Maya villa with more bedrooms, chef service, staff, private pool, beachfront access, or better space for a family trip or celebration.</p><ul><li>Use Cozumel condos for simple beachfront stays and lower nightly rates.</li><li>Use Tulum, Soliman Bay, Akumal, Playa del Carmen, and Puerto Aventuras for larger private villa trips.</li><li>Match the area before the villa: privacy, restaurants, beach access, service level, and logistics matter.</li></ul></div></div></section>

	<section class="lvc-rm-section"><div class="lvc-rm-wrap"><header class="lvc-rm-head"><span class="lvc-rm-kicker">Two Booking Paths</span><h2 class="lvc-rm-title">Choose the path that fits <em>your real trip</em></h2></header><div class="lvc-rm-paths"><article class="lvc-rm-path"><h3>Cozumel condos for simple stays</h3><p>Best for couples, divers, small families, and guests who want beachfront access without paying for a full villa experience.</p><a class="lvc-rm-btn lvc-rm-btn--ghost" href="<?php echo esc_url( lvc_rm_area_url( 'cozumel', '/cozumel/' ) ); ?>">View Cozumel Stays</a></article><article class="lvc-rm-path"><h3>Private villas for larger trips</h3><p>Best for families, group trips, chef service, private pools, celebrations, retreats, and guests who want more privacy and support.</p><a class="lvc-rm-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a></article></div></div></section>

	<section class="lvc-rm-section lvc-rm-section--alt"><div class="lvc-rm-wrap"><header class="lvc-rm-head"><span class="lvc-rm-kicker">Where to Stay</span><h2 class="lvc-rm-title">Compare Riviera Maya <em>villa destinations</em></h2><p class="lvc-rm-copy">The right destination filters the wrong villas out quickly. Start here before comparing individual properties.</p></header><div class="lvc-rm-cardgrid"><?php foreach ( $lvc_destinations as $area ) : $img = lvc_rm_area_image( $area[1] ); ?><a class="lvc-rm-area" href="<?php echo esc_url( lvc_rm_area_url( $area[1] ) ); ?>" style="<?php echo $img ? '--area-img:url(' . esc_url( $img ) . ')' : ''; ?>"><div class="lvc-rm-area__body"><h3><?php echo esc_html( $area[0] ); ?></h3><p><?php echo esc_html( $area[2] ); ?></p><span>Explore <?php echo esc_html( $area[0] ); ?> &rarr;</span></div></a><?php endforeach; ?></div></div></section>

	<section class="lvc-rm-section"><div class="lvc-rm-wrap"><header class="lvc-rm-head"><span class="lvc-rm-kicker">Tulum Villa Areas</span><h2 class="lvc-rm-title">Tulum is not one market: <em>choose carefully</em></h2><p class="lvc-rm-copy">For villa inquiries, Tulum area selection matters as much as the property itself.</p></header><div class="lvc-rm-cardgrid"><?php foreach ( $lvc_tulum_areas as $area ) : $img = lvc_rm_area_image( $area[1] ); ?><a class="lvc-rm-area" href="<?php echo esc_url( lvc_rm_area_url( $area[1] ) ); ?>" style="<?php echo $img ? '--area-img:url(' . esc_url( $img ) . ')' : ''; ?>"><div class="lvc-rm-area__body"><h3><?php echo esc_html( $area[0] ); ?></h3><p><?php echo esc_html( $area[2] ); ?></p><span>Explore <?php echo esc_html( $area[0] ); ?> villas &rarr;</span></div></a><?php endforeach; ?></div></div></section>

	<section class="lvc-rm-section lvc-rm-section--alt"><div class="lvc-rm-wrap"><header class="lvc-rm-head"><span class="lvc-rm-kicker">Decision Help</span><h2 class="lvc-rm-title">Cozumel condo or <em>Riviera Maya villa?</em></h2></header><div class="lvc-rm-compare"><article class="lvc-rm-compare-card"><h3>Choose a Cozumel condo if...</h3><ul><li>You are a couple, small family, or diving-focused group.</li><li>You want a lower nightly rate and simple beachfront base.</li><li>You do not need chef service, staff, or a large private villa layout.</li></ul></article><article class="lvc-rm-compare-card"><h3>Choose a private villa if...</h3><ul><li>You need more bedrooms, privacy, and shared spaces.</li><li>You want chef service, staff, private pool, or concierge planning.</li><li>You are planning a family trip, celebration, group retreat, or longer luxury stay.</li></ul></article></div></div></section>

	<section class="lvc-rm-section" id="villas"><div class="lvc-rm-wrap"><header class="lvc-rm-head"><span class="lvc-rm-kicker">Villa Collection</span><h2 class="lvc-rm-title">Browse Riviera Maya <em>villas and condos</em></h2><p class="lvc-rm-copy">Browse the current collection, then request help if you want the realistic shortlist for your dates.</p></header><nav class="lvc-rm-filters" aria-label="Riviera Maya villa filters"><?php foreach ( $lvc_filters as $filter ) : ?><a class="lvc-rm-filter" href="<?php echo esc_url( $filter[1] ); ?>"><?php echo esc_html( $filter[0] ); ?></a><?php endforeach; ?></nav><?php if ( $lvc_villas->have_posts() ) : ?><div class="lvc-rm-villas"><?php while ( $lvc_villas->have_posts() ) : $lvc_villas->the_post(); get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); endwhile; wp_reset_postdata(); ?></div><div style="text-align:center;margin-top:2rem"><a class="lvc-rm-btn lvc-rm-btn--ghost" href="<?php echo esc_url( $lvc_arch ); ?>">View All Villas</a></div><?php endif; ?></div></section>

	<section class="lvc-rm-section lvc-rm-section--alt"><div class="lvc-rm-narrow"><header class="lvc-rm-head"><span class="lvc-rm-kicker">FAQ</span><h2 class="lvc-rm-title">Riviera Maya villa rental <em>questions</em></h2></header><div class="lvc-rm-faq"><?php foreach ( $lvc_faqs as $faq ) : ?><details><summary><?php echo esc_html( $faq['q'] ); ?></summary><p><?php echo esc_html( $faq['a'] ); ?></p></details><?php endforeach; ?></div></div></section>

	<section class="lvc-rm-section lvc-rm-final"><div class="lvc-rm-narrow"><span class="lvc-rm-kicker">Start Planning</span><h2 class="lvc-rm-title">Tell us what your group needs. <em>We will narrow the search.</em></h2><p class="lvc-rm-copy">Share your dates, group size, preferred area, budget range, and must-haves. We will help identify whether a Cozumel condo, Tulum villa, or another Riviera Maya area is the stronger fit.</p><div class="lvc-rm-btns"><a class="lvc-rm-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><?php if ( $lvc_wa ) : ?><a class="lvc-rm-btn lvc-rm-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a><?php endif; ?></div></div></section>
</main>

<?php get_footer(); ?>
