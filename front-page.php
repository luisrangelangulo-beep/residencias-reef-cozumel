<?php
/**
 * Front page — Residencias Reef Cozumel.
 * Conversion-focused direct booking homepage.
 *
 * Ported from Los Cabos's front-page.php (same layout/class structure).
 * Area cards + collection filters now come from this site's real areas
 * (Cozumel, Tulum, Playa del Carmen, Akumal, Puerto Aventuras) instead of
 * Los Cabos's neighborhoods; no hardcoded hero upload path — falls back to
 * a real villa photo from the `area` taxonomy if no dedicated asset exists.
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

$lvc_home_hero_img = lvc_area_image( 'riviera-maya' );

$lvc_area_cards = array(
	array( 'Cozumel', 'cozumel', '/cozumel/', 'Island beachfront villas and Residencias Reef condos, close to world-class diving, ferry access, and a quieter island pace.' ),
	array( 'Tulum', 'tulum', '/tulum/', 'Bohemian-luxury villas and jungle-beach estates across Tulum, Sian Ka\'an, and the Tulum Beach Zone.' ),
	array( 'Playa Del Carmen', 'playa-del-carmen', '/playa-del-carmen/', 'Beachfront and Playacar villas minutes from 5th Avenue, dining, and nightlife.' ),
	array( 'Akumal', 'akumal', '/akumal/', 'Laid-back bayfront villas near sea-turtle snorkeling and quieter Caribbean beaches.' ),
);

$lvc_collection_filters = array_merge(
	array( array( 'All Villas', $lvc_arch ) ),
	array(
		array( 'Cozumel', home_url( '/cozumel/' ) ),
		array( 'Tulum', home_url( '/tulum/' ) ),
		array( 'Playa Del Carmen', home_url( '/playa-del-carmen/' ) ),
		array( 'Akumal', home_url( '/akumal/' ) ),
		array( 'Puerto Aventuras', home_url( '/puerto-aventuras/' ) ),
	)
);
?>

<style>
	.lcv-home-modern{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}.lcv-home-modern *{box-sizing:border-box}.lcv-home-wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lcv-home-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lcv-home-section{padding:clamp(4rem,7vw,7rem) 0}.lcv-home-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lcv-home-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lcv-home-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.65rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lcv-home-title em{font-style:italic;color:var(--lvc-accent)}.lcv-home-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.25vw,1.08rem);font-weight:300;line-height:1.82}.lcv-home-head{text-align:center;margin:0 auto clamp(2rem,4vw,3rem);max-width:900px}.lcv-home-head .lcv-home-copy{max-width:760px;margin:1rem auto 0}.lcv-home-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center}.lcv-home-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lcv-home-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
	.lcv-home-hero{position:relative;min-height:min(760px,86vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--home-hero-img,none) center/cover no-repeat}.lcv-home-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 48%,rgba(10,12,15,.48)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.25) 52%,rgba(10,12,15,.64))}.lcv-home-hero__grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.42fr);gap:clamp(2rem,5vw,5rem);align-items:end}.lcv-home-hero h1{max-width:850px;margin:0;font-family:var(--lvc-font-display);font-size:clamp(2.7rem,5vw,5.25rem);font-weight:200;line-height:1.08;color:var(--lvc-text)}.lcv-home-hero h1 em{display:block;margin-top:.25rem;font-style:italic;color:var(--lvc-accent)}.lcv-home-hero__sub{max-width:720px;margin:1.35rem 0 0;color:rgba(243,243,241,.84);font-size:clamp(1rem,1.3vw,1.13rem);line-height:1.78}.lcv-home-hero__actions{margin-top:1.8rem}.lcv-home-match{background:linear-gradient(180deg,rgba(16,21,28,.95),rgba(10,12,15,.88));border:1px solid rgba(255,255,255,.14);padding:1.5rem;box-shadow:0 24px 70px rgba(0,0,0,.36)}.lcv-home-match h2{margin:0 0 .6rem;font-family:var(--lvc-font-display);font-weight:300;font-size:1.25rem;color:var(--lvc-text)}.lcv-home-match p{margin:0;color:var(--lvc-soft);font-size:.9rem;line-height:1.7}.lcv-home-match__facts{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;margin:1.1rem 0}.lcv-home-match__fact{border:1px solid var(--lvc-border);background:rgba(255,255,255,.035);padding:.85rem}.lcv-home-match__fact strong{display:block;font-family:var(--lvc-font-display);font-size:1.45rem;font-weight:300;color:var(--lvc-text);line-height:1}.lcv-home-match__fact span{display:block;margin-top:.35rem;color:var(--lvc-muted);font-size:.66rem;letter-spacing:.12em;text-transform:uppercase}
	.lcv-proof{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border);padding:1.15rem 0}.lvc-proof__inner{display:flex;flex-wrap:wrap;justify-content:center;gap:.8rem}.lvc-proof__item{border:1px solid var(--lvc-border);padding:.6rem .85rem;color:var(--lvc-soft);font-size:.78rem;text-transform:uppercase}.lvc-proof__item strong{color:var(--lvc-accent);font-weight:500}.lcv-intro-grid{display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);gap:clamp(2rem,5vw,5rem);align-items:center}.lcv-home-panel{border-left:1px solid var(--lvc-border);padding-left:clamp(1.5rem,3vw,3rem)}.lcv-home-panel ul{list-style:none;margin:1.35rem 0 0;padding:0;display:grid;gap:.8rem}.lcv-home-panel li{position:relative;padding-left:1.25rem;color:var(--lvc-soft);line-height:1.65}.lcv-home-panel li:before{content:'✓';position:absolute;left:0;color:var(--lvc-accent)}
	.lcv-filter-pills{display:flex;flex-wrap:wrap;justify-content:center;gap:.65rem;margin:0 auto 2.2rem;max-width:1040px}.lcv-filter-pill{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--lvc-border);background:rgba(255,255,255,.025);color:var(--lvc-soft)!important;padding:.62rem .9rem;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}.lcv-filter-pill:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important;background:var(--lvc-accent-soft)}.lcv-villa-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.35rem}.lcv-home-pagination{display:flex;justify-content:center;align-items:center;gap:.45rem;margin-top:2.5rem;flex-wrap:wrap}.lcv-home-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:42px;padding:.55rem .8rem;border:1px solid var(--lvc-border);color:var(--lvc-soft);background:rgba(255,255,255,.02)}.lcv-home-pagination .page-numbers.current{background:var(--lvc-accent);border-color:var(--lvc-accent);color:#fff}.lcv-home-pagination .page-numbers:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)}
	.lcv-area-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.lcv-area-tile{position:relative;min-height:330px;display:flex;align-items:flex-end;padding:1.35rem;border:1px solid var(--lvc-border);background:var(--lvc-card) var(--area-img,none) center/cover no-repeat;overflow:hidden}.lcv-area-tile:before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,12,15,.12),rgba(10,12,15,.92))}.lcv-area-tile__body{position:relative;z-index:1}.lcv-area-tile h3{margin:0;font-family:var(--lvc-font-display);font-size:1.35rem;font-weight:300;color:var(--lvc-text)}.lcv-area-tile p{margin:.55rem 0 0;color:var(--lvc-soft);font-size:.86rem;line-height:1.55}.lcv-area-tile span{display:block;margin-top:.85rem;color:var(--lvc-accent);font-size:.82rem}.lcv-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.lcv-step{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.5rem}.lcv-step__num{display:block;color:var(--lvc-accent);font-family:var(--lvc-font-display);font-size:1.45rem;font-weight:300;margin-bottom:.7rem}.lcv-step h3,.lcv-concierge-card h3{margin:0 0 .6rem;font-family:var(--lvc-font-display);font-weight:300;color:var(--lvc-text)}.lcv-step p,.lcv-concierge-card p{margin:0;color:var(--lvc-soft);line-height:1.65;font-size:.92rem}.lcv-concierge-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.9rem}.lcv-concierge-card{background:var(--lvc-bg);border:1px solid var(--lvc-border);padding:1.25rem;min-height:150px}.lcv-final-cta{background:var(--lvc-bg-deep);border-top:1px solid rgba(255,255,255,.12);text-align:center}.lcv-final-cta .lcv-home-copy{max-width:680px;margin:1rem auto 1.6rem}.lcv-final-cta .lcv-home-btns{justify-content:center}
	@media(max-width:1100px){.lcv-home-hero__grid,.lcv-intro-grid{grid-template-columns:1fr}.lcv-area-grid,.lcv-villa-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.lcv-concierge-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.lcv-home-panel{border-left:0;padding-left:0}}@media(max-width:720px){.lcv-home-wrap,.lcv-home-narrow{width:calc(100% - 2rem)}.lcv-home-hero{min-height:auto;padding:6rem 0 4rem}.lcv-home-hero h1{font-size:clamp(2.3rem,12vw,3.5rem)}.lcv-home-btns{display:grid;grid-template-columns:1fr}.lcv-home-match__facts,.lcv-area-grid,.lcv-steps,.lcv-concierge-grid,.lcv-villa-grid{grid-template-columns:1fr}.lvc-proof__item{width:100%;text-align:center}.lcv-filter-pills{justify-content:flex-start}}
</style>

<main class="lvc-home-modern">

	<section class="lcv-home-hero" <?php echo $lvc_home_hero_img ? 'style="--home-hero-img:url(\'' . esc_url( $lvc_home_hero_img ) . '\')"' : ''; ?> aria-label="Luxury villa rentals in the Riviera Maya">
		<div class="lcv-home-wrap lcv-home-hero__grid">
			<div>
				<span class="lcv-home-kicker"><?php echo esc_html( lvc_brand() ); ?></span>
				<h1>Luxury Villa Rentals in the Riviera Maya <em>with Private Concierge</em></h1>
				<p class="lcv-home-hero__sub">Private estates, beachfront villas, and island condos for families, groups, celebrations, and longer stays across Cozumel, Tulum, Playa del Carmen, and Akumal.</p>
				<div class="lcv-home-btns lcv-home-hero__actions"><a class="lcv-home-btn" href="<?php echo esc_url( $lvc_req ); ?>">Submit Your Villa Request</a><a class="lcv-home-btn lcv-home-btn--ghost" href="<?php echo esc_url( $lvc_arch ); ?>">Browse Villas</a></div>
			</div>
			<aside class="lcv-home-match"><h2>Tell us your dates. We will shortlist the right villas.</h2><p>Skip the endless browsing. Share your group size, dates, preferred area, and service needs — we will confirm fit, availability, and realistic alternatives.</p><div class="lcv-home-match__facts"><div class="lcv-home-match__fact"><strong>Direct</strong><span>No OTA markup</span></div><div class="lcv-home-match__fact"><strong>Local</strong><span>Villa guidance</span></div></div><a class="lcv-home-btn" href="<?php echo esc_url( $lvc_req ); ?>">Get Villa Matches</a></aside>
		</div>
	</section>

	<section class="lcv-proof"><div class="lcv-home-wrap lvc-proof__inner"><div class="lvc-proof__item"><strong>Private villas</strong> for families & groups</div><div class="lvc-proof__item"><strong>Concierge support</strong> before arrival</div><div class="lvc-proof__item"><strong>Chef, transfers & tours</strong> on request</div><div class="lvc-proof__item"><strong>Direct booking</strong> guidance</div></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap lcv-intro-grid"><div><span class="lcv-home-kicker">Direct Booking Collection</span><h2 class="lcv-home-title">Private villas selected for <em>how your group actually travels</em></h2></div><div class="lcv-home-panel lcv-home-copy"><p>The Riviera Maya is not a one-size-fits-all destination. Cozumel, Tulum, Playa del Carmen, and Akumal each work for different kinds of trips. We help guests compare location, layout, service level, beach access, and group priorities before they book.</p><ul><li>Beachfront, island, jungle, and town-center villas.</li><li>Options for family stays, celebrations, corporate retreats, and larger groups.</li><li>Concierge planning for chef service, airport transfers, tours, spa, diving, and local experiences.</li></ul></div></div></section>

	<section class="lcv-home-section" id="feat"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Villa Collection</span><h2 class="lcv-home-title">Browse luxury villas in the <em>Riviera Maya</em></h2><p class="lcv-home-copy">Start with the full villa grid, or jump into the area that best matches your trip.</p></header><nav class="lcv-filter-pills" aria-label="Villa collection filters"><?php foreach ( $lvc_collection_filters as $filter ) : ?><a class="lcv-filter-pill" href="<?php echo esc_url( $filter[1] ); ?>"><?php echo esc_html( $filter[0] ); ?></a><?php endforeach; ?></nav><?php $lvc_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ); $lvc_villas = new WP_Query( array( 'post_type' => $lvc_cpt, 'posts_per_page' => 9, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'paged' => $lvc_paged ) ); if ( $lvc_villas->have_posts() ) : ?><div class="lcv-villa-grid"><?php while ( $lvc_villas->have_posts() ) : $lvc_villas->the_post(); get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); endwhile; ?></div><?php if ( $lvc_villas->max_num_pages > 1 ) : ?><nav class="lcv-home-pagination" aria-label="Villa pagination"><?php echo wp_kses_post( paginate_links( array( 'total' => $lvc_villas->max_num_pages, 'current' => $lvc_paged, 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ) ); ?></nav><?php endif; wp_reset_postdata(); endif; ?></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Where to Stay</span><h2 class="lcv-home-title">Choose the right <em>Riviera Maya area</em></h2><p class="lcv-home-copy">The right villa starts with the right location. These are the areas most guests compare first.</p></header><div class="lcv-area-grid"><?php foreach ( $lvc_area_cards as $area ) : $area_img = lvc_area_image( $area[1] ); ?><a class="lcv-area-tile" href="<?php echo esc_url( home_url( $area[2] ) ); ?>" style="<?php echo $area_img ? '--area-img:url(' . esc_url( $area_img ) . ')' : ''; ?>"><div class="lcv-area-tile__body"><h3><?php echo esc_html( $area[0] ); ?></h3><p><?php echo esc_html( $area[3] ); ?></p><span>Explore <?php echo esc_html( $area[0] ); ?> &rarr;</span></div></a><?php endforeach; ?></div></div></section>

	<section class="lcv-home-section"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">How It Works</span><h2 class="lcv-home-title">A simpler way to book a <em>private villa</em></h2></header><div class="lcv-steps"><div class="lcv-step"><span class="lcv-step__num">01</span><h3>Share your trip details</h3><p>Tell us your dates, group size, bedroom needs, preferred area, and service needs.</p></div><div class="lcv-step"><span class="lcv-step__num">02</span><h3>Review matched villas</h3><p>We help compare realistic options based on location, layout, service level, privacy, and fit.</p></div><div class="lcv-step"><span class="lcv-step__num">03</span><h3>Plan the stay</h3><p>Concierge planning can include airport transfers, private chef, groceries, tours, spa, diving, and activities.</p></div></div></div></section>

	<section class="lcv-home-section lcv-home-section--alt"><div class="lcv-home-wrap"><header class="lcv-home-head"><span class="lcv-home-kicker">Concierge Services</span><h2 class="lcv-home-title">Beyond the villa: <em>complete stay planning</em></h2><p class="lcv-home-copy">Luxury villa trips work best when the details are handled before arrival.</p></header><div class="lcv-concierge-grid"><div class="lcv-concierge-card"><h3>Airport Transfers</h3><p>Private transportation from Cancún International Airport.</p></div><div class="lcv-concierge-card"><h3>Private Chef</h3><p>In-villa dining, celebrations, breakfast service, and group meals.</p></div><div class="lcv-concierge-card"><h3>Diving & Snorkeling</h3><p>Reef diving, cenotes, and Caribbean boat days.</p></div><div class="lcv-concierge-card"><h3>Tours & Activities</h3><p>Mayan ruins, beach clubs, fishing, and ATV tours.</p></div><div class="lcv-concierge-card"><h3>Spa & Wellness</h3><p>In-villa massage, wellness sessions, and relaxation services.</p></div></div></div></section>

	<section class="lcv-home-section lcv-final-cta"><div class="lcv-home-narrow"><span class="lcv-home-kicker">Start Planning</span><h2 class="lcv-home-title">Tell us what your group needs. <em>We will help narrow the search.</em></h2><p class="lcv-home-copy">Share your dates, preferred area, group size, and villa priorities. We will help identify the strongest Riviera Maya options and next steps.</p><div class="lcv-home-btns"><a class="lcv-home-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><?php if ( $lvc_wa ) : ?><a class="lcv-home-btn lcv-home-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a><?php endif; ?></div></div></section>

</main>
<?php get_footer(); ?>
