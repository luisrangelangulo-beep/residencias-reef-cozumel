<?php
/**
 * Reusable inquiry form. Wired to inc/inquiry/ajax-handler.php.
 * Optional $args: 'property_name', 'inquiry_type' ('guest'|'owner'), 'submit_label'.
 * Brand-agnostic markup + .lvc-form classes. JS to submit lives in assets/brand
 * or a site script; this part just renders the spec-correct fields.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_action   = (string) lvc_config( 'inquiry_action', 'lvc_inquiry' );
$lvc_type     = isset( $args['inquiry_type'] ) ? sanitize_key( $args['inquiry_type'] ) : 'guest';
$lvc_prop     = isset( $args['property_name'] ) ? (string) $args['property_name'] : '';
$lvc_submit   = isset( $args['submit_label'] ) ? (string) $args['submit_label'] : 'Send Enquiry';
?>
<form class="lvc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-lvc-inquiry>
	<input type="hidden" name="action" value="<?php echo esc_attr( $lvc_action ); ?>">
	<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( $lvc_action ) ); ?>">
	<input type="hidden" name="inquiry_type" value="<?php echo esc_attr( $lvc_type ); ?>">
	<input type="hidden" name="property_name" value="<?php echo esc_attr( $lvc_prop ); ?>">
	<input type="hidden" name="source_url" value="<?php echo esc_url( get_permalink() ?: home_url( '/' ) ); ?>">
	<input type="hidden" name="lvc_ts" value="<?php echo esc_attr( time() ); ?>">
	<?php // Honeypot — visually hidden; bots fill it, humans don't. ?>
	<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="lvc-form__hp">

	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="lvc-name">Full Name</label>
			<input id="lvc-name" type="text" name="name" required>
		</div>
		<div class="lvc-form__group">
			<label for="lvc-email">Email</label>
			<input id="lvc-email" type="email" name="email" required>
		</div>
	</div>
	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="lvc-checkin">Check-in</label>
			<input id="lvc-checkin" type="date" name="checkin">
		</div>
		<div class="lvc-form__group">
			<label for="lvc-checkout">Check-out</label>
			<input id="lvc-checkout" type="date" name="checkout">
		</div>
	</div>
	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="lvc-guests">Guests</label>
			<input id="lvc-guests" type="number" name="guests" min="1" max="100">
		</div>
		<div class="lvc-form__group">
			<label for="lvc-phone">Phone / WhatsApp</label>
			<input id="lvc-phone" type="text" name="phone">
		</div>
	</div>
	<div class="lvc-form__group">
		<label for="lvc-message">Message</label>
		<textarea id="lvc-message" name="message" required></textarea>
	</div>

	<p class="lvc-form__status" data-inquiry-status aria-live="polite"></p>
	<button type="submit" class="lvc-btn lvc-form__submit"><?php echo esc_html( $lvc_submit ); ?></button>
	<p class="lvc-form__micro">We typically respond <?php echo esc_html( lvc_config( 'response_time', 'soon' ) ); ?>.</p>
</form>
