<?php
/**
 * Magazine / blog single article.
 *
 * Designed to connect editorial traffic back to villa areas, direct inquiries,
 * and related property inventory.
 *
 * @package ResidenciasReefCozumel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'lvc_article_area_links' ) ) {
	function lvc_article_area_links( $post_id ) {
		$links = array();
		if ( ! taxonomy_exists( 'area' ) ) {
			return $links;
		}
		$terms = get_the_terms( $post_id, 'area' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$url = function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( $term->slug ) : get_term_link( $term );
				if ( ! is_wp_error( $url ) ) {
					$links[ $term->name ] = $url;
				}
			}
		}
		if ( ! $links && function_exists( 'lvc_area_lander_url' ) ) {
			$links = array(
				'Tulum Villas'     => lvc_area_lander_url( 'tulum' ),
				'Soliman Bay'      => lvc_area_lander_url( 'soliman-bay' ),
				'Cozumel'          => lvc_area_lander_url( 'cozumel' ),
				'Akumal'           => lvc_area_lander_url( 'akumal' ),
			);
		}
		return $links;
	}
}

while ( have_posts() ) :
	the_post();
	$lvc_id    = get_the_ID();
	$lvc_img   = get_the_post_thumbnail_url( $lvc_id, 'full' );
	$lvc_req   = lvc_page_url( 'request' );
	$lvc_arch  = lvc_archive_url();
	$lvc_wa    = lvc_whatsapp_url();
	$lvc_areas = lvc_article_area_links( $lvc_id );

	if ( ! $lvc_img ) {
		$hero_q = new WP_Query( array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );
		if ( $hero_q->have_posts() ) {
			$lvc_img = lvc_property_image( $hero_q->posts[0], 'full' );
		}
		wp_reset_postdata();
	}

	if ( function_exists( 'lvc_schema_article' ) ) {
		lvc_schema_article( $lvc_id );
	}
	?>

	<style>
		.lvc-article-modern{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}.lvc-article-modern *{box-sizing:border-box}.lvc-art-wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-art-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-art-section{padding:clamp(4rem,7vw,7rem) 0}.lvc-art-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lvc-art-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lvc-art-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.7rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lvc-art-title em{font-style:italic;color:var(--lvc-accent)}.lvc-art-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.2vw,1.08rem);font-weight:300;line-height:1.82}.lvc-art-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center}.lvc-art-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lvc-art-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
		.lvc-art-hero{position:relative;min-height:min(720px,82vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--art-hero-img,none) center/cover no-repeat}.lvc-art-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 48%,rgba(10,12,15,.48)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.25) 52%,rgba(10,12,15,.64))}.lvc-art-hero h1{max-width:980px;margin:0;font-family:var(--lvc-font-display);font-size:clamp(2.45rem,5vw,4.8rem);font-weight:200;line-height:1.08;color:var(--lvc-text)}.lvc-art-meta{display:flex;flex-wrap:wrap;gap:.65rem;margin:1.25rem 0 0}.lvc-art-meta span{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.035);padding:.55rem .75rem;color:var(--lvc-soft);font-size:.75rem;text-transform:uppercase;letter-spacing:.08em}.lvc-art-hero__actions{margin-top:1.8rem}
		.lvc-art-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(310px,380px);gap:clamp(2rem,5vw,4rem);align-items:start}.lvc-art-body{min-width:0;color:var(--lvc-soft);font-size:1.06rem;line-height:1.86}.lvc-art-body > *:first-child{margin-top:0}.lvc-art-body h2,.lvc-art-body h3{font-family:var(--lvc-font-display);font-weight:300;color:var(--lvc-text);line-height:1.18;margin:2.2rem 0 .85rem}.lvc-art-body h2{font-size:clamp(1.7rem,3vw,2.5rem)}.lvc-art-body h3{font-size:clamp(1.35rem,2vw,1.8rem)}.lvc-art-body p{margin:0 0 1.1rem}.lvc-art-body a{text-decoration:underline;text-underline-offset:3px}.lvc-art-body ul,.lvc-art-body ol{padding-left:1.35rem;margin:1rem 0 1.2rem}.lvc-art-body img{border:1px solid var(--lvc-border);margin:1.5rem 0}.lvc-art-side{position:sticky;top:92px;display:grid;gap:1rem}.lvc-art-side-card{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1.35rem}.lvc-art-side-card h2,.lvc-art-side-card h3{margin:.25rem 0 .65rem;font-family:var(--lvc-font-display);font-weight:300;color:var(--lvc-text)}.lvc-art-side-card p{color:var(--lvc-soft);font-size:.92rem;line-height:1.7}.lvc-art-links{list-style:none;margin:.85rem 0 0;padding:0;display:grid;gap:.55rem}.lvc-art-links a{display:block;border-bottom:1px solid var(--lvc-border);padding:.5rem 0;color:var(--lvc-soft)!important}.lvc-art-links a:hover{color:var(--lvc-accent)!important}.lvc-art-inline-cta{margin:2rem 0;padding:1.35rem;background:var(--lvc-card);border:1px solid var(--lvc-border)}.lvc-art-inline-cta p{color:var(--lvc-soft);margin:.4rem 0 1rem}.lvc-art-related .lvc-grid{margin-top:2rem}.lvc-art-final{background:var(--lvc-bg-deep);text-align:center}.lvc-art-final .lvc-art-copy{max-width:700px;margin:1rem auto 1.6rem}.lvc-art-final .lvc-art-btns{justify-content:center}
		@media(max-width:1000px){.lvc-art-layout{grid-template-columns:1fr}.lvc-art-side{position:static}}@media(max-width:720px){.lvc-art-wrap,.lvc-art-narrow{width:calc(100% - 2rem)}.lvc-art-hero{min-height:auto;padding:6rem 0 4rem}.lvc-art-hero h1{font-size:clamp(2.25rem,11vw,3.35rem)}.lvc-art-btns{display:grid;grid-template-columns:1fr}}
	</style>

	<main class="lvc-article-modern">
		<header class="lvc-art-hero" <?php echo $lvc_img ? 'style="--art-hero-img:url(\'' . esc_url( $lvc_img ) . '\')"' : ''; ?>>
			<div class="lvc-art-wrap">
				<span class="lvc-art-kicker">Riviera Maya Magazine</span>
				<h1><?php the_title(); ?></h1>
				<div class="lvc-art-meta"><span><?php echo esc_html( get_the_date() ); ?></span><span>Villa Planning Guide</span><?php if ( $lvc_areas ) : ?><span><?php echo esc_html( implode( ' / ', array_slice( array_keys( $lvc_areas ), 0, 2 ) ) ); ?></span><?php endif; ?></div>
				<div class="lvc-art-btns lvc-art-hero__actions"><a class="lvc-art-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><a class="lvc-art-btn lvc-art-btn--ghost" href="<?php echo esc_url( $lvc_arch ); ?>">Browse Villas</a></div>
			</div>
		</header>

		<section class="lvc-art-section"><div class="lvc-art-wrap lvc-art-layout"><article class="lvc-art-body">
			<div class="lvc-art-inline-cta"><span class="lvc-art-kicker">Planning Shortcut</span><h2 class="lvc-art-title" style="font-size:clamp(1.45rem,2.4vw,2.1rem)">Research is useful. Villa matching saves time.</h2><p>Send your dates, group size, preferred area, and must-haves. We will help compare villas, Cozumel condo options, and nearby alternatives.</p><div class="lvc-art-btns"><a class="lvc-art-btn" href="<?php echo esc_url( $lvc_req ); ?>">Ask for Villa Help</a></div></div>
			<?php the_content(); ?>
		</article><aside class="lvc-art-side" aria-label="Article villa planning sidebar"><div class="lvc-art-side-card"><span class="lvc-art-kicker">Villa Match</span><h2>Need help choosing?</h2><p>Share your dates, group size, preferred area, and service needs. We will narrow the villas that actually fit.</p><a class="lvc-art-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a></div><?php if ( $lvc_areas ) : ?><div class="lvc-art-side-card"><span class="lvc-art-kicker">Related Areas</span><ul class="lvc-art-links"><?php foreach ( $lvc_areas as $label => $url ) : ?><li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li><?php endforeach; ?></ul></div><?php endif; ?><div class="lvc-art-side-card"><span class="lvc-art-kicker">Explore</span><ul class="lvc-art-links"><li><a href="<?php echo esc_url( $lvc_arch ); ?>">Browse all villas</a></li><li><a href="<?php echo esc_url( function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'tulum' ) : home_url( '/tulum-villa-rentals/' ) ); ?>">Tulum villas</a></li><li><a href="<?php echo esc_url( function_exists( 'lvc_area_lander_url' ) ? lvc_area_lander_url( 'cozumel' ) : home_url( '/cozumel/' ) ); ?>">Cozumel stays</a></li></ul></div></aside></div></section>

		<?php $lvc_related = function_exists( 'lvc_related_properties_for_post' ) ? lvc_related_properties_for_post( $lvc_id, 3 ) : array(); if ( $lvc_related ) : ?>
			<section class="lvc-art-section lvc-art-related lvc-art-section--alt"><div class="lvc-art-wrap"><span class="lvc-art-kicker">Villa Ideas</span><h2 class="lvc-art-title"><?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?> related to this guide</h2><div class="lvc-grid lvc-grid--3"><?php foreach ( $lvc_related as $rid ) { get_template_part( 'template-parts/card-property', null, array( 'id' => $rid ) ); } ?></div></div></section>
		<?php endif; ?>

		<section class="lvc-art-section lvc-art-final"><div class="lvc-art-narrow"><span class="lvc-art-kicker">Start Planning</span><h2 class="lvc-art-title">Want the realistic shortlist?</h2><p class="lvc-art-copy">Tell us your dates, group size, preferred area, and service priorities. We will help identify the villas or Cozumel condo options that make sense.</p><div class="lvc-art-btns"><a class="lvc-art-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><?php if ( $lvc_wa ) : ?><a class="lvc-art-btn lvc-art-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a><?php endif; ?></div></div></section>
	</main>
	<?php
endwhile;

get_footer();
