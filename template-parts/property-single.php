<?php
/**
 * Single property — hero, gallery, overview + living sections, amenities,
 * service, FAQ, inquiry, related, JSON-LD. Routed here for the configured CPT
 * by the router. Structure + logic only; brand styling via assets/brand.css.
 *
 * Reads the generator's fields directly (property_descr, indoor_living,
 * outdoor_living, bedroom_desc, catering_detail, faq_q1..faq_a4, gallery_squares).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$lvc_id      = get_the_ID();
	$lvc_img     = lvc_property_image( $lvc_id, 'full' );
	$lvc_h1      = lvc_field( 'h1_title', $lvc_id, get_the_title() );
	$lvc_beds    = lvc_field( 'bed_count', $lvc_id );
	$lvc_baths   = lvc_field( 'bath_count', $lvc_id );
	$lvc_guests  = lvc_field( 'guests_max', $lvc_id );
	$lvc_over    = lvc_field( 'property_descr', $lvc_id, get_the_content() );
	$lvc_indoor  = lvc_field( 'indoor_living', $lvc_id );
	$lvc_outdoor = lvc_field( 'outdoor_living', $lvc_id );
	$lvc_bedrm   = lvc_field( 'bedroom_desc', $lvc_id );
	$lvc_cater   = lvc_field( 'catering_detail', $lvc_id );
	$lvc_area    = get_the_terms( $lvc_id, 'area' );
	$lvc_area_n  = ( $lvc_area && ! is_wp_error( $lvc_area ) ) ? $lvc_area[0]->name : '';

	if ( function_exists( 'lvc_schema_property' ) ) {
		lvc_schema_property( $lvc_id );
	}
	?>
	<main class="lvc-single">
		<section class="lvc-single__hero<?php echo $lvc_img ? ' lvc-single__hero--img' : ''; ?>">
			<?php if ( $lvc_img ) : ?>
				<img class="lvc-single__img" src="<?php echo esc_url( $lvc_img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
				<span class="lvc-single__scrim" aria-hidden="true"></span>
			<?php endif; ?>
			<div class="lvc-single__heading">
				<?php if ( $lvc_area_n ) : ?><p class="lvc-eyebrow"><?php echo esc_html( $lvc_area_n ); ?></p><?php endif; ?>
				<h1 class="lvc-single__title"><?php echo esc_html( $lvc_h1 ); ?></h1>
				<ul class="lvc-single__facts">
					<?php if ( $lvc_beds ) : ?><li><?php echo esc_html( $lvc_beds ); ?> Bedrooms</li><?php endif; ?>
					<?php if ( $lvc_baths ) : ?><li><?php echo esc_html( $lvc_baths ); ?> Baths</li><?php endif; ?>
					<?php if ( $lvc_guests ) : ?><li>Sleeps <?php echo esc_html( $lvc_guests ); ?></li><?php endif; ?>
				</ul>
			</div>
		</section>

		<?php
		// Gallery — one Cloudflare R2 URL per line/comma in gallery_squares.
		$lvc_gallery_raw = (string) lvc_field( 'gallery_squares', $lvc_id );
		$lvc_gallery     = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $lvc_gallery_raw ) ) );
		if ( $lvc_gallery ) : ?>
			<section class="lvc-single__gallery lvc-gallery">
				<?php foreach ( $lvc_gallery as $g ) :
					if ( ! preg_match( '#^https?://#i', $g ) ) {
						continue;
					} ?>
					<figure class="lvc-gallery__item">
						<img src="<?php echo esc_url( $g ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" decoding="async">
					</figure>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<div class="lvc-single__body lvc-section">
			<article class="lvc-single__main">
				<?php if ( $lvc_over ) : ?>
					<div class="lvc-single__desc"><?php echo wp_kses_post( wpautop( $lvc_over ) ); ?></div>
				<?php endif; ?>

				<?php
				// Living sections — only render what the generator produced.
				$lvc_living = array(
					'Indoor Living'  => $lvc_indoor,
					'Outdoor Living' => $lvc_outdoor,
					'The Bedrooms'   => $lvc_bedrm,
				);
				foreach ( $lvc_living as $lvc_heading => $lvc_text ) :
					if ( ! $lvc_text ) {
						continue;
					} ?>
					<section class="lvc-single__section">
						<h2 class="lvc-sec-title"><?php echo esc_html( $lvc_heading ); ?></h2>
						<div class="lvc-prose"><?php echo wp_kses_post( wpautop( $lvc_text ) ); ?></div>
					</section>
				<?php endforeach; ?>

				<?php
				// Amenities (taxonomy — assigned by the sync from the token list).
				$lvc_amen = get_the_terms( $lvc_id, 'amenity' );
				if ( $lvc_amen && ! is_wp_error( $lvc_amen ) ) : ?>
					<section class="lvc-single__amenities">
						<h2 class="lvc-sec-title">Amenities</h2>
						<ul class="lvc-amenities">
							<?php foreach ( $lvc_amen as $a ) : ?><li><?php echo esc_html( $a->name ); ?></li><?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $lvc_cater ) : ?>
					<section class="lvc-single__service">
						<h2 class="lvc-sec-title">Service &amp; Catering</h2>
						<div class="lvc-prose"><?php echo wp_kses_post( wpautop( $lvc_cater ) ); ?></div>
					</section>
				<?php endif; ?>

				<?php
				// FAQ — flat faq_q1..faq_a4 (1:1 with the generator). FAQPage schema emitted in lvc_schema_property().
				$lvc_faq = array();
				for ( $i = 1; $i <= 4; $i++ ) {
					$q = lvc_field( 'faq_q' . $i, $lvc_id );
					$a = lvc_field( 'faq_a' . $i, $lvc_id );
					if ( $q && $a ) {
						$lvc_faq[] = array( $q, $a );
					}
				}
				if ( $lvc_faq ) : ?>
					<section class="lvc-faq">
						<h2 class="lvc-sec-title">Good to Know</h2>
						<?php foreach ( $lvc_faq as $qa ) : ?>
							<details class="lvc-faq__item">
								<summary class="lvc-faq__q"><?php echo esc_html( $qa[0] ); ?></summary>
								<div class="lvc-faq__a"><?php echo wp_kses_post( wpautop( $qa[1] ) ); ?></div>
							</details>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
			</article>

			<aside class="lvc-single__sidebar">
				<div class="lvc-single__inquiry" id="inquiry">
					<h2 class="lvc-sec-title">Enquire</h2>
					<?php
					get_template_part( 'template-parts/inquiry-form', null, array(
						'property_name' => get_the_title(),
						'submit_label'  => 'Request Availability',
					) );
					?>
				</div>
			</aside>
		</div>

		<?php
		// Related: same area, then fall back to same destination.
		$lvc_related = function_exists( 'lvc_related_properties' ) ? lvc_related_properties( $lvc_id, 3 ) : array();
		if ( $lvc_related ) : ?>
			<section class="lvc-section lvc-related">
				<h2 class="lvc-sec-title">Similar <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></h2>
				<div class="lvc-grid lvc-grid--3">
					<?php foreach ( $lvc_related as $rid ) {
						get_template_part( 'template-parts/card-property', null, array( 'id' => $rid ) );
					} ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="lvc-single__mobilebar">
			<span class="lvc-single__mobilebar-name"><?php echo esc_html( get_the_title() ); ?></span>
			<a class="lvc-btn" href="#inquiry">Request Availability</a>
		</div>
	</main>
	<?php
endwhile;

get_footer();
