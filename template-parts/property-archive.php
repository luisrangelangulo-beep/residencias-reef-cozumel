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

$lvc_archive_hero_img = lvc_site_content( 'archive_hero_image' );
if ( ! $lvc_archive_hero_img ) {
	$lvc_archive_hero_img = lvc_field( 'archive_hero_image_url', 'option' );
}
$lvc_archive_title = lvc_site_content( 'archive_title', 'Cozumel Vacation Rentals & Riviera Maya Villas' );
$lvc_archive_intro = lvc_site_content( 'archive_intro', 'Compare beachfront Cozumel condos with private villas across Tulum, Playa del Carmen, Akumal, Soliman Bay, and Puerto Aventuras.' );

if ( function_exists( 'lvc_schema_collection' ) ) {
	lvc_schema_collection();
}
?>
<main class="lvc-archive">
	<section class="lvc-archive-hero" <?php echo $lvc_archive_hero_img ? 'style="--archive-hero-img:url(\'' . esc_url( $lvc_archive_hero_img ) . '\')"' : ''; ?>>
		<div class="lvc-archive-hero__inner">
			<p class="lvc-eyebrow"><?php echo esc_html( lvc_brand() ); ?></p>
			<h1 class="lvc-hero__title"><?php echo esc_html( $lvc_archive_title ); ?></h1>
			<p class="lvc-hero__sub"><?php echo esc_html( $lvc_archive_intro ); ?></p>
		</div>
	</section>

	<nav class="lvc-archive-areas lvc-wide-wrap" aria-label="Browse by area">
		<span class="lvc-archive-areas__label">Browse by area:</span>
		<a href="<?php echo esc_url( home_url( '/cozumel/' ) ); ?>">Cozumel</a>
		<a href="<?php echo esc_url( home_url( '/tulum-villa-rentals/' ) ); ?>">Tulum</a>
		<a href="<?php echo esc_url( home_url( '/playa-del-carmen/' ) ); ?>">Playa del Carmen</a>
		<a href="<?php echo esc_url( home_url( '/soliman-bay/' ) ); ?>">Soliman Bay</a>
		<a href="<?php echo esc_url( home_url( '/akumal/' ) ); ?>">Akumal</a>
		<a href="<?php echo esc_url( home_url( '/puerto-aventuras/' ) ); ?>">Puerto Aventuras</a>
	</nav>

	<form class="lvc-filter lvc-wide-wrap" method="get" data-lvc-filter>
		<?php foreach ( array( 'arrival', 'departure', 'guests', 'beds' ) as $lvc_passthrough ) : ?>
			<?php if ( isset( $_GET[ $lvc_passthrough ] ) && '' !== $_GET[ $lvc_passthrough ] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<input type="hidden" name="<?php echo esc_attr( $lvc_passthrough ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET[ $lvc_passthrough ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
			<?php endif; ?>
		<?php endforeach; ?>
		<?php foreach ( $lvc_filter_taxes as $tax ) :
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
			if ( is_wp_error( $terms ) || ! $terms ) { continue; }
			if ( 'bedrooms' === $tax ) {
				$terms = lvc_sort_bedroom_terms( $terms );
			}
			$filter_param = lvc_filter_param_for_taxonomy( $tax );
			$current      = get_query_var( $filter_param );
			$current      = $current ? sanitize_title( $current ) : ( isset( $_GET[ $filter_param ] ) ? sanitize_title( wp_unslash( $_GET[ $filter_param ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$obj = get_taxonomy( $tax );
			?>
			<label class="lvc-filter__group">
				<span><?php echo esc_html( $obj ? $obj->labels->singular_name : ucfirst( $tax ) ); ?></span>
				<select class="lvc-filter__select" name="<?php echo esc_attr( $filter_param ); ?>">
					<option value="">Any</option>
					<?php foreach ( $terms as $t ) : ?>
						<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $current, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endforeach; ?>
		<button type="submit" class="lvc-btn lvc-filter__submit">Filter</button>
	</form>

	<?php
	$lvc_arrival   = isset( $_GET['arrival'] ) ? sanitize_text_field( wp_unslash( $_GET['arrival'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lvc_departure = isset( $_GET['departure'] ) ? sanitize_text_field( wp_unslash( $_GET['departure'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lvc_guests    = isset( $_GET['guests'] ) ? absint( $_GET['guests'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lvc_beds      = isset( $_GET['beds'] ) ? absint( $_GET['beds'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lvc_date_ok   = static function ( $date ) {
		$parsed = DateTime::createFromFormat( 'Y-m-d', $date );
		return $parsed && $parsed->format( 'Y-m-d' ) === $date ? $date : '';
	};
	$lvc_arrival   = $lvc_date_ok( $lvc_arrival );
	$lvc_departure = $lvc_date_ok( $lvc_departure );
	if ( $lvc_arrival || $lvc_departure || $lvc_guests || $lvc_beds ) :
		$lvc_request_url = add_query_arg(
			array_filter(
				array(
					'arrival'   => $lvc_arrival,
					'departure' => $lvc_departure,
					'guests'    => $lvc_guests,
					'bedrooms'  => $lvc_beds,
				)
			),
			lvc_page_url( 'request' )
		);
		?>
		<div class="lvc-fchip lvc-wide-wrap" aria-label="Your villa search">
			<?php if ( $lvc_arrival ) : ?><span class="lvc-fchip__item">Arrival <?php echo esc_html( date_i18n( 'j M Y', strtotime( $lvc_arrival ) ) ); ?></span><?php endif; ?>
			<?php if ( $lvc_departure ) : ?><span class="lvc-fchip__item">Departure <?php echo esc_html( date_i18n( 'j M Y', strtotime( $lvc_departure ) ) ); ?></span><?php endif; ?>
			<?php if ( $lvc_guests ) : ?><span class="lvc-fchip__item"><?php echo esc_html( $lvc_guests ); ?>+ guests</span><?php endif; ?>
			<?php if ( $lvc_beds ) : ?><span class="lvc-fchip__item"><?php echo esc_html( $lvc_beds ); ?>+ bedrooms</span><?php endif; ?>
			<?php if ( $lvc_arrival || $lvc_departure ) : ?><span class="lvc-fchip__note">Dates are advisory; availability is confirmed personally.</span><?php endif; ?>
			<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( $lvc_request_url ); ?>">Request This Match</a>
		</div>
	<?php endif; ?>

	<section class="lvc-wide-section">
		<div class="lvc-wide-wrap">
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
		</div>
	</section>
</main>
<?php
get_footer();
