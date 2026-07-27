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
 * Plus: name, email, checkin, checkout, guests (required); optional phone,
 * message, budget, property_name, source_url, inquiry_type ('guest'|'owner'),
 * and any of the extra fields below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$action = (string) lvc_config( 'inquiry_action', 'lvc_inquiry' );
	add_action( 'wp_ajax_' . $action, 'lvc_handle_inquiry' );
	add_action( 'wp_ajax_nopriv_' . $action, 'lvc_handle_inquiry' );
} );

if ( ! function_exists( 'lvc_turnstile_field' ) ) {
	/**
	 * Cloudflare Turnstile widget — rendered inside the inquiry form, right
	 * before the submit button. The site key is public by design; the SECRET
	 * lives in the lvc_turnstile_secret option (DB only, never in the repo).
	 * When the secret is absent the widget is not rendered and the server
	 * check is skipped, so forms keep working where Turnstile isn't
	 * configured. FormData(form) picks up cf-turnstile-response automatically.
	 */
	function lvc_turnstile_field() {
		if ( '' === trim( (string) get_option( 'lvc_turnstile_secret' ) ) ) {
			return;
		}
		static $script_printed = false;
		echo '<div class="cf-turnstile" data-sitekey="0x4AAAAAAD9OWyEgjF9Jz8os" data-theme="dark" style="margin:0 0 1rem;"></div>';
		if ( ! $script_printed ) {
			echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
			$script_printed = true;
		}
	}
}

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

		/*
		 * Honeypot — bots fill hidden fields. Two safeguards against the
		 * failure mode where a REAL lead dies with a fake success (proven on
		 * a sibling site, where browser autofill filled name="website"):
		 * the field is now lvc_hp (legacy name still checked for cached
		 * pages), and hits are stored as spam-flagged records (per-IP
		 * capped) so a false positive is recoverable, never silently lost.
		 */
		if ( ! empty( $_POST['lvc_hp'] ) || ! empty( $_POST['website'] ) ) {
			$hp_rate_key = 'lvc_inq_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
			$hp_count    = (int) get_transient( $hp_rate_key );
			if ( $hp_count < 6 && function_exists( 'lvc_save_inquiry' ) ) {
				set_transient( $hp_rate_key, $hp_count + 1, HOUR_IN_SECONDS );
				$hp_id = lvc_save_inquiry( array(
					'type'       => 'guest',
					'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
					'email'      => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
					'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
					'checkin'    => isset( $_POST['checkin'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin'] ) ) : '',
					'checkout'   => isset( $_POST['checkout'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout'] ) ) : '',
					'guests'     => isset( $_POST['guests'] ) ? absint( $_POST['guests'] ) : 0,
					'budget'     => '',
					'message'    => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
					'property'   => isset( $_POST['property_name'] ) ? sanitize_text_field( wp_unslash( $_POST['property_name'] ) ) : '',
					'source_url' => isset( $_POST['source_url'] ) ? esc_url_raw( wp_unslash( $_POST['source_url'] ) ) : '',
					'extra'      => array(),
					'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
					'site'       => (string) lvc_config( 'brand_name', home_url() ),
				) );
				if ( $hp_id ) {
					update_post_meta( $hp_id, 'spam_suspect', 1 );
				}
			}
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

		// Cloudflare Turnstile — human verification. Only enforced when a
		// secret is configured; lvc_turnstile_field() likewise only renders
		// the widget then, so the two sides can never disagree. Fails OPEN
		// on Cloudflare network errors: a lost real lead costs more than the
		// rare spam the other layers would miss.
		$ts_secret = trim( (string) get_option( 'lvc_turnstile_secret' ) );
		if ( '' !== $ts_secret ) {
			$ts_token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
			$ts_ok    = false;
			if ( '' !== $ts_token ) {
				$ts_resp = wp_remote_post(
					'https://challenges.cloudflare.com/turnstile/v0/siteverify',
					array(
						'timeout' => 8,
						'body'    => array(
							'secret'   => $ts_secret,
							'response' => $ts_token,
						),
					)
				);
				if ( is_wp_error( $ts_resp ) ) {
					$ts_ok = true; // Cloudflare unreachable — fail open.
				} else {
					$ts_data = json_decode( wp_remote_retrieve_body( $ts_resp ), true );
					$ts_ok   = ! empty( $ts_data['success'] );
				}
			}
			if ( ! $ts_ok ) {
				wp_send_json_error( array( 'message' => 'Human verification failed. Please refresh the page and try again.' ), 403 );
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
		$checkin     = isset( $_POST['checkin'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin'] ) ) : '';
		$checkout    = isset( $_POST['checkout'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout'] ) ) : '';
		$guests      = isset( $_POST['guests'] ) ? absint( $_POST['guests'] ) : 0;
		$budget      = isset( $_POST['budget'] ) ? sanitize_text_field( wp_unslash( $_POST['budget'] ) ) : '';

		// Generic capture of any known optional fields, in a stable order.
		// UTM/attribution + property context ride along so paid-campaign
		// source and the exact unit reach the stored lead (audit RRC-012/020).
		$extra_keys = apply_filters( 'lvc_inquiry_extra_fields', array(
			'destination', 'area', 'checkin', 'checkout', 'guests', 'budget', 'bedrooms', 'preferred_area', 'listing_url',
			'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page', 'referrer',
			'property_id', 'adults', 'children',
		) );
		$extra = array();
		foreach ( (array) $extra_keys as $k ) {
			if ( ! empty( $_POST[ $k ] ) ) {
				$extra[ $k ] = sanitize_text_field( wp_unslash( $_POST[ $k ] ) );
			}
		}

		// Dates and guest count matter far more for qualifying a lead than a
		// free-text message, so message is optional; check-in/check-out/
		// guests are not (previously only name/email/message were required,
		// so leads routinely arrived with no dates, unquotable on first
		// touch). Owner inquiries don't book a stay, so they're exempt.
		$is_owner = ( 'owner' === $type );
		if ( ! $is_owner ) {
			if ( '' === $name || '' === $email || '' === $checkin || '' === $checkout || $guests < 1 ) {
				wp_send_json_error( array( 'message' => 'Please fill in your name, email, dates, and guest count.' ), 400 );
			}
			$checkin_dt  = DateTime::createFromFormat( 'Y-m-d', $checkin );
			$checkout_dt = DateTime::createFromFormat( 'Y-m-d', $checkout );
			if ( ! $checkin_dt || ! $checkout_dt ) {
				wp_send_json_error( array( 'message' => 'Please enter valid dates.' ), 400 );
			}
			if ( $checkout_dt <= $checkin_dt ) {
				wp_send_json_error( array( 'message' => 'Check-out must be after check-in.' ), 400 );
			}
			if ( $checkin_dt < new DateTime( 'today' ) ) {
				wp_send_json_error( array( 'message' => 'Check-in cannot be in the past.' ), 400 );
			}
		} elseif ( '' === $name || '' === $email ) {
			wp_send_json_error( array( 'message' => 'Please fill in your name and email.' ), 400 );
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
		$body .= "\nMessage:\n" . ( $message ?: '(none)' ) . "\n";
		$body .= "\n---\nIP: " . ( $_SERVER['REMOTE_ADDR'] ?? '-' ) . "\n";

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		);
		// Safety-net CC support inbox on owner leads until a dedicated mailbox is confirmed.
		if ( $is_owner && $support && strtolower( $recipient ) !== strtolower( $support ) ) {
			$headers[] = 'Cc: ' . $support;
		}

		/*
		 * Idempotency (audit RRC-012): one UUID per form load — a retry after
		 * a mail failure or a network hiccup must not create a second lead.
		 * The duplicate flag rides back so analytics never double-counts.
		 */
		$client_uuid = isset( $_POST['client_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['client_uuid'] ) ) : '';
		if ( '' !== $client_uuid && preg_match( '/^[0-9a-f-]{16,64}$/i', $client_uuid ) ) {
			$dupe = get_posts( array(
				'post_type'      => 'inquiry',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'client_uuid',
				'meta_value'     => $client_uuid,
				'date_query'     => array( array( 'after' => '24 hours ago' ) ),
				'no_found_rows'  => true,
			) );
			if ( $dupe ) {
				wp_send_json_success( array(
					'message'   => 'Your inquiry was already received (ref #' . (int) $dupe[0] . '). We will be in touch — no need to resubmit.',
					'lead_id'   => (int) $dupe[0],
					'duplicate' => true,
				) );
			}
		}

		// Persist BEFORE attempting delivery — these leads are worth
		// $30k-45k each; previously the inbox was the only record, so an
		// SMTP outage, spam-folder landing, or throttling meant the lead
		// was gone for good.
		$inquiry_post_id = 0;
		if ( function_exists( 'lvc_save_inquiry' ) ) {
			$inquiry_post_id = lvc_save_inquiry( array(
				'type'         => $type,
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'checkin'      => $checkin,
				'checkout'     => $checkout,
				'guests'       => $guests,
				'budget'       => $budget,
				'message'      => $message,
				'property'     => $property,
				'source_url'   => $source_url,
				'extra'        => $extra,
				'ip'           => $_SERVER['REMOTE_ADDR'] ?? '',
				'site'         => (string) lvc_config( 'brand_name', home_url() ),
			) );
		}

		if ( $inquiry_post_id && '' !== $client_uuid && preg_match( '/^[0-9a-f-]{16,64}$/i', $client_uuid ) ) {
			update_post_meta( $inquiry_post_id, 'client_uuid', $client_uuid );
		}

		// A failed save must not masquerade as a healthy submit (audit
		// RRC-012): the notification email becomes the ONLY record, so say so
		// in it, and leave a server-side trace for the operator.
		if ( ! $inquiry_post_id ) {
			error_log( sprintf( 'lvc_inquiry: persistence FAILED for %s (%s) — email is the only record.', $email, $property ) );
			$body .= "\n*** WARNING: this lead FAILED to save to the site database. This email is the only record — copy it somewhere safe. ***\n";
		}

		$sent = wp_mail( $recipient, $subject, $body, $headers );

		if ( $inquiry_post_id && ! $sent ) {
			update_post_meta( $inquiry_post_id, 'mail_failed', 1 );
		}

		do_action( 'lvc_inquiry_submitted', compact( 'name', 'email', 'phone', 'property', 'type', 'inquiry_post_id' ) );

		/*
		 * Saved lead = success (audit RRC-012): the old mail-failure path
		 * told the visitor to retry a lead that was already stored — the
		 * retry created a duplicate. A stored lead gets a success receipt
		 * either way; only total failure (no store AND no mail) errors.
		 */
		if ( $inquiry_post_id || $sent ) {
			wp_send_json_success( array(
				'message' => $sent
					? 'Thank you. We will respond ' . lvc_config( 'response_time', 'soon' ) . '.'
					: 'Your inquiry was received' . ( $inquiry_post_id ? ' (ref #' . (int) $inquiry_post_id . ')' : '' ) . '. Our email notification is delayed on our side — no need to resubmit; we will be in touch.',
				'lead_id' => (int) $inquiry_post_id,
				'saved'   => (bool) $inquiry_post_id,
			) );
		}
		wp_send_json_error( array( 'message' => 'Something went wrong on our side. Please message us on WhatsApp.' ), 500 );
	}
}
