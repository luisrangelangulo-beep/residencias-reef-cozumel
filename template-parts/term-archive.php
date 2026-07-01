<?php
/**
 * Generic taxonomy term archive — one template for ALL property taxonomies
 * (area, destination, collection, bedrooms, beach_access, …). Replaces the
 * ~90% duplicate per-taxonomy templates the brand sites carried.
 *
 * Renders: term header (name + description + optional hero from term meta),
 * the term's property grid (main query), optional sibling/child term band for
 * hierarchical-style taxonomies, pagination, and CollectionPage schema.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_term = get_queried_object();
$lvc_tax  = $lvc_term->taxonomy;
$lvc_obj  = get_taxonomy( $lvc_tax );

// Term meta (ACF) for richer headers, with graceful fallback.
$lvc_intro = lvc_field( $lvc_tax . '_intro', $lvc_tax . '_' . $lvc_term->term_id, term_description() );
$lvc_hero  = lvc_field( $lvc_tax . '_hero_image_url', $lvc_tax . '_' . $lvc_term->term_id );

if ( function_exists( 'lvc_schema_collection' ) ) {
	lvc_schema_collection();
}
?>
<main class="lvc-term">
	<section class="lvc-term__head lvc-section" <?php echo $lvc_hero ? 'style="--lvc-term-hero:url(\'' . esc_url( $lvc_hero ) . '\')"' : ''; ?>>
		<p class="lvc-eyebrow"><?php echo esc_html( $lvc_obj ? $lvc_obj->labels->singular_name : '' ); ?></p>
		<h1 class="lvc-sec-title"><?php echo esc_html( $lvc_term->name ); ?></h1>
		<?php if ( $lvc_intro ) : ?>
			<div class="lvc-term__intro"><?php echo wp_kses_post( wpautop( $lvc_intro ) ); ?></div>
		<?php endif; ?>
	</section>

	<section class="lvc-section">
		<?php if ( have_posts() ) : ?>
			<p class="lvc-archive__count"><?php echo esc_html( sprintf( '%d %s', (int) $GLOBALS['wp_query']->found_posts, lvc_config( 'cpt_plural', 'Villas' ) ) ); ?></p>
			<div class="lvc-grid lvc-grid--3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); ?>
				<?php endwhile; ?>
			</div>
			<nav class="lvc-pagination" aria-label="Pagination">
				<?php echo paginate_links( array( 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ); ?>
			</nav>
		<?php else : ?>
			<p class="lvc-empty">No <?php echo esc_html( strtolower( lvc_config( 'cpt_plural', 'Villas' ) ) ); ?> here yet.
				<a href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse all</a>.</p>
		<?php endif; ?>
	</section>

	<?php
	// Sibling terms band (helps area/destination cross-linking + internal SEO).
	$lvc_siblings = get_terms( array( 'taxonomy' => $lvc_tax, 'hide_empty' => true, 'exclude' => array( $lvc_term->term_id ), 'number' => 8, 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( ! is_wp_error( $lvc_siblings ) && $lvc_siblings ) : ?>
		<section class="lvc-section lvc-term__siblings">
			<h2 class="lvc-sec-title">More <?php echo esc_html( $lvc_obj ? $lvc_obj->labels->name : '' ); ?></h2>
			<ul class="lvc-term__sibling-list">
				<?php foreach ( $lvc_siblings as $s ) : $u = get_term_link( $s ); if ( is_wp_error( $u ) ) { continue; } ?>
					<li><a href="<?php echo esc_url( $u ); ?>"><?php echo esc_html( $s->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
