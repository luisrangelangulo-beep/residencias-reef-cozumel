<?php
/**
 * Template Name: Magazine Hub
 * Dedicated magazine hub for /magazine/ even when the page was built in Elementor.
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_req  = lvc_page_url( 'request' );
$lvc_arch = lvc_archive_url();
$lvc_wa   = lvc_whatsapp_url();

$lvc_hero_img = get_the_post_thumbnail_url( get_the_ID(), 'full' );
if ( ! $lvc_hero_img ) {
	$hero_q = new WP_Query( array(
		'post_type'      => lvc_config( 'cpt', 'villas' ),
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'tax_query'      => array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => 'tulum' ) ),
	) );
	if ( $hero_q->have_posts() ) {
		$lvc_hero_img = lvc_property_image( $hero_q->posts[0], 'full' );
	}
	wp_reset_postdata();
}

$lvc_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$lvc_posts = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 9,
	'paged'               => $lvc_paged,
	'ignore_sticky_posts' => false,
) );

$lvc_featured = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 1,
	'ignore_sticky_posts' => false,
) );
$lvc_featured_id = $lvc_featured->have_posts() ? (int) $lvc_featured->posts[0]->ID : 0;

$lvc_area_links = array(
	'Tulum Villas'     => function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'tulum' ) : home_url( '/tulum-villa-rentals/' ),
	'Soliman Bay'      => function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'soliman-bay' ) : home_url( '/soliman-bay/' ),
	'Tulum Beach Zone' => function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'tulum-beach-zone' ) : home_url( '/tulum-beach-zone-villas/' ),
	'Cozumel'          => function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'cozumel' ) : home_url( '/cozumel/' ),
	'Akumal'           => function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'akumal' ) : home_url( '/akumal/' ),
	'Playa del Carmen' => function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'playa-del-carmen' ) : home_url( '/playa-del-carmen/' ),
);
?>

<style>
	.lvc-maghub{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}.lvc-maghub *{box-sizing:border-box}.lvc-maghub-wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-maghub-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-maghub-section{padding:clamp(4rem,7vw,7rem) 0}.lvc-maghub-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lvc-maghub-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lvc-maghub-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.7rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lvc-maghub-title em{font-style:italic;color:var(--lvc-accent)}.lvc-maghub-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.2vw,1.08rem);font-weight:300;line-height:1.82}.lvc-maghub-head{text-align:center;margin:0 auto clamp(2rem,4vw,3rem);max-width:900px}.lvc-maghub-head .lvc-maghub-copy{max-width:760px;margin:1rem auto 0}.lvc-maghub-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center}.lvc-maghub-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lvc-maghub-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
	.lvc-maghub-hero{position:relative;min-height:min(720px,82vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--maghub-hero-img,none) center/cover no-repeat}.lvc-maghub-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 48%,rgba(10,12,15,.48)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.25) 52%,rgba(10,12,15,.64))}.lvc-maghub-hero h1{max-width:930px;margin:0;font-family:var(--lvc-font-display);font-size:clamp(2.55rem,5vw,5rem);font-weight:200;line-height:1.08;color:var(--lvc-text)}.lvc-maghub-hero__sub{max-width:780px;margin:1.35rem 0 0;color:rgba(243,243,241,.84);font-size:clamp(1rem,1.3vw,1.13rem);line-height:1.78}.lvc-maghub-hero__actions{margin-top:1.8rem}
	.lvc-maghub-feature{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:0;background:var(--lvc-card);border:1px solid var(--lvc-border)}.lvc-maghub-feature__img{min-height:430px;background:var(--lvc-bg-deep) var(--feature-img,none) center/cover no-repeat}.lvc-maghub-feature__body{padding:clamp(1.6rem,4vw,3rem);display:flex;flex-direction:column;justify-content:center}.lvc-maghub-feature__date{color:var(--lvc-accent);font-size:.72rem;letter-spacing:.16em;text-transform:uppercase}.lvc-maghub-feature h2{margin:.65rem 0 .8rem;font-family:var(--lvc-font-display);font-size:clamp(1.8rem,3vw,3rem);font-weight:200;line-height:1.15;color:var(--lvc-text)}.lvc-maghub-feature p{color:var(--lvc-soft);line-height:1.75}.lvc-maghub-area-links{display:flex;flex-wrap:wrap;justify-content:center;gap:.65rem}.lvc-maghub-area-links a{display:inline-flex;border:1px solid var(--lvc-border);background:rgba(255,255,255,.025);color:var(--lvc-soft)!important;padding:.62rem .9rem;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}.lvc-maghub-area-links a:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important}.lvc-maghub-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.35rem}.lvc-maghub-card{display:flex;flex-direction:column;background:var(--lvc-card);border:1px solid var(--lvc-border);overflow:hidden;min-height:100%}.lvc-maghub-card:hover{border-color:var(--lvc-accent)}.lvc-maghub-card__img{aspect-ratio:16/10;background:var(--lvc-bg-deep);overflow:hidden}.lvc-maghub-card__img img{width:100%;height:100%;object-fit:cover;transition:transform .45s ease}.lvc-maghub-card:hover img{transform:scale(1.04)}.lvc-maghub-card__body{padding:1.15rem 1.2rem 1.35rem;display:flex;flex-direction:column;gap:.45rem}.lvc-maghub-card__date{color:var(--lvc-accent);font-size:.7rem;letter-spacing:.16em;text-transform:uppercase}.lvc-maghub-card__title{font-family:var(--lvc-font-display);font-size:1.25rem;font-weight:300;color:var(--lvc-text);line-height:1.25}.lvc-maghub-card__excerpt{color:var(--lvc-soft);font-size:.9rem;line-height:1.65}.lvc-maghub-card__cta{margin-top:.4rem;color:var(--lvc-accent);font-size:.85rem}.lvc-maghub-pagination{display:flex;justify-content:center;align-items:center;gap:.45rem;margin-top:2.5rem;flex-wrap:wrap}.lvc-maghub-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:42px;padding:.55rem .8rem;border:1px solid var(--lvc-border);color:var(--lvc-soft);background:rgba(255,255,255,.02)}.lvc-maghub-pagination .current{background:var(--lvc-accent);border-color:var(--lvc-accent);color:#fff}.lvc-maghub-final{background:var(--lvc-bg-deep);text-align:center}.lvc-maghub-final .lvc-maghub-copy{max-width:700px;margin:1rem auto 1.6rem}.lvc-maghub-final .lvc-maghub-btns{justify-content:center}
	@media(max-width:1000px){.lvc-maghub-feature{grid-template-columns:1fr}.lvc-maghub-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.lvc-maghub-wrap,.lvc-maghub-narrow{width:calc(100% - 2rem)}.lvc-maghub-hero{min-height:auto;padding:6rem 0 4rem}.lvc-maghub-hero h1{font-size:clamp(2.3rem,12vw,3.5rem)}.lvc-maghub-btns{display:grid;grid-template-columns:1fr}.lvc-maghub-grid{grid-template-columns:1fr}.lvc-maghub-feature__img{min-height:260px}}
</style>

<main class="lvc-maghub">
	<section class="lvc-maghub-hero" <?php echo $lvc_hero_img ? 'style="--maghub-hero-img:url(\'' . esc_url( $lvc_hero_img ) . '\')"' : ''; ?>>
		<div class="lvc-maghub-wrap">
			<span class="lvc-maghub-kicker">Riviera Maya Magazine</span>
			<h1>Villa planning guides for the Riviera Maya</h1>
			<p class="lvc-maghub-hero__sub">Area guides, villa planning advice, Cozumel-to-Tulum comparisons, and practical tips for choosing the right private stay across Tulum, Cozumel, Akumal, Playa del Carmen, and beyond.</p>
			<div class="lvc-maghub-btns lvc-maghub-hero__actions"><a class="lvc-maghub-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><a class="lvc-maghub-btn lvc-maghub-btn--ghost" href="<?php echo esc_url( $lvc_arch ); ?>">Browse Villas</a></div>
		</div>
	</section>

	<?php if ( $lvc_featured->have_posts() ) : $lvc_featured->the_post(); $lvc_feat_img = get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>
	<section class="lvc-maghub-section lvc-maghub-section--alt"><div class="lvc-maghub-wrap"><article class="lvc-maghub-feature"><a class="lvc-maghub-feature__img" href="<?php the_permalink(); ?>" style="<?php echo $lvc_feat_img ? '--feature-img:url(' . esc_url( $lvc_feat_img ) . ')' : ''; ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>"></a><div class="lvc-maghub-feature__body"><span class="lvc-maghub-feature__date"><?php echo esc_html( get_the_date() ); ?></span><h2><?php the_title(); ?></h2><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 34 ) ); ?></p><div class="lvc-maghub-btns"><a class="lvc-maghub-btn" href="<?php the_permalink(); ?>">Read Guide</a><a class="lvc-maghub-btn lvc-maghub-btn--ghost" href="<?php echo esc_url( $lvc_req ); ?>">Ask for Villa Help</a></div></div></article></div></section>
	<?php wp_reset_postdata(); endif; ?>

	<section class="lvc-maghub-section"><div class="lvc-maghub-wrap"><header class="lvc-maghub-head"><span class="lvc-maghub-kicker">Browse by Area</span><h2 class="lvc-maghub-title">Connect each guide to the <em>right villa area</em></h2><p class="lvc-maghub-copy">Use the magazine for research, but keep the booking path clear: area first, villa second, dates and service level before final choice.</p></header><nav class="lvc-maghub-area-links" aria-label="Magazine area links"><?php foreach ( $lvc_area_links as $label => $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?></nav></div></section>

	<section class="lvc-maghub-section lvc-maghub-section--alt"><div class="lvc-maghub-wrap"><header class="lvc-maghub-head"><span class="lvc-maghub-kicker">Latest Guides</span><h2 class="lvc-maghub-title">Villa planning <em>articles</em></h2></header><?php if ( $lvc_posts->have_posts() ) : ?><div class="lvc-maghub-grid"><?php while ( $lvc_posts->have_posts() ) : $lvc_posts->the_post(); if ( get_the_ID() === $lvc_featured_id && 1 === $lvc_paged ) { continue; } $img = get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?><a class="lvc-maghub-card" href="<?php the_permalink(); ?>"><?php if ( $img ) : ?><span class="lvc-maghub-card__img"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" decoding="async"></span><?php endif; ?><span class="lvc-maghub-card__body"><span class="lvc-maghub-card__date"><?php echo esc_html( get_the_date() ); ?></span><span class="lvc-maghub-card__title"><?php the_title(); ?></span><span class="lvc-maghub-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></span><span class="lvc-maghub-card__cta">Read guide &rarr;</span></span></a><?php endwhile; ?></div><nav class="lvc-maghub-pagination" aria-label="Magazine pagination"><?php echo wp_kses_post( paginate_links( array( 'total' => $lvc_posts->max_num_pages, 'current' => $lvc_paged, 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ) ); ?></nav><?php wp_reset_postdata(); else : ?><p class="lvc-empty">No articles yet.</p><?php endif; ?></div></section>

	<section class="lvc-maghub-section lvc-maghub-final"><div class="lvc-maghub-narrow"><span class="lvc-maghub-kicker">Villa Planning</span><h2 class="lvc-maghub-title">Finished researching? <em>Let us narrow the villas.</em></h2><p class="lvc-maghub-copy">Send your dates, group size, preferred area, budget range, and must-haves. We will help identify whether Cozumel, Tulum, Soliman Bay, Akumal, or Playa del Carmen is the stronger fit.</p><div class="lvc-maghub-btns"><a class="lvc-maghub-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><?php if ( $lvc_wa ) : ?><a class="lvc-maghub-btn lvc-maghub-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a><?php endif; ?></div></div></section>
</main>

<?php get_footer(); ?>
