<?php
/**
 * Editorial/sidebar block for pages, magazine, and single posts.
 *
 * Keeps non-property pages connected to villas, areas, and inquiry.
 * Ported from Los Cabos's editorial-sidebar.php: area list now reads from
 * the shared lvc_area_lander_map() instead of a duplicated hardcoded list;
 * "Popular Searches" uses the `bedrooms` taxonomy (RRC has no `amenities`
 * taxonomy, so there's no beachfront/large-groups/etc. to link to).
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_sidebar_areas = array();
foreach ( lvc_area_lander_map() as $page_slug => $area_slug ) {
	$term = get_term_by( 'slug', $area_slug, 'area' );
	if ( $term ) {
		$lvc_sidebar_areas[ $term->name ] = '/' . $page_slug . '/';
	}
}

$lvc_sidebar_bedrooms = array();
$lvc_bedroom_terms    = get_terms( array( 'taxonomy' => 'bedrooms', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 5 ) );
if ( ! is_wp_error( $lvc_bedroom_terms ) ) {
	foreach ( $lvc_bedroom_terms as $term ) {
		$lvc_sidebar_bedrooms[ $term->name ] = get_term_link( $term );
	}
}

$lvc_sidebar_villas = new WP_Query( array(
	'post_type'      => lvc_config( 'cpt', 'villas' ),
	'post_status'    => 'publish',
	'posts_per_page' => 2,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );
?>
<aside class="lvc-editorial-sidebar" aria-label="Villa planning sidebar">
	<div class="lvc-side-card lvc-side-card--cta">
		<span class="lvc-side-kicker">Villa Match</span>
		<h2>Need help choosing?</h2>
		<p>Share your dates, group size, preferred area, and service needs. We will help narrow the villas that fit.</p>
		<a class="lvc-side-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request Villa Matches</a>
	</div>

	<?php if ( $lvc_sidebar_areas ) : ?>
	<div class="lvc-side-card">
		<span class="lvc-side-kicker">Explore Areas</span>
		<ul class="lvc-side-links">
			<?php foreach ( $lvc_sidebar_areas as $label => $url ) : ?>
				<li><a href="<?php echo esc_url( home_url( $url ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<?php if ( $lvc_sidebar_bedrooms ) : ?>
	<div class="lvc-side-card">
		<span class="lvc-side-kicker">Popular Searches</span>
		<ul class="lvc-side-links">
			<?php foreach ( $lvc_sidebar_bedrooms as $label => $url ) : ?>
				<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<?php if ( $lvc_sidebar_villas->have_posts() ) : ?>
	<div class="lvc-side-card">
		<span class="lvc-side-kicker">Latest Villas</span>
		<div class="lvc-side-villas">
			<?php while ( $lvc_sidebar_villas->have_posts() ) : $lvc_sidebar_villas->the_post();
				$villa_id    = get_the_ID();
				$villa_name  = get_field( 'h1_property_title', $villa_id ) ? get_field( 'h1_property_title', $villa_id ) : get_the_title();
				$villa_image = lvc_property_image( $villa_id, 'medium_large' );
				?>
				<a class="lvc-side-villa" href="<?php the_permalink(); ?>">
					<?php if ( $villa_image ) : ?><span class="lvc-side-villa__image" style="background-image:url('<?php echo esc_url( $villa_image ); ?>')"></span><?php endif; ?>
					<span class="lvc-side-villa__body"><strong><?php echo esc_html( $villa_name ); ?></strong><em>View villa &rarr;</em></span>
				</a>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</div>
	<?php endif; ?>
</aside>
