<?php
/**
 * Floating action buttons — fixed bottom-right on every page.
 * Stacked bottom-to-top: WhatsApp (always last so its position doesn't
 * move for existing visitors), Email, Browse Villas (globe). WhatsApp
 * only renders when whatsapp_url is configured; email only when
 * support_email is configured. Brand-agnostic aside from WhatsApp's own
 * brand green.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', 'lvc_render_float_actions' );

if ( ! function_exists( 'lvc_render_float_actions' ) ) {
	function lvc_render_float_actions() {
		$wa_url = lvc_whatsapp_url();
		$email  = (string) lvc_config( 'support_email', '' );
		?>
		<div class="lvc-float-stack">
			<a class="lvc-fab lvc-fab--globe" href="<?php echo esc_url( lvc_archive_url() ); ?>" aria-label="Browse all villas">
				<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 4 5.5 4 9s-1.5 6.5-4 9c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9z"/></svg>
			</a>
			<?php if ( $email ) : ?>
				<a class="lvc-fab lvc-fab--email" href="<?php echo esc_url( 'mailto:' . $email ); ?>" aria-label="Email us">
					<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M3 5h18v14H3z"/><path d="M3 5l9 7 9-7"/></svg>
				</a>
			<?php endif; ?>
			<?php if ( $wa_url ) : ?>
				<a class="lvc-fab lvc-fab--wa" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( apply_filters( 'lvc_whatsapp_label', 'Chat with a villa specialist on WhatsApp' ) ); ?>" data-lvc-wa>
					<svg viewBox="0 0 32 32" width="30" height="30" aria-hidden="true" focusable="false">
						<path fill="currentColor" d="M16 .4C7.4.4.4 7.4.4 16c0 2.8.7 5.5 2.1 7.9L.3 31.6l7.9-2.1c2.3 1.3 5 1.9 7.8 1.9 8.6 0 15.6-7 15.6-15.6S24.6.4 16 .4zm0 28.5c-2.5 0-5-.7-7.1-1.9l-.5-.3-4.7 1.2 1.3-4.6-.3-.5C3.3 21 2.6 18.5 2.6 16 2.6 8.6 8.6 2.6 16 2.6S29.4 8.6 29.4 16 23.4 28.9 16 28.9zm7.4-9.7c-.4-.2-2.4-1.2-2.8-1.3-.4-.1-.6-.2-.9.2-.3.4-1 1.3-1.3 1.6-.2.3-.5.3-.9.1-.4-.2-1.7-.6-3.3-2-1.2-1.1-2-2.4-2.3-2.8-.2-.4 0-.6.2-.8.2-.2.4-.5.6-.7.2-.2.3-.4.4-.7.1-.3.1-.5 0-.7-.1-.2-.9-2.1-1.2-2.9-.3-.8-.6-.7-.9-.7h-.8c-.3 0-.7.1-1 .5-.3.4-1.3 1.3-1.3 3.1s1.3 3.6 1.5 3.9c.2.3 2.6 4 6.3 5.6.9.4 1.6.6 2.1.8.9.3 1.7.2 2.3.1.7-.1 2.4-1 2.7-1.9.3-.9.3-1.7.2-1.9-.1-.2-.3-.3-.7-.5z"/>
					</svg>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}
}
