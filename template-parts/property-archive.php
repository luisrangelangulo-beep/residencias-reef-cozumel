<?php
/**
 * Property archive — filter bar + responsive card grid + pagination.
 * Routed here for the configured CPT archive by inc/template-router.php.
 * Filters are read from GET, sanitized, applied to the main query via pre_get_posts
 * (see inc/template-router.php). This template only renders. No styling.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_cpt    = lvc_config( 'cpt', 'villa' );
$lvc_plural = lvc_config( 'cpt_plural', 'Villas' );

// Build filter options from taxonomies that exist.
$lvc_filter_taxes = array_intersect( array( 'area', 'bedrooms', 'beach_access', 'property_type' ), array_keys( (array) lvc_config( 'taxonomies', array() ) ) );

if ( function_exists( 'lvc_schema_collection' ) ) {
	lvc_schema_collection();
}
?>
<main class="lvc-archive">
	<section class="lvc-archive__head lvc-section">
		<p class="lvc-eyebrow"><?php echo esc_html( lvc_brand() ); ?></p>
		<h1 class="lvc-sec-title"><?php echo esc_html( $lvc_plural ); ?></h1>
	</section>

	<nav class="lvc-archive-areas" aria-label="Browse by area">
		<span class="lvc-archive-areas__label">Browse by area:</span>
		<a href="<?php echo esc_url( home_url( '/cozumel/' ) ); ?>">Cozumel</a>
		<a href="<?php echo esc_url( home_url( '/tulum-villa-rentals/' ) ); ?>">Tulum</a>
		<a href="<?php echo esc_url( home_url( '/playa-del-carmen/' ) ); ?>">Playa del Carmen</a>
		<a href="<?php echo esc_url( home_url( '/soliman-bay/' ) ); ?>">Soliman Bay</a>
		<a href="<?php echo esc_url( home_url( '/akumal/' ) ); ?>">Akumal</a>
		<a href="<?php echo esc_url( home_url( '/puerto-aventuras/' ) ); ?>">Puerto Aventuras</a>
	</nav>

	<form class="lvc-filter" method="get" data-lvc-filter>
		<?php foreach ( $lvc_filter_taxes as $tax ) :
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
			if ( is_wp_error( $terms ) || ! $terms ) { continue; }
			$current = isset( $_GET[ $tax ] ) ? sanitize_title( wp_unslash( $_GET[ $tax ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$obj = get_taxonomy( $tax );
			?>
			<label class="lvc-filter__group">
				<span><?php echo esc_html( $obj ? $obj->labels->singular_name : ucfirst( $tax ) ); ?></span>
				<select class="lvc-filter__select" name="<?php echo esc_attr( $tax ); ?>">
					<option value="">Any</option>
					<?php foreach ( $terms as $t ) : ?>
						<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $current, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endforeach; ?>
		<button type="submit" class="lvc-btn lvc-filter__submit">Filter</button>
	</form>

	<section class="lvc-section">
		<?php if ( have_posts() ) : ?>
			<p class="lvc-archive__count"><?php echo esc_html( sprintf( '%d %s', (int) $GLOBALS['wp_query']->found_posts, $lvc_plural ) ); ?></p>
			<div class="lvc-grid lvc-grid--3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); ?>
				<?php endwhile; ?>
			</div>
			<nav class="lvc-pagination" aria-label="Pagination">
				<?php echo paginate_links( array( 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ); ?>
			</nav>
		<?php else : ?>
			<p class="lvc-empty">No <?php echo esc_html( strtolower( $lvc_plural ) ); ?> match those filters. <a href="<?php echo esc_url( lvc_archive_url() ); ?>">Clear filters</a>.</p>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
