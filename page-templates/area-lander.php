<?php
/**
 * Template Name: Area Lander
 * Residencias Reef Cozumel — reusable template for area landing pages.
 *
 * Ported from Los Cabos's area-lander.php (same "Area Pages" ACF field group,
 * same reusable-template-per-page pattern). Adapted: this site's `area`
 * taxonomy is hierarchical (Riviera Maya > Cozumel/Tulum/etc. > sub-areas)
 * and there is no `amenities` taxonomy here, so the quick-filter row only
 * offers `bedrooms` (no amenity-based chips).
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_slug = get_post_field( 'post_name', get_the_ID() );
if ( 'riviera-maya-villa-rentals' === $lvc_slug ) {
	$lvc_riviera_template = locate_template( 'page-templates/riviera-maya-villa-rentals.php' );
	if ( $lvc_riviera_template ) {
		include $lvc_riviera_template;
		return;
	}
}

get_header();

$lvc_area_map = lvc_area_lander_map();

$lvc_aslug = isset( $lvc_area_map[ $lvc_slug ] ) ? $lvc_area_map[ $lvc_slug ] : sanitize_title( str_replace( '-luxury-villas', '', $lvc_slug ) );
$lvc_term  = get_term_by( 'slug', $lvc_aslug, 'area' );
$lvc_cpt   = lvc_config( 'cpt', 'villas' );
$lvc_wa    = lvc_whatsapp_url();
$lvc_tid   = $lvc_term ? 'area_' . $lvc_term->term_id : 0;

$lvc_h1     = $lvc_tid ? lvc_field( 'h1_title', $lvc_tid, get_the_title() ) : get_the_title();
$lvc_intro  = $lvc_tid ? lvc_field( 'h2_area_paragraph', $lvc_tid ) : '';
$lvc_why_t  = $lvc_tid ? lvc_field( 'why_stay_here_title', $lvc_tid, 'Why Stay in ' . ( $lvc_term ? $lvc_term->name : '' ) . '?' ) : '';
$lvc_why    = $lvc_tid ? lvc_field( 'why_stay_info', $lvc_tid ) : '';
$lvc_high_t = $lvc_tid ? lvc_field( 'key_highlights', $lvc_tid ) : '';
$lvc_high   = $lvc_tid ? lvc_field( 'key_highlights_text', $lvc_tid ) : '';

$lvc_hero = $lvc_tid ? lvc_field( 'hero_image_url', $lvc_tid ) : '';
if ( ! $lvc_hero && $lvc_term ) {
	$hero_query = new WP_Query( array(
		'post_type'      => $lvc_cpt,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'tax_query'      => array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => $lvc_aslug ) ),
	) );
	if ( $hero_query->have_posts() ) {
		$lvc_hero = lvc_property_image( $hero_query->posts[0], 'full' );
	}
	wp_reset_postdata();
}

$lvc_paged = isset( $_GET['vp'] ) ? max( 1, (int) $_GET['vp'] ) : 1;
$lvc_tax_q = array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => $lvc_aslug ) );

if ( ! empty( $_GET['bedrooms'] ) ) {
	$lvc_tax_q[] = array(
		'taxonomy' => 'bedrooms',
		'field'    => 'slug',
		'terms'    => sanitize_title( wp_unslash( $_GET['bedrooms'] ) ),
	);
	$lvc_tax_q['relation'] = 'AND';
}

$lvc_q = new WP_Query( array(
	'post_type'      => $lvc_cpt,
	'post_status'    => 'publish',
	'posts_per_page' => 9,
	'paged'          => $lvc_paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'tax_query'      => $lvc_tax_q,
) );

if ( function_exists( 'lvc_schema_collection' ) ) {
	global $wp_query;
	$original_query = $wp_query;
	$wp_query       = $lvc_q;
	lvc_schema_collection();
	$wp_query = $original_query;
}

$lvc_area_label = $lvc_term ? $lvc_term->name : 'this area';
$lvc_faqs = array(
	array( 'q' => 'How do I book a villa in ' . $lvc_area_label . '?', 'a' => 'Send your dates, group size, and priorities. A specialist shortlists ' . $lvc_area_label . ' villas that fit and confirms availability, rates, and inclusions before you commit — booked direct with the villa team.' ),
	array( 'q' => 'Do you charge platform or booking fees?', 'a' => 'No. You book direct — there are no marketplace markups or platform fees.' ),
	array( 'q' => 'How far is ' . $lvc_area_label . ' from the airport?', 'a' => 'Most guests fly into Cancún International Airport. Transfer times vary by area and traffic; we confirm the realistic drive time for the specific villa you are considering.' ),
	array( 'q' => 'What is included — staff, chef, housekeeping?', 'a' => 'It varies by villa. Many include housekeeping and staff; chef service may be included or arranged on request. We confirm inclusions before booking.' ),
	array( 'q' => 'How quickly will you respond?', 'a' => 'We typically respond ' . lvc_config( 'response_time', 'within 24 hours' ) . '.' ),
);

if ( function_exists( 'lvc_jsonld' ) ) {
	$lvc_faq_qas = array();
	foreach ( $lvc_faqs as $lvc_faq_item ) {
		$lvc_faq_qas[] = array( '@type' => 'Question', 'name' => $lvc_faq_item['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $lvc_faq_item['a'] ) );
	}
	lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $lvc_faq_qas ) );
}

/* ── Hierarchy: breadcrumb + parent-aware "Explore More" grouping ──────────
 * `area` is hierarchical (Riviera Maya root > Cozumel/Tulum/etc. > sub-areas
 * like Soliman Bay). Root is treated as implicit (same as Los Cabos not
 * naming itself in its own breadcrumbs) — it never appears as a breadcrumb
 * crumb or an "ancestor", only as the site-wide "Villas" archive context. */
$lvc_root_term   = get_term_by( 'slug', 'riviera-maya', 'area' );
$lvc_root_id     = $lvc_root_term ? $lvc_root_term->term_id : 0;
$lvc_is_root     = $lvc_term && $lvc_root_id && $lvc_term->term_id === $lvc_root_id;

$lvc_ancestors   = array(); // root-to-immediate order, root itself excluded
$lvc_parent_term = null;    // immediate parent (excluding root), if any
if ( $lvc_term && $lvc_term->parent ) {
	foreach ( array_reverse( get_ancestors( $lvc_term->term_id, 'area' ) ) as $lvc_aid ) {
		if ( $lvc_aid === $lvc_root_id ) {
			continue;
		}
		$lvc_anc = get_term( $lvc_aid, 'area' );
		if ( $lvc_anc && ! is_wp_error( $lvc_anc ) ) {
			$lvc_ancestors[] = $lvc_anc;
		}
	}
	if ( $lvc_ancestors ) {
		$lvc_parent_term = end( $lvc_ancestors );
	}
}

// Direct children that have their own lander page (skips excluded/empty sub-areas).
$lvc_children = array();
if ( $lvc_term ) {
	$lvc_child_terms = get_terms( array( 'taxonomy' => 'area', 'parent' => $lvc_term->term_id, 'hide_empty' => false ) );
	if ( ! is_wp_error( $lvc_child_terms ) ) {
		foreach ( $lvc_child_terms as $lvc_ct ) {
			if ( in_array( $lvc_ct->slug, $lvc_area_map, true ) ) {
				$lvc_children[] = $lvc_ct;
			}
		}
	}
}

// Other top-level Riviera Maya destinations (skipped entirely on the root page — its
// own "Neighborhoods" list above already covers the same 6 destinations).
$lvc_top_level_areas = array();
if ( $lvc_root_id && ! $lvc_is_root ) {
	$lvc_top_terms = get_terms( array( 'taxonomy' => 'area', 'parent' => $lvc_root_id, 'hide_empty' => false ) );
	if ( ! is_wp_error( $lvc_top_terms ) ) {
		foreach ( $lvc_top_terms as $lvc_tt ) {
			if ( ! in_array( $lvc_tt->slug, $lvc_area_map, true ) ) {
				continue;
			}
			if ( $lvc_term && $lvc_tt->term_id === $lvc_term->term_id ) {
				continue; // exclude self
			}
			$lvc_top_level_areas[] = $lvc_tt;
		}
	}
}
?>

<style>
	.lvc-area-page{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}
	.lvc-area-page *{box-sizing:border-box}.lvc-area-wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-area-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}.lvc-area-section{padding:clamp(4rem,7vw,7rem) 0}.lvc-area-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}.lvc-area-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}.lvc-area-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.75rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}.lvc-area-title em{font-style:italic;color:var(--lvc-accent)}.lvc-area-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.2vw,1.08rem);font-weight:300;line-height:1.85}.lvc-area-copy p{margin:0 0 1rem}.lvc-area-head{text-align:center;max-width:900px;margin:0 auto clamp(2rem,4vw,3rem)}.lvc-area-head .lvc-area-copy{max-width:760px;margin:1rem auto 0}.lvc-area-btns{display:flex;flex-wrap:wrap;justify-content:center;gap:.85rem;margin-top:1.6rem}.lvc-area-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}.lvc-area-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}.lvc-area-btn:hover{background:var(--lvc-gold);border-color:var(--lvc-gold);color:#fff!important}.lvc-area-btn--ghost:hover{background:transparent!important;color:var(--lvc-gold)!important;border-color:var(--lvc-gold)}
	.lvc-area-hero{position:relative;min-height:min(720px,82vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--area-hero-img,none) center/cover no-repeat}.lvc-area-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 52%,rgba(10,12,15,.5)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.2) 52%,rgba(10,12,15,.6))}.lvc-area-hero__inner{max-width:880px}.lvc-area-hero .lvc-area-copy{max-width:720px;margin-top:1.2rem;color:rgba(243,243,241,.84)}.lvc-area-quick{display:flex;flex-wrap:wrap;gap:.65rem;margin-top:1.5rem}.lvc-area-chip{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.035);padding:.55rem .75rem;color:var(--lvc-soft);font-size:.75rem;text-transform:uppercase;letter-spacing:.08em}
	.lvc-area-intro{display:grid;grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr);gap:clamp(2rem,5vw,5rem);align-items:center}.lvc-area-panel{border-left:1px solid var(--lvc-border);padding-left:clamp(1.5rem,3vw,3rem)}.lvc-area-filter-form{display:flex;justify-content:center;align-items:end;gap:1rem;flex-wrap:wrap;margin:0 auto 2rem}.lvc-area-filter-form label{display:flex;flex-direction:column;gap:.35rem}.lvc-area-filter-form span{font-size:.68rem;letter-spacing:.14em;text-transform:uppercase;color:var(--lvc-muted)}.lvc-area-filter-form select{min-width:190px;background:var(--lvc-bg);border:1px solid var(--lvc-border);color:var(--lvc-text);padding:.72rem .85rem;font-size:1rem}.lvc-area-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.35rem}.lvc-area-count{text-align:center;color:var(--lvc-muted);font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;margin:0 0 1.5rem}.lvc-area-pagination{display:flex;justify-content:center;align-items:center;gap:.45rem;margin-top:2.5rem;flex-wrap:wrap}.lvc-area-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:42px;padding:.55rem .8rem;border:1px solid var(--lvc-border);color:var(--lvc-soft);background:rgba(255,255,255,.02)}.lvc-area-pagination .page-numbers.current{background:var(--lvc-accent);border-color:var(--lvc-accent);color:#fff}.lvc-area-pagination .page-numbers:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)}
	.lvc-area-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:clamp(2rem,5vw,5rem);align-items:start}.lvc-area-cardish{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:clamp(1.5rem,3vw,2.2rem)}.lvc-area-siblings{list-style:none;padding:0;margin:0;display:flex;justify-content:center;gap:.65rem;flex-wrap:wrap}.lvc-area-siblings a{display:inline-flex;border:1px solid var(--lvc-border);padding:.62rem .95rem;color:var(--lvc-soft)!important}.lvc-area-siblings a:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important}.lvc-area-cta{background:var(--lvc-bg-deep);text-align:center;border-top:1px solid rgba(255,255,255,.12)}.lvc-area-cta .lvc-area-copy{max-width:680px;margin:1rem auto 0}
	.lvc-area-breadcrumb{border-bottom:1px solid var(--lvc-border);padding:.85rem 0}.lvc-area-breadcrumb ol{display:flex;gap:.5rem;flex-wrap:wrap;list-style:none;margin:0;padding:0}.lvc-area-breadcrumb li{color:var(--lvc-muted);font-size:.72rem}.lvc-area-breadcrumb li a{color:var(--lvc-muted)!important}.lvc-area-breadcrumb li a:hover{color:var(--lvc-accent)!important}.lvc-area-breadcrumb li:not(:last-child):after{content:'\203A';margin-left:.5rem;color:rgba(255,255,255,.25)}
	@media(max-width:1100px){.lvc-area-intro,.lvc-area-two{grid-template-columns:1fr}.lvc-area-panel{border-left:0;padding-left:0}.lvc-area-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.lvc-area-wrap,.lvc-area-narrow{width:calc(100% - 2rem)}.lvc-area-hero{min-height:auto;padding:6rem 0 4rem}.lvc-area-title{font-size:clamp(2.25rem,11vw,3.35rem)}.lvc-area-grid{grid-template-columns:1fr}.lvc-area-filter-form{align-items:stretch}.lvc-area-filter-form label,.lvc-area-filter-form select,.lvc-area-filter-form button{width:100%}.lvc-area-btns{display:grid;grid-template-columns:1fr}}
</style>

<main class="lvc-area-page">

	<nav class="lvc-area-breadcrumb" aria-label="Breadcrumb"><div class="lvc-area-wrap"><ol>
		<li><a href="<?php echo esc_url( home_url() ); ?>">Home</a></li>
		<li><a href="<?php echo esc_url( lvc_archive_url() ); ?>">Villas</a></li>
		<?php foreach ( $lvc_ancestors as $lvc_anc ) : ?>
			<li><a href="<?php echo esc_url( lvc_area_lander_url( $lvc_anc->slug ) ); ?>"><?php echo esc_html( $lvc_anc->name ); ?></a></li>
		<?php endforeach; ?>
		<li aria-current="page"><?php echo esc_html( $lvc_term ? $lvc_term->name : $lvc_h1 ); ?></li>
	</ol></div></nav>

	<section class="lvc-area-hero" <?php echo $lvc_hero ? 'style="--area-hero-img:url(\'' . esc_url( $lvc_hero ) . '\')"' : ''; ?>>
		<div class="lvc-area-wrap lvc-area-hero__inner">
			<span class="lvc-area-kicker">Riviera Maya Area Guide</span>
			<h1 class="lvc-area-title"><?php echo wp_kses_post( $lvc_h1 ); ?></h1>
			<?php if ( $lvc_intro ) : ?><div class="lvc-area-copy"><?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $lvc_intro ), 46 ) ) ); ?></div><?php endif; ?>
			<div class="lvc-area-btns">
				<a class="lvc-area-btn" href="#villas">View Villas in <?php echo esc_html( $lvc_term ? $lvc_term->name : 'This Area' ); ?></a>
				<a class="lvc-area-btn lvc-area-btn--ghost" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request a Match</a>
			</div>
			<div class="lvc-area-quick" aria-label="Area highlights">
				<span class="lvc-area-chip">Private Villas</span>
				<span class="lvc-area-chip">Concierge Planning</span>
				<span class="lvc-area-chip">Direct Booking Guidance</span>
			</div>
		</div>
	</section>

	<?php if ( $lvc_intro ) : ?>
	<section class="lvc-area-section">
		<div class="lvc-area-wrap lvc-area-intro">
			<div>
				<span class="lvc-area-kicker">About <?php echo esc_html( $lvc_term ? $lvc_term->name : 'This Area' ); ?></span>
				<h2 class="lvc-area-title">Is <?php echo esc_html( $lvc_term ? $lvc_term->name : 'this area' ); ?> right for your group?</h2>
			</div>
			<div class="lvc-area-panel lvc-area-copy"><?php echo wp_kses_post( wpautop( $lvc_intro ) ); ?></div>
		</div>
	</section>
	<?php endif; ?>

	<section class="lvc-area-section lvc-area-section--alt" id="villas">
		<div class="lvc-area-wrap">
			<header class="lvc-area-head">
				<span class="lvc-area-kicker">Villa Collection</span>
				<h2 class="lvc-area-title">Luxury villas in <em><?php echo esc_html( $lvc_term ? $lvc_term->name : 'this area' ); ?></em></h2>
				<p class="lvc-area-copy">Browse available villas in this area, or narrow the collection by bedroom count.</p>
			</header>

			<form class="lvc-area-filter-form" method="get" action="#villas">
				<?php
				$lvc_bedroom_terms = get_terms( array( 'taxonomy' => 'bedrooms', 'hide_empty' => true ) );
				if ( ! is_wp_error( $lvc_bedroom_terms ) && $lvc_bedroom_terms ) :
					$lvc_current_bedrooms = isset( $_GET['bedrooms'] ) ? sanitize_title( wp_unslash( $_GET['bedrooms'] ) ) : '';
					?>
					<label><span>Bedrooms</span>
						<select name="bedrooms">
							<option value="">Any</option>
							<?php foreach ( $lvc_bedroom_terms as $term ) : ?><option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $lvc_current_bedrooms, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<button type="submit" class="lvc-area-btn">Filter Villas</button>
			</form>

			<?php if ( $lvc_q->have_posts() ) : ?>
				<p class="lvc-area-count"><?php echo esc_html( sprintf( '%d %s', (int) $lvc_q->found_posts, lvc_config( 'cpt_plural', 'Villas' ) ) ); ?></p>
				<div class="lvc-area-grid">
					<?php while ( $lvc_q->have_posts() ) : $lvc_q->the_post(); get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); endwhile; ?>
				</div>
				<?php if ( $lvc_q->max_num_pages > 1 ) : ?>
				<nav class="lvc-area-pagination" aria-label="Villa pagination">
					<?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'vp', '%#%' ) . '#villas', 'format' => '', 'current' => $lvc_paged, 'total' => (int) $lvc_q->max_num_pages, 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ) ); ?>
				</nav>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p class="lvc-empty">No villas match this filter yet. <a href="<?php echo esc_url( remove_query_arg( array( 'bedrooms', 'vp' ) ) ); ?>#villas">Clear filters</a> or <a href="<?php echo esc_url( lvc_archive_url() ); ?>">browse all villas</a>.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $lvc_why || $lvc_high ) : ?>
	<section class="lvc-area-section">
		<div class="lvc-area-wrap lvc-area-two">
			<?php if ( $lvc_why ) : ?><article class="lvc-area-cardish"><span class="lvc-area-kicker">Why Stay Here</span><h2 class="lvc-area-title"><?php echo esc_html( $lvc_why_t ?: ( 'Why Stay in ' . ( $lvc_term ? $lvc_term->name : 'This Area' ) ) ); ?></h2><div class="lvc-area-copy"><?php echo wp_kses_post( wpautop( $lvc_why ) ); ?></div></article><?php endif; ?>
			<?php if ( $lvc_high ) : ?><article class="lvc-area-cardish"><span class="lvc-area-kicker">Area Highlights</span><h2 class="lvc-area-title"><?php echo esc_html( $lvc_high_t ?: 'Experiences Nearby' ); ?></h2><div class="lvc-area-copy"><?php echo wp_kses_post( wpautop( $lvc_high ) ); ?></div></article><?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="lvc-area-section lvc-area-section--alt">
		<div class="lvc-area-narrow">
			<header class="lvc-area-head"><span class="lvc-area-kicker">FAQ</span><h2 class="lvc-area-title">Questions about staying in <em><?php echo esc_html( $lvc_area_label ); ?></em></h2></header>
			<div class="lvc-faq">
				<?php foreach ( $lvc_faqs as $lvc_faq_item ) : ?>
					<details class="lvc-faq__item"><summary class="lvc-faq__q"><?php echo esc_html( $lvc_faq_item['q'] ); ?></summary><p class="lvc-faq__a"><?php echo esc_html( $lvc_faq_item['a'] ); ?></p></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( $lvc_children ) : ?>
	<section class="lvc-area-section lvc-area-section--alt">
		<div class="lvc-area-wrap">
			<header class="lvc-area-head"><span class="lvc-area-kicker"><?php echo $lvc_is_root ? 'Destinations' : 'Neighborhoods'; ?></span><h2 class="lvc-area-title"><?php echo $lvc_is_root ? 'Explore areas across the' : 'Explore ' . esc_html( $lvc_area_label ) . '&rsquo;s'; ?> <em><?php echo $lvc_is_root ? 'Riviera Maya' : 'neighborhoods'; ?></em></h2></header>
			<ul class="lvc-area-siblings">
				<?php foreach ( $lvc_children as $lvc_child ) : ?>
					<li><a href="<?php echo esc_url( lvc_area_lander_url( $lvc_child->slug ) ); ?>"><?php echo esc_html( $lvc_child->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $lvc_top_level_areas ) : ?>
	<section class="lvc-area-section<?php echo $lvc_children ? '' : ' lvc-area-section--alt'; ?>">
		<div class="lvc-area-wrap">
			<header class="lvc-area-head"><span class="lvc-area-kicker">Explore More</span><h2 class="lvc-area-title">Other Riviera Maya <em>destinations</em></h2></header>
			<ul class="lvc-area-siblings">
				<?php foreach ( $lvc_top_level_areas as $lvc_tt ) : ?>
					<li><a href="<?php echo esc_url( lvc_area_lander_url( $lvc_tt->slug ) ); ?>"><?php echo esc_html( $lvc_tt->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<section class="lvc-area-section lvc-area-cta">
		<div class="lvc-area-narrow">
			<span class="lvc-area-kicker">Plan Your Stay</span>
			<h2 class="lvc-area-title">Need help choosing a villa in <em><?php echo esc_html( $lvc_term ? $lvc_term->name : 'the Riviera Maya' ); ?></em>?</h2>
			<p class="lvc-area-copy">Tell us your dates, group size, bedroom needs, and service priorities. We will help shortlist the villas that actually fit.</p>
			<div class="lvc-area-btns">
				<a class="lvc-area-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request a Villa Match</a>
				<?php if ( $lvc_wa ) : ?><a class="lvc-area-btn lvc-area-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a><?php endif; ?>
			</div>
		</div>
	</section>

</main>
<?php get_footer(); ?>