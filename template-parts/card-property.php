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

// Primary area term (first assigned), for the location line.
$lvc_area_terms = get_the_terms( $lvc_id, 'area' );
$lvc_area       = ( $lvc_area_terms && ! is_wp_error( $lvc_area_terms ) ) ? $lvc_area_terms[0]->name : '';

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
		<span class="lvc-card__img" style="--lvc-card-img:url('<?php echo esc_url( $lvc_img ); ?>')">
			<img src="<?php echo esc_url( $lvc_img ); ?>" alt="<?php echo esc_attr( $lvc_name ); ?>" loading="lazy" decoding="async">
		</span>
	<?php endif; ?>
	<span class="lvc-card__body">
		<?php if ( $lvc_area ) : ?><span class="lvc-card__loc"><?php echo esc_html( $lvc_area ); ?></span><?php endif; ?>
		<span class="lvc-card__name"><?php echo esc_html( $lvc_name ); ?></span>
		<?php if ( $lvc_specs ) : ?><span class="lvc-card__meta"><?php echo esc_html( implode( ' · ', $lvc_specs ) ); ?></span><?php endif; ?>
		<span class="lvc-card__cta">View <?php echo esc_html( lvc_config( 'cpt_singular', 'Villa' ) ); ?> &rarr;</span>
	</span>
</a>
