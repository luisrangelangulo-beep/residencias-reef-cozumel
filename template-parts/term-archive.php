<?php
/**
 * Generic taxonomy term archive — enhanced for the `area` taxonomy.
 *
 * Area terms now behave like conversion-focused landing pages: strong hero,
 * local context, child/sibling area links, villa grid, qualification copy,
 * FAQ schema, and final inquiry CTA. Other property taxonomies still get a
 * clean collection archive using the same layout system.
 *
 * @package ResidenciasReefCozumel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_term = get_queried_object();
$lvc_tax  = $lvc_term instanceof WP_Term ? $lvc_term->taxonomy : '';
$lvc_obj  = $lvc_tax ? get_taxonomy( $lvc_tax ) : null;
$lvc_cpt  = lvc_config( 'cpt', 'villas' );
$lvc_req  = lvc_page_url( 'request' );
$lvc_arch = lvc_archive_url();
$lvc_wa   = lvc_whatsapp_url();

$lvc_is_area = ( 'area' === $lvc_tax );
$lvc_tid     = $lvc_term instanceof WP_Term ? $lvc_tax . '_' . $lvc_term->term_id : '';

if ( ! function_exists( 'lvc_term_archive_area_image' ) ) {
	function lvc_term_archive_area_image( $slug ) {
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

if ( ! function_exists( 'lvc_term_archive_term_url' ) ) {
	function lvc_term_archive_term_url( $term ) {
		$link = get_term_link( $term );
		return is_wp_error( $link ) ? lvc_archive_url() : $link;
	}
}

$lvc_fallback_intro = term_description();
$lvc_intro          = $lvc_tid ? lvc_field( $lvc_tax . '_intro', $lvc_tid, '' ) : '';
if ( ! $lvc_intro && $lvc_tid ) {
	$lvc_intro = lvc_field( 'h2_area_paragraph', $lvc_tid, '' );
}
if ( ! $lvc_intro ) {
	$lvc_intro = $lvc_fallback_intro;
}

$lvc_hero = $lvc_tid ? lvc_field( $lvc_tax . '_hero_image_url', $lvc_tid, '' ) : '';
if ( ! $lvc_hero && $lvc_tid ) {
	$lvc_hero = lvc_field( 'hero_image_url', $lvc_tid, '' );
}
if ( ! $lvc_hero && $lvc_is_area && $lvc_term instanceof WP_Term ) {
	$lvc_hero = lvc_term_archive_area_image( $lvc_term->slug );
}

$lvc_h1 = $lvc_term instanceof WP_Term ? $lvc_term->name : get_the_archive_title();
if ( $lvc_tid ) {
	$lvc_h1 = lvc_field( 'h1_title', $lvc_tid, $lvc_h1 );
}

$lvc_area_label = $lvc_term instanceof WP_Term ? $lvc_term->name : 'this area';

$lvc_root_term = $lvc_is_area ? get_term_by( 'slug', 'riviera-maya', 'area' ) : null;
$lvc_root_id   = $lvc_root_term ? (int) $lvc_root_term->term_id : 0;
$lvc_is_root   = $lvc_root_id && $lvc_term instanceof WP_Term && (int) $lvc_term->term_id === $lvc_root_id;

$lvc_ancestors = array();
if ( $lvc_is_area && $lvc_term instanceof WP_Term && $lvc_term->parent ) {
	foreach ( array_reverse( get_ancestors( $lvc_term->term_id, 'area' ) ) as $lvc_aid ) {
		if ( $lvc_root_id && (int) $lvc_aid === $lvc_root_id ) {
			continue;
		}
		$lvc_ancestor = get_term( $lvc_aid, 'area' );
		if ( $lvc_ancestor && ! is_wp_error( $lvc_ancestor ) ) {
			$lvc_ancestors[] = $lvc_ancestor;
		}
	}
}

$lvc_children = array();
if ( $lvc_is_area && $lvc_term instanceof WP_Term ) {
	$lvc_child_terms = get_terms( array( 'taxonomy' => 'area', 'parent' => $lvc_term->term_id, 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( ! is_wp_error( $lvc_child_terms ) && $lvc_child_terms ) {
		$lvc_children = $lvc_child_terms;
	}
}

$lvc_siblings = array();
if ( $lvc_term instanceof WP_Term ) {
	$lvc_siblings = get_terms( array(
		'taxonomy'   => $lvc_tax,
		'hide_empty' => true,
		'exclude'    => array( $lvc_term->term_id ),
		'parent'     => $lvc_is_area ? (int) $lvc_term->parent : '',
		'number'     => 8,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );
	if ( is_wp_error( $lvc_siblings ) ) {
		$lvc_siblings = array();
	}
}

$lvc_faqs = array(
	array( 'q' => 'Is ' . $lvc_area_label . ' a good area for a private villa stay?', 'a' => $lvc_area_label . ' can be a strong fit depending on your group size, preferred beach access, service expectations, and budget. We help compare the area against nearby options before you book.' ),
	array( 'q' => 'Can you help choose between Cozumel condos and Riviera Maya villas?', 'a' => 'Yes. Cozumel condos work well for simple beachfront stays, couples, divers, and smaller groups. Private villas are usually better for larger families, chef service, private pools, celebrations, and higher-service trips.' ),
	array( 'q' => 'Do villas in ' . $lvc_area_label . ' include chef or staff?', 'a' => 'It depends on the villa. Some include housekeeping or staff, while chef service may be included or arranged separately. We confirm inclusions, staffing, and extra costs before you commit.' ),
	array( 'q' => 'How do I request villa options in ' . $lvc_area_label . '?', 'a' => 'Send your travel dates, group size, bedroom needs, preferred location, and service priorities. We will shortlist realistic villas and alternatives based on availability and fit.' ),
);

if ( function_exists( 'lvc_jsonld' ) ) {
	$lvc_faq_qas = array();
	foreach ( $lvc_faqs as $lvc_faq_item ) {
		$lvc_faq_qas[] = array( '@type' => 'Question', 'name' => $lvc_faq_item['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $lvc_faq_item['a'] ) );
	}
	lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $lvc_faq_qas ) );
}

if ( function_exists( 'lvc_schema_collection' ) ) {
	lvc_schema_collection();
}
?>

<style>
	.lvc-tax-page{background:var(--lvc-bg);color:var(--lvc-soft);font-family:var(--lvc-font-body)}
	.lvc-tax-page *{box-sizing:border-box}
	.lvc-tax-wrap{width:min(1480px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}
	.lvc-tax-narrow{width:min(980px,calc(100% - clamp(2rem,6vw,6rem)));margin:0 auto}
	.lvc-tax-section{padding:clamp(4rem,7vw,7rem) 0}
	.lvc-tax-section--alt{background:var(--lvc-bg-alt);border-top:1px solid var(--lvc-border);border-bottom:1px solid var(--lvc-border)}
	.lvc-tax-kicker{display:block;margin:0 0 .85rem;color:var(--lvc-accent);font-size:.68rem;font-weight:400;letter-spacing:.2em;text-transform:uppercase}
	.lvc-tax-title{margin:0;font-family:var(--lvc-font-display);font-size:clamp(2rem,4vw,3.7rem);font-weight:200;line-height:1.12;color:var(--lvc-text)}
	.lvc-tax-title em{font-style:italic;color:var(--lvc-accent)}
	.lvc-tax-copy{color:var(--lvc-soft);font-size:clamp(.98rem,1.2vw,1.08rem);font-weight:300;line-height:1.82}
	.lvc-tax-copy p{margin:0 0 1rem}
	.lvc-tax-head{text-align:center;max-width:900px;margin:0 auto clamp(2rem,4vw,3rem)}
	.lvc-tax-head .lvc-tax-copy{max-width:760px;margin:1rem auto 0}
	.lvc-tax-btns{display:flex;flex-wrap:wrap;gap:.85rem;align-items:center;margin-top:1.7rem}
	.lvc-tax-btns--center{justify-content:center}
	.lvc-tax-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:.85rem 1.45rem;border:1px solid var(--lvc-accent);background:var(--lvc-accent);color:#fff!important;font-size:.86rem;font-weight:500;border-radius:var(--lvc-radius)}
	.lvc-tax-btn--ghost{background:transparent!important;border-color:rgba(255,255,255,.28);color:var(--lvc-text)!important}
	.lvc-tax-breadcrumb{border-bottom:1px solid var(--lvc-border);padding:.85rem 0;background:rgba(10,12,15,.72)}
	.lvc-tax-breadcrumb ol{display:flex;gap:.5rem;flex-wrap:wrap;list-style:none;margin:0;padding:0}
	.lvc-tax-breadcrumb li{color:var(--lvc-muted);font-size:.72rem}
	.lvc-tax-breadcrumb a{color:var(--lvc-muted)!important}.lvc-tax-breadcrumb a:hover{color:var(--lvc-accent)!important}
	.lvc-tax-breadcrumb li:not(:last-child):after{content:'\203A';margin-left:.5rem;color:rgba(255,255,255,.25)}
	.lvc-tax-hero{position:relative;min-height:min(720px,82vh);display:flex;align-items:center;isolation:isolate;padding:clamp(7rem,10vw,10rem) 0;background:var(--lvc-bg-deep) var(--tax-hero-img,none) center/cover no-repeat}
	.lvc-tax-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(10,12,15,.95),rgba(10,12,15,.72) 52%,rgba(10,12,15,.5)),linear-gradient(0deg,rgba(10,12,15,.9),rgba(10,12,15,.22) 52%,rgba(10,12,15,.62))}
	.lvc-tax-hero__inner{max-width:930px}
	.lvc-tax-hero .lvc-tax-copy{max-width:760px;margin-top:1.25rem;color:rgba(243,243,241,.86)}
	.lvc-tax-chips{display:flex;flex-wrap:wrap;gap:.65rem;margin-top:1.45rem}
	.lvc-tax-chip{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.035);padding:.55rem .75rem;color:var(--lvc-soft);font-size:.75rem;text-transform:uppercase;letter-spacing:.08em}
	.lvc-tax-intro{display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);gap:clamp(2rem,5vw,5rem);align-items:center}
	.lvc-tax-panel{border-left:1px solid var(--lvc-border);padding-left:clamp(1.5rem,3vw,3rem)}
	.lvc-tax-panel ul{list-style:none;margin:1.35rem 0 0;padding:0;display:grid;gap:.8rem}
	.lvc-tax-panel li{position:relative;padding-left:1.25rem;color:var(--lvc-soft);line-height:1.65}
	.lvc-tax-panel li:before{content:'✓';position:absolute;left:0;color:var(--lvc-accent)}
	.lvc-tax-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.35rem}
	.lvc-tax-count{text-align:center;color:var(--lvc-muted);font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;margin:0 0 1.5rem}
	.lvc-tax-pagination{display:flex;justify-content:center;align-items:center;gap:.45rem;margin-top:2.5rem;flex-wrap:wrap}
	.lvc-tax-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:42px;padding:.55rem .8rem;border:1px solid var(--lvc-border);color:var(--lvc-soft);background:rgba(255,255,255,.02)}
	.lvc-tax-pagination .page-numbers.current{background:var(--lvc-accent);border-color:var(--lvc-accent);color:#fff}
	.lvc-tax-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
	.lvc-tax-area-card{position:relative;min-height:285px;display:flex;align-items:flex-end;padding:1.3rem;border:1px solid var(--lvc-border);background:var(--lvc-card) var(--area-img,none) center/cover no-repeat;overflow:hidden}
	.lvc-tax-area-card:before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,12,15,.12),rgba(10,12,15,.92))}
	.lvc-tax-area-card__body{position:relative;z-index:1}
	.lvc-tax-area-card h3{margin:0;font-family:var(--lvc-font-display);font-weight:300;font-size:1.35rem;color:var(--lvc-text)}
	.lvc-tax-area-card p{margin:.55rem 0 0;color:var(--lvc-soft);font-size:.86rem;line-height:1.55}
	.lvc-tax-area-card span{display:block;margin-top:.85rem;color:var(--lvc-accent);font-size:.82rem}
	.lvc-tax-upgrade{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
	.lvc-tax-upgrade-card{background:rgba(255,255,255,.025);border:1px solid var(--lvc-border);padding:1.25rem;min-height:165px}
	.lvc-tax-upgrade-card span{display:block;color:var(--lvc-accent);font-size:.68rem;letter-spacing:.16em;text-transform:uppercase;margin-bottom:.6rem}
	.lvc-tax-upgrade-card h3{margin:0 0 .5rem;font-family:var(--lvc-font-display);font-size:1.18rem;font-weight:300;color:var(--lvc-text)}
	.lvc-tax-upgrade-card p{margin:0;color:var(--lvc-soft);font-size:.9rem;line-height:1.65}
	.lvc-tax-compare{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
	.lvc-tax-compare-card{border:1px solid var(--lvc-border);background:var(--lvc-card);padding:1.5rem}
	.lvc-tax-compare-card h3{margin:0 0 1rem;font-family:var(--lvc-font-display);font-weight:300;color:var(--lvc-text)}
	.lvc-tax-compare-card ul{margin:0;padding:0;list-style:none;display:grid;gap:.7rem}
	.lvc-tax-compare-card li{position:relative;padding-left:1.1rem;color:var(--lvc-soft);line-height:1.55}
	.lvc-tax-compare-card li:before{content:'•';position:absolute;left:0;color:var(--lvc-accent)}
	.lvc-tax-siblings{list-style:none;padding:0;margin:0;display:flex;justify-content:center;gap:.65rem;flex-wrap:wrap}
	.lvc-tax-siblings a{display:inline-flex;border:1px solid var(--lvc-border);padding:.62rem .95rem;color:var(--lvc-soft)!important}.lvc-tax-siblings a:hover{border-color:var(--lvc-accent);color:var(--lvc-accent)!important}
	.lvc-tax-faq{display:grid;gap:.75rem}.lvc-tax-faq details{background:var(--lvc-card);border:1px solid var(--lvc-border);padding:1rem 1.15rem}.lvc-tax-faq summary{cursor:pointer;color:var(--lvc-text);font-family:var(--lvc-font-display);font-weight:300}.lvc-tax-faq p{margin:.75rem 0 0;color:var(--lvc-soft);line-height:1.7}
	.lvc-tax-cta{background:var(--lvc-bg-deep);text-align:center;border-top:1px solid rgba(255,255,255,.12)}.lvc-tax-cta .lvc-tax-copy{max-width:680px;margin:1rem auto 0}
	@media(max-width:1100px){.lvc-tax-intro,.lvc-tax-compare{grid-template-columns:1fr}.lvc-tax-panel{border-left:0;padding-left:0}.lvc-tax-grid,.lvc-tax-card-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.lvc-tax-upgrade{grid-template-columns:repeat(2,minmax(0,1fr))}}
	@media(max-width:720px){.lvc-tax-wrap,.lvc-tax-narrow{width:calc(100% - 2rem)}.lvc-tax-hero{min-height:auto;padding:6rem 0 4rem}.lvc-tax-title{font-size:clamp(2.25rem,11vw,3.35rem)}.lvc-tax-grid,.lvc-tax-card-grid,.lvc-tax-upgrade{grid-template-columns:1fr}.lvc-tax-btns{display:grid;grid-template-columns:1fr}.lvc-tax-btn{width:100%}}
</style>

<main class="lvc-tax-page">

	<nav class="lvc-tax-breadcrumb" aria-label="Breadcrumb"><div class="lvc-tax-wrap"><ol>
		<li><a href="<?php echo esc_url( home_url() ); ?>">Home</a></li>
		<li><a href="<?php echo esc_url( $lvc_arch ); ?>">Villas</a></li>
		<?php foreach ( $lvc_ancestors as $lvc_ancestor ) : ?>
			<li><a href="<?php echo esc_url( lvc_term_archive_term_url( $lvc_ancestor ) ); ?>"><?php echo esc_html( $lvc_ancestor->name ); ?></a></li>
		<?php endforeach; ?>
		<li aria-current="page"><?php echo esc_html( $lvc_area_label ); ?></li>
	</ol></div></nav>

	<section class="lvc-tax-hero" <?php echo $lvc_hero ? 'style="--tax-hero-img:url(\'' . esc_url( $lvc_hero ) . '\')"' : ''; ?>>
		<div class="lvc-tax-wrap lvc-tax-hero__inner">
			<span class="lvc-tax-kicker"><?php echo esc_html( $lvc_is_area ? 'Riviera Maya Area Guide' : ( $lvc_obj ? $lvc_obj->labels->singular_name : 'Collection' ) ); ?></span>
			<h1 class="lvc-tax-title"><?php echo wp_kses_post( $lvc_h1 ); ?></h1>
			<?php if ( $lvc_intro ) : ?><div class="lvc-tax-copy"><?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $lvc_intro ), 48 ) ) ); ?></div><?php endif; ?>
			<div class="lvc-tax-btns">
				<a class="lvc-tax-btn" href="#villas">View Villas in <?php echo esc_html( $lvc_area_label ); ?></a>
				<a class="lvc-tax-btn lvc-tax-btn--ghost" href="<?php echo esc_url( $lvc_req ); ?>">Request a Match</a>
			</div>
			<div class="lvc-tax-chips" aria-label="Area highlights">
				<span class="lvc-tax-chip">Private Villas</span>
				<span class="lvc-tax-chip">Direct Booking Guidance</span>
				<span class="lvc-tax-chip">Concierge Planning</span>
			</div>
		</div>
	</section>

	<section class="lvc-tax-section lvc-tax-section--alt">
		<div class="lvc-tax-wrap lvc-tax-intro">
			<div><span class="lvc-tax-kicker">Area Strategy</span><h2 class="lvc-tax-title">Is <em><?php echo esc_html( $lvc_area_label ); ?></em> the right fit?</h2></div>
			<div class="lvc-tax-panel lvc-tax-copy">
				<?php if ( $lvc_intro ) : ?><?php echo wp_kses_post( wpautop( $lvc_intro ) ); ?><?php else : ?><p>Every Riviera Maya area works differently. The right choice depends on group size, beach access, service level, arrival logistics, privacy, and whether your stay is a simple vacation or a higher-value private villa trip.</p><?php endif; ?>
				<ul><li>Compare the area against Cozumel, Tulum, Playa del Carmen, Akumal, and Puerto Aventuras.</li><li>Shortlist villas by bedrooms, location, pool, beach access, chef service, staff, and group priorities.</li><li>Use the inquiry form when dates and exact fit matter more than endless browsing.</li></ul>
			</div>
		</div>
	</section>

	<?php if ( $lvc_children ) : ?>
	<section class="lvc-tax-section">
		<div class="lvc-tax-wrap">
			<header class="lvc-tax-head"><span class="lvc-tax-kicker"><?php echo esc_html( $lvc_is_root ? 'Destinations' : 'Neighborhoods' ); ?></span><h2 class="lvc-tax-title">Explore <?php echo esc_html( $lvc_area_label ); ?> <em>villa areas</em></h2><p class="lvc-tax-copy">Use these area links to compare the micro-locations that matter for beach access, restaurants, privacy, service level, and overall trip fit.</p></header>
			<div class="lvc-tax-card-grid">
				<?php foreach ( $lvc_children as $lvc_child ) : $lvc_child_img = lvc_term_archive_area_image( $lvc_child->slug ); ?>
					<a class="lvc-tax-area-card" href="<?php echo esc_url( lvc_term_archive_term_url( $lvc_child ) ); ?>" style="<?php echo $lvc_child_img ? '--area-img:url(' . esc_url( $lvc_child_img ) . ')' : ''; ?>"><div class="lvc-tax-area-card__body"><h3><?php echo esc_html( $lvc_child->name ); ?></h3><p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( term_description( $lvc_child->term_id, 'area' ) ), 24, '...' ) ?: 'Compare villas, location style, and group fit in this area.' ); ?></p><span>Explore <?php echo esc_html( $lvc_child->name ); ?> villas &rarr;</span></div></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $lvc_is_area ) : ?>
	<section class="lvc-tax-section<?php echo $lvc_children ? ' lvc-tax-section--alt' : ''; ?>">
		<div class="lvc-tax-wrap">
			<header class="lvc-tax-head"><span class="lvc-tax-kicker">When to Upgrade</span><h2 class="lvc-tax-title">When a villa in <em><?php echo esc_html( $lvc_area_label ); ?></em> makes sense</h2><p class="lvc-tax-copy">This page should qualify the traveler, not just display inventory. These are the common reasons guests move from a simple condo search into a higher-value private villa stay.</p></header>
			<div class="lvc-tax-upgrade">
				<article class="lvc-tax-upgrade-card"><span>Group Size</span><h3>More space than a condo</h3><p>Families and groups often need multiple bedrooms, private outdoor areas, and better shared living spaces.</p></article>
				<article class="lvc-tax-upgrade-card"><span>Service</span><h3>Chef, staff, and planning</h3><p>Private villas are stronger when meals, groceries, transfers, tours, and arrival details need coordination.</p></article>
				<article class="lvc-tax-upgrade-card"><span>Occasion</span><h3>Celebrations and retreats</h3><p>Milestone trips need a property that supports privacy, hosting, dining, and a better guest experience.</p></article>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="lvc-tax-section lvc-tax-section--alt" id="villas">
		<div class="lvc-tax-wrap">
			<header class="lvc-tax-head"><span class="lvc-tax-kicker">Villa Collection</span><h2 class="lvc-tax-title">Luxury villas in <em><?php echo esc_html( $lvc_area_label ); ?></em></h2><p class="lvc-tax-copy">Browse the current collection, then send your dates if you want help confirming availability, service level, and realistic alternatives.</p></header>
			<?php if ( have_posts() ) : ?>
				<p class="lvc-tax-count"><?php echo esc_html( sprintf( '%d %s', (int) $GLOBALS['wp_query']->found_posts, lvc_config( 'cpt_plural', 'Villas' ) ) ); ?></p>
				<div class="lvc-tax-grid">
					<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); endwhile; ?>
				</div>
				<nav class="lvc-tax-pagination" aria-label="Pagination"><?php echo wp_kses_post( paginate_links( array( 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ) ); ?></nav>
			<?php else : ?>
				<p class="lvc-empty">No <?php echo esc_html( strtolower( lvc_config( 'cpt_plural', 'Villas' ) ) ); ?> here yet. <a href="<?php echo esc_url( $lvc_arch ); ?>">Browse all villas</a> or <a href="<?php echo esc_url( $lvc_req ); ?>">request a match</a>.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $lvc_is_area ) : ?>
	<section class="lvc-tax-section">
		<div class="lvc-tax-wrap">
			<header class="lvc-tax-head"><span class="lvc-tax-kicker">Cozumel or Villa Upgrade</span><h2 class="lvc-tax-title">Cozumel condo or <em><?php echo esc_html( $lvc_area_label ); ?> villa?</em></h2><p class="lvc-tax-copy">This keeps the Residencias Reef hook useful while guiding qualified groups toward private villa options when the trip demands more space, privacy, and service.</p></header>
			<div class="lvc-tax-compare"><article class="lvc-tax-compare-card"><h3>Choose a Cozumel condo if...</h3><ul><li>You are a couple, small family, or diving-focused group.</li><li>You want a simple beachfront base at a lower nightly rate.</li><li>You do not need a private chef, staff, or a large villa layout.</li></ul></article><article class="lvc-tax-compare-card"><h3>Choose a private villa if...</h3><ul><li>You need more bedrooms, privacy, and stronger shared spaces.</li><li>You want chef service, staff, groceries, transfers, or celebration planning.</li><li>You are comparing Tulum, Soliman Bay, Akumal, Playa del Carmen, Puerto Aventuras, or <?php echo esc_html( $lvc_area_label ); ?>.</li></ul></article></div>
		</div>
	</section>
	<?php endif; ?>

	<section class="lvc-tax-section lvc-tax-section--alt">
		<div class="lvc-tax-narrow">
			<header class="lvc-tax-head"><span class="lvc-tax-kicker">FAQ</span><h2 class="lvc-tax-title">Questions about <em><?php echo esc_html( $lvc_area_label ); ?></em></h2></header>
			<div class="lvc-tax-faq">
				<?php foreach ( $lvc_faqs as $lvc_faq_item ) : ?>
					<details><summary><?php echo esc_html( $lvc_faq_item['q'] ); ?></summary><p><?php echo esc_html( $lvc_faq_item['a'] ); ?></p></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( $lvc_siblings ) : ?>
	<section class="lvc-tax-section">
		<div class="lvc-tax-wrap">
			<header class="lvc-tax-head"><span class="lvc-tax-kicker">Explore More</span><h2 class="lvc-tax-title">Compare nearby <em><?php echo esc_html( $lvc_obj ? $lvc_obj->labels->name : 'areas' ); ?></em></h2></header>
			<ul class="lvc-tax-siblings">
				<?php foreach ( $lvc_siblings as $lvc_sibling ) : ?><li><a href="<?php echo esc_url( lvc_term_archive_term_url( $lvc_sibling ) ); ?>"><?php echo esc_html( $lvc_sibling->name ); ?></a></li><?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<section class="lvc-tax-section lvc-tax-cta">
		<div class="lvc-tax-narrow">
			<span class="lvc-tax-kicker">Plan Your Stay</span>
			<h2 class="lvc-tax-title">Need help choosing a villa in <em><?php echo esc_html( $lvc_area_label ); ?></em>?</h2>
			<p class="lvc-tax-copy">Tell us your dates, group size, bedroom needs, preferred area, and service priorities. We will help identify whether this area, a nearby Riviera Maya villa, or a Cozumel condo makes the most sense.</p>
			<div class="lvc-tax-btns lvc-tax-btns--center"><a class="lvc-tax-btn" href="<?php echo esc_url( $lvc_req ); ?>">Request Villa Matches</a><?php if ( $lvc_wa ) : ?><a class="lvc-tax-btn lvc-tax-btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a><?php endif; ?></div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
