<?php
/**
 * Luxury Villa Theme Core — Inquiry handler (guest + owner).
 * ─────────────────────────────────────────────────────────────────────────
 * Production-grade contact/inquiry engine distilled from the brand themes.
 * Anti-spam: nonce + honeypot + submission time-trap + per-IP rate limit +
 * disposable-domain blocklist. Recipient + owner routing are config/filterable.
 *
 * Front-end form must POST (to admin-ajax.php) these hidden fields:
 *   action       = lvc_config('inquiry_action')   (default 'lvc_inquiry')
 *   _wpnonce     = wp_create_nonce( <inquiry_action> )
 *   website      = ""   (honeypot — must stay empty)
 *   lvc_ts       = <timestamp at render>  (time-trap)
 * Plus: name, email, message (required); optional phone, property_name,
 * source_url, inquiry_type ('guest'|'owner'), and any of the extra fields below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$action = (string) lvc_config( 'inquiry_action', 'lvc_inquiry' );
	add_action( 'wp_ajax_' . $action, 'lvc_handle_inquiry' );
	add_action( 'wp_ajax_nopriv_' . $action, 'lvc_handle_inquiry' );
} );

if ( ! function_exists( 'lvc_disposable_domains' ) ) {
	function lvc_disposable_domains() {
		return apply_filters( 'lvc_disposable_domains', array(
			'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'tempmail.com',
			'throwam.com', 'yopmail.com', 'trashmail.com', 'sharklasers.com', 'grr.la',
			'maildrop.cc', 'getnada.com', 'tempinbox.com', 'discard.email',
		) );
	}
}

if ( ! function_exists( 'lvc_handle_inquiry' ) ) {
	function lvc_handle_inquiry() {
		$action = (string) lvc_config( 'inquiry_action', 'lvc_inquiry' );

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
		}

		// Honeypot — bots fill hidden fields.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success( array( 'message' => 'Thank you.' ) );
		}

		// Time-trap — reject sub-2s submissions (supports ms or s timestamps).
		$ts = isset( $_POST['lvc_ts'] ) ? (int) $_POST['lvc_ts'] : 0;
		if ( $ts > 0 ) {
			$delta = ( $ts > 9999999999 )
				? (int) floor( ( round( microtime( true ) * 1000 ) - $ts ) / 1000 )
				: ( time() - $ts );
			if ( $delta >= 0 && $delta < 2 ) {
				wp_send_json_error( array( 'message' => 'Please wait a moment and try again.' ), 429 );
			}
		}

		// Per-IP rate limit.
		$rate_key   = 'lvc_inq_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
		$rate_count = (int) get_transient( $rate_key );
		if ( $rate_count >= 6 ) {
			wp_send_json_error( array( 'message' => 'Too many attempts. Please try again in about an hour.' ), 429 );
		}
		set_transient( $rate_key, $rate_count + 1, HOUR_IN_SECONDS );

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$message     = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$property    = isset( $_POST['property_name'] ) ? sanitize_text_field( wp_unslash( $_POST['property_name'] ) ) : '';
		$source_url  = isset( $_POST['source_url'] ) ? esc_url_raw( wp_unslash( $_POST['source_url'] ) ) : '';
		$type        = isset( $_POST['inquiry_type'] ) ? sanitize_key( wp_unslash( $_POST['inquiry_type'] ) ) : 'guest';

		// Generic capture of any known optional fields, in a stable order.
		$extra_keys = apply_filters( 'lvc_inquiry_extra_fields', array(
			'destination', 'area', 'checkin', 'checkout', 'guests', 'bedrooms', 'preferred_area', 'listing_url',
		) );
		$extra = array();
		foreach ( (array) $extra_keys as $k ) {
			if ( ! empty( $_POST[ $k ] ) ) {
				$extra[ $k ] = sanitize_text_field( wp_unslash( $_POST[ $k ] ) );
			}
		}

		if ( '' === $name || '' === $email || '' === $message ) {
			wp_send_json_error( array( 'message' => 'Please fill in name, email, and message.' ), 400 );
		}
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ), 400 );
		}

		$domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );
		if ( in_array( $domain, lvc_disposable_domains(), true ) ) {
			wp_send_json_error( array( 'message' => 'Please use a non-disposable email address.' ), 400 );
		}

		if ( '' === $property ) {
			$property = trim( lvc_config( 'brand_name', '' ) . ' Inquiry' );
		}

		$is_owner = ( 'owner' === $type );
		$support  = (string) lvc_config( 'support_email', '' );
		$owner    = (string) lvc_config( 'owner_email', '' );
		$owner    = '' !== $owner ? $owner : $support;

		$recipient = $is_owner
			? apply_filters( 'lvc_owner_inquiry_recipient', $owner )
			: apply_filters( 'lvc_inquiry_recipient', $support );

		$subject = ( $is_owner ? '[Owner Inquiry] ' : '[Inquiry] ' ) . $name . ' - ' . $property;

		$body  = ( $is_owner ? 'New OWNER inquiry' : 'New inquiry' ) . ' from ' . home_url( '/' ) . "\n\n";
		$body .= "Name:    {$name}\n";
		$body .= "Email:   {$email}\n";
		$body .= 'Phone:   ' . ( $phone ?: '-' ) . "\n";
		foreach ( $extra as $k => $v ) {
			$body .= ucfirst( str_replace( '_', ' ', $k ) ) . ":   {$v}\n";
		}
		$body .= "Property: {$property}\n";
		if ( $source_url ) {
			$body .= "Source:  {$source_url}\n";
		}
		$body .= "\nMessage:\n{$message}\n";
		$body .= "\n---\nIP: " . ( $_SERVER['REMOTE_ADDR'] ?? '-' ) . "\n";

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		);
		// Safety-net CC support inbox on owner leads until a dedicated mailbox is confirmed.
		if ( $is_owner && $support && strtolower( $recipient ) !== strtolower( $support ) ) {
			$headers[] = 'Cc: ' . $support;
		}

		$sent = wp_mail( $recipient, $subject, $body, $headers );

		if ( $sent ) {
			do_action( 'lvc_inquiry_submitted', compact( 'name', 'email', 'phone', 'property', 'type' ) );
			wp_send_json_success( array( 'message' => 'Thank you. We will respond ' . lvc_config( 'response_time', 'soon' ) . '.' ) );
		}

		wp_send_json_error( array( 'message' => 'Email delivery failed. Please try again or message us on WhatsApp.' ), 500 );
	}
}
