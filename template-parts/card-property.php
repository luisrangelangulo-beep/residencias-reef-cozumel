<?php
/**
 * Reusable property card. Expects $args['id'] (post ID) via get_template_part args
 * or the current loop post. Brand-agnostic markup + .lvc-card classes only.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_id   = isset( $args['id'] ) ? (int) $args['id'] : get_the_ID();
$lvc_img  = lvc_property_image( $lvc_id );
$lvc_name = lvc_field( 'card_title', $lvc_id, get_the_title( $lvc_id ) );
$lvc_url  = get_permalink( $lvc_id );

// Most specific area term (e.g. "Soliman Bay", not "Riviera Maya"), for the location line.
$lvc_area_obj = lvc_property_area_term( $lvc_id );
$lvc_area     = $lvc_area_obj ? $lvc_area_obj->name : '';

// Key facts (ACF first, taxonomy fallback for bedrooms).
$lvc_beds   = lvc_field( 'bed_count', $lvc_id );
$lvc_baths  = lvc_field( 'bath_count', $lvc_id );
$lvc_guests = lvc_field( 'guests_max', $lvc_id );
if ( '' === $lvc_beds ) {
	$bt = get_the_terms( $lvc_id, 'bedrooms' );
	$lvc_beds = ( $bt && ! is_wp_error( $bt ) ) ? preg_replace( '/\D/', '', $bt[0]->name ) : '';
}

$lvc_specs = array_filter( array(
	$lvc_beds ? $lvc_beds . ' BR' : '',
	$lvc_baths ? $lvc_baths . ' BA' : '',
	$lvc_guests ? 'Sleeps ' . $lvc_guests : '',
) );
?>
<a class="lvc-card" href="<?php echo esc_url( $lvc_url ); ?>" aria-label="<?php echo esc_attr( $lvc_name ); ?>">
	<?php if ( $lvc_img ) : ?>
		<span class="lvc-card__img">
			<?php
			/*
			 * NO deferral on card images — not native loading="lazy", and not WP
			 * Rocket's LazyLoad (hence skip-lazy + data-no-lazy, the same opt-out
			 * single-villas.php uses on the hero). Do not restore either without
			 * re-testing.
			 *
			 * Card images were stalling permanently in Chromium: complete=false /
			 * naturalWidth=0 with no network request ever issued, even after the card
			 * had been scrolled into view and left there. Flipping only the loading
			 * attribute to "eager" on a stuck element loaded it instantly, so the
			 * deferral was the blocker — not the URL, srcset, CDN or cache (every card
			 * URL on /villas/ was verified 200 through Photon).
			 *
			 * The two mechanisms are not independent: Rocket LazyLoad skips images
			 * that already carry loading="lazy" and takes over the ones that do not,
			 * swapping src for an SVG placeholder and srcset for data-lazy-srcset. So
			 * dropping the native attribute alone just moves these images onto
			 * Rocket's script — which would also defeat the Photon origin fallback in
			 * theme.js, since that reads img.srcset and sets img.src. Opting out of
			 * both is what actually leaves a plain, immediately-fetched <img>.
			 *
			 * Cost: the grid's images are fetched up front. Accepted — they are
			 * Photon-optimised webp, and a browse grid whose photos never appear is
			 * worse than a heavier one. Chromium already fetches images at Low
			 * priority and boosts only those that land in the viewport, so no explicit
			 * fetchpriority is wanted here.
			 *
			 * To re-test: load /villas/ and confirm every .lvc-card__img img has a
			 * real src (not a data: placeholder), complete=true and naturalWidth>0.
			 */
			?>
			<img class="skip-lazy" data-no-lazy="1" src="<?php echo esc_url( function_exists( 'lvc_cdn_img' ) ? lvc_cdn_img( $lvc_img, 800 ) : $lvc_img ); ?>"
				<?php if ( function_exists( 'lvc_cdn_srcset' ) ) : ?>srcset="<?php echo esc_attr( lvc_cdn_srcset( $lvc_img, array( 400, 800, 1200 ) ) ); ?>" sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"<?php endif; ?>
				<?php echo function_exists( 'lvc_cdn_fallback_attr' ) ? lvc_cdn_fallback_attr( $lvc_img, 800 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute ?>
				alt="<?php echo esc_attr( $lvc_name ); ?>" width="800" height="600" decoding="async">
		</span>
	<?php endif; ?>
	<span class="lvc-card__body">
		<?php if ( $lvc_area ) : ?><span class="lvc-card__loc"><?php echo esc_html( $lvc_area ); ?></span><?php endif; ?>
		<span class="lvc-card__name"><?php echo esc_html( $lvc_name ); ?></span>
		<?php if ( $lvc_specs ) : ?><span class="lvc-card__meta"><?php echo esc_html( implode( ' · ', $lvc_specs ) ); ?></span><?php endif; ?>
		<span class="lvc-card__price">Rates on Request</span>
		<span class="lvc-card__cta">View <?php echo esc_html( lvc_config( 'cpt_singular', 'Villa' ) ); ?> &rarr;</span>
	</span>
</a>
