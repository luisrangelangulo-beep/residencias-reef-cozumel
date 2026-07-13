<?php
/**
 * Signature Collection band — a curated, image-led villa showcase placed high on
 * the destination + all-villas pages so the product (villa imagery) leads,
 * before the SEO/qualification copy.
 *
 * Source order (never renders a villa without a working image):
 *   1. `featured` villas in this area  (editorial curation via the sheet-sync
 *      `featured` field — the intended control surface)
 *   2. recent villas in this area that HAVE a resolvable image  (graceful
 *      fallback so the band is never empty while `featured` is unset / while the
 *      image backfill is in progress)
 *   3. the same two passes site-wide, when an area has no image-bearing villas
 * If nothing resolves, the section renders nothing (no empty band).
 *
 * $args:
 *   'area_term'  WP_Term|null  — limit to this area (null = site-wide / all villas)
 *   'title'      string        — section headline (has a sensible default)
 *   'view_all'   string        — optional URL for a "See all" ghost link
 *   'view_label' string        — label for that link
 *   'limit'      int           — cards to show (default 3)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_sig_term    = isset( $args['area_term'] ) && $args['area_term'] instanceof WP_Term ? $args['area_term'] : null;
$lvc_sig_limit   = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 3;
$lvc_sig_title   = isset( $args['title'] ) ? (string) $args['title'] : 'Villas we would book first';
$lvc_sig_va_url  = isset( $args['view_all'] ) ? (string) $args['view_all'] : '';
$lvc_sig_va_lbl  = isset( $args['view_label'] ) ? (string) $args['view_label'] : 'See all villas';
$lvc_sig_cpt     = (string) lvc_config( 'cpt', 'villa' );

/**
 * Collect candidate IDs (featured first, then recent), optionally scoped to an
 * area term, then keep only those with a resolvable image, in order, up to limit.
 */
$lvc_sig_pick = function ( $term ) use ( $lvc_sig_cpt, $lvc_sig_limit ) {
	$tax_query = array();
	if ( $term instanceof WP_Term ) {
		$tax_query[] = array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => $term->term_id );
	}
	$base = array(
		'post_type'      => $lvc_sig_cpt,
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => $lvc_sig_limit * 5,
		'no_found_rows'  => true,
	);
	if ( $tax_query ) {
		$base['tax_query'] = $tax_query;
	}

	$featured = get_posts( array_merge( $base, array(
		'meta_query' => array( array( 'key' => 'featured', 'value' => array( '', '0', 'false', 'no' ), 'compare' => 'NOT IN' ) ),
	) ) );
	$recent = get_posts( array_merge( $base, array( 'orderby' => 'date', 'order' => 'DESC' ) ) );

	$ordered = array_values( array_unique( array_merge( (array) $featured, (array) $recent ) ) );

	$picked = array();
	foreach ( $ordered as $id ) {
		if ( lvc_property_image( $id, 'large' ) ) {
			$picked[] = $id;
		}
		if ( count( $picked ) >= $lvc_sig_limit ) {
			break;
		}
	}
	return $picked;
};

$lvc_sig_ids = $lvc_sig_pick( $lvc_sig_term );
if ( count( $lvc_sig_ids ) < $lvc_sig_limit && $lvc_sig_term ) {
	// Area had too few image-bearing villas — top up from the site-wide pool.
	$fill = $lvc_sig_pick( null );
	$lvc_sig_ids = array_slice( array_values( array_unique( array_merge( $lvc_sig_ids, $fill ) ) ), 0, $lvc_sig_limit );
}

if ( ! $lvc_sig_ids ) {
	return; // Nothing with a real image — render no band rather than an empty one.
}

// Print the band's CSS once per request, even if the partial is included twice.
static $lvc_sig_css_done = false;
if ( ! $lvc_sig_css_done ) :
	$lvc_sig_css_done = true;
	?>
	<style>
	.lvc-sig{padding:clamp(3.5rem,6vw,6rem) 0;background:var(--lvc-bg-deep,#0a0c0f)}
	.lvc-sig__wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}
	.lvc-sig__head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin:0 0 clamp(1.5rem,3vw,2.5rem)}
	.lvc-sig__kicker{display:block;margin:0 0 .7rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}
	.lvc-sig__title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(1.75rem,3vw,2.75rem);font-weight:200;line-height:1.14;color:var(--lvc-text)}
	.lvc-sig__title em{font-style:italic;color:var(--lvc-accent)}
	.lvc-sig__all{display:inline-flex;align-items:center;gap:.4rem;min-height:44px;padding:.7rem 1.3rem;border:1px solid rgba(255,255,255,.28);color:var(--lvc-text);font-size:.82rem;border-radius:var(--lvc-radius);white-space:nowrap}
	.lvc-sig__all:hover{border-color:var(--lvc-gold,var(--lvc-accent));color:var(--lvc-gold,var(--lvc-accent))}
	.lvc-sig__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:clamp(1rem,1.6vw,1.5rem)}
	.lvc-sig__card{position:relative;display:flex;flex-direction:column;border:1px solid var(--lvc-border);border-radius:var(--lvc-radius);overflow:hidden;background:var(--lvc-bg-alt,#111);text-decoration:none}
	.lvc-sig__media{display:block;position:relative;aspect-ratio:3/2;overflow:hidden}
	.lvc-sig__media img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
	.lvc-sig__card:hover .lvc-sig__media img{transform:scale(1.04)}
	.lvc-sig__flag{position:absolute;top:.7rem;left:.7rem;z-index:2;font-size:.6rem;letter-spacing:.14em;text-transform:uppercase;color:var(--lvc-text);background:rgba(10,12,15,.6);border:1px solid rgba(255,255,255,.18);padding:.28rem .55rem;border-radius:20px}
	.lvc-sig__area{position:absolute;bottom:.7rem;left:.7rem;z-index:2;font-size:.68rem;letter-spacing:.06em;color:#f3f3f1;background:rgba(10,12,15,.55);padding:.3rem .6rem;border-radius:20px}
	.lvc-sig__body{display:block;padding:1rem 1.1rem 1.15rem}
	.lvc-sig__name{display:block;margin:0 0 .35rem;font-family:var(--lvc-font-display);font-size:1.12rem;font-weight:300;line-height:1.25;color:var(--lvc-text)}
	.lvc-sig__meta{display:block;margin:0 0 .7rem;color:var(--lvc-muted);font-size:.8rem;letter-spacing:.03em}
	.lvc-sig__link{display:inline-flex;align-items:center;gap:.35rem;color:var(--lvc-accent);font-size:.82rem}
	.lvc-sig__card:hover .lvc-sig__link{color:var(--lvc-gold,var(--lvc-accent))}
	@media(max-width:1000px){.lvc-sig__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
	@media(max-width:640px){.lvc-sig__grid{grid-template-columns:1fr}}
	</style>
	<?php
endif;
?>
<section class="lvc-sig" aria-label="Signature villa collection">
	<div class="lvc-sig__wrap">
		<header class="lvc-sig__head">
			<div>
				<span class="lvc-sig__kicker">Signature Collection</span>
				<h2 class="lvc-sig__title"><?php echo wp_kses_post( $lvc_sig_title ); ?></h2>
			</div>
			<?php if ( $lvc_sig_va_url ) : ?>
				<a class="lvc-sig__all" href="<?php echo esc_url( $lvc_sig_va_url ); ?>"><?php echo esc_html( $lvc_sig_va_lbl ); ?> &rarr;</a>
			<?php endif; ?>
		</header>

		<div class="lvc-sig__grid">
			<?php
			foreach ( $lvc_sig_ids as $lvc_sig_id ) :
				$lvc_sig_img  = lvc_property_image( $lvc_sig_id, 'large' );
				$lvc_sig_name = lvc_field( 'h1_title', $lvc_sig_id, get_the_title( $lvc_sig_id ) );
				$lvc_sig_beds = lvc_field( 'bed_count', $lvc_sig_id );
				$lvc_sig_bath = lvc_field( 'bath_count', $lvc_sig_id );
				$lvc_sig_gst  = lvc_field( 'guests_max', $lvc_sig_id );
				$lvc_sig_af   = get_post_meta( $lvc_sig_id, 'featured', true );
				$lvc_sig_isf  = $lvc_sig_af && ! in_array( strtolower( (string) $lvc_sig_af ), array( '', '0', 'false', 'no' ), true );
				$lvc_sig_aobj = lvc_property_area_term( $lvc_sig_id );
				$lvc_sig_specs = array_filter( array(
					$lvc_sig_beds ? $lvc_sig_beds . ' BR' : '',
					$lvc_sig_bath ? $lvc_sig_bath . ' BA' : '',
					$lvc_sig_gst ? 'Sleeps ' . $lvc_sig_gst : '',
				) );
				?>
				<a class="lvc-sig__card" href="<?php echo esc_url( get_permalink( $lvc_sig_id ) ); ?>" aria-label="<?php echo esc_attr( $lvc_sig_name ); ?>">
					<span class="lvc-sig__media">
						<?php if ( $lvc_sig_isf ) : ?><span class="lvc-sig__flag">Signature</span><?php endif; ?>
						<?php if ( $lvc_sig_aobj ) : ?><span class="lvc-sig__area"><?php echo esc_html( $lvc_sig_aobj->name ); ?></span><?php endif; ?>
						<img src="<?php echo esc_url( $lvc_sig_img ); ?>" alt="<?php echo esc_attr( $lvc_sig_name ); ?>" loading="lazy" decoding="async">
					</span>
					<span class="lvc-sig__body">
						<span class="lvc-sig__name"><?php echo esc_html( $lvc_sig_name ); ?></span>
						<?php if ( $lvc_sig_specs ) : ?><span class="lvc-sig__meta"><?php echo esc_html( implode( ' · ', $lvc_sig_specs ) ); ?></span><?php endif; ?>
						<span class="lvc-sig__link">View villa &rarr;</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
