<?php
/**
 * Theme footer — dynamic destination/area link columns + contact + legal.
 * All links/contact derive from taxonomy terms + config. No styling here.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Top areas for the link column (by property count). No `destination` taxonomy on this site — `area` plays that role.
$lvc_area = get_terms( array( 'taxonomy' => 'area', 'hide_empty' => true, 'number' => 8, 'orderby' => 'count', 'order' => 'DESC' ) );
$lvc_req  = lvc_page_url( 'request' );
$lvc_wa   = lvc_whatsapp_url();
?>
<footer class="lvc-footer">
	<div class="lvc-footer__cta">
		<div class="lvc-footer__cta-copy">
			<span class="lvc-footer__eyebrow">Private stays across the Riviera Maya</span>
			<h2>Start with the right location. <em>Finish with the right villa.</em></h2>
			<p>Share your dates, group size, and priorities. We will help compare realistic options across Cozumel, Tulum, Playa del Carmen, Akumal, and beyond.</p>
		</div>
		<div class="lvc-footer__cta-actions">
			<a class="lvc-footer__btn" href="<?php echo esc_url( $lvc_req ); ?>">Plan Your Stay <span aria-hidden="true">&rarr;</span></a>
			<?php if ( $lvc_wa ) : ?>
				<a class="lvc-footer__btn lvc-footer__btn--ghost" href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">WhatsApp</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="lvc-footer__inner">

		<div class="lvc-footer__brand">
			<span class="lvc-footer__brand-kicker">Cozumel · Tulum · Riviera Maya</span>
			<span class="lvc-footer__name"><?php echo esc_html( lvc_brand() ); ?></span>
			<?php if ( lvc_config( 'brand_tagline' ) ) : ?>
				<p class="lvc-footer__tagline"><?php echo esc_html( lvc_config( 'brand_tagline' ) ); ?></p>
			<?php endif; ?>
			<p class="lvc-footer__brand-note">Direct booking guidance, thoughtful villa matching, and local stay planning.</p>
		</div>

		<?php if ( ! is_wp_error( $lvc_area ) && $lvc_area ) : ?>
		<nav class="lvc-footer__col" aria-label="Areas">
			<h3 class="lvc-footer__heading">Areas</h3>
			<ul>
				<?php foreach ( $lvc_area as $t ) : $u = function_exists( 'lvc_area_lander_url_by_term' ) ? lvc_area_lander_url_by_term( $t ) : get_term_link( $t ); if ( is_wp_error( $u ) ) { continue; } ?>
					<li><a href="<?php echo esc_url( $u ); ?>"><?php echo esc_html( $t->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php endif; ?>

		<nav class="lvc-footer__col" aria-label="Explore">
			<h3 class="lvc-footer__heading">Explore</h3>
			<ul>
				<li><a href="<?php echo esc_url( lvc_archive_url() ); ?>">All <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( lvc_page_url( 'magazine' ) ); ?>">Magazine</a></li>
				<li><a href="<?php echo esc_url( lvc_page_url( 'about' ) ); ?>">About</a></li>
			</ul>
		</nav>

		<?php
		/*
		 * Cross-property links.
		 *
		 * These existed years ago ("buy a piece of paradise" → real estate, plus a
		 * tours link) and were dropped during the rebuild. Guests staying here are
		 * the warmest possible buyers for the same building, and the search data
		 * shows the reverse too: rental-intent queries currently land on the
		 * for-sale site, which cannot serve them.
		 *
		 * Filterable so a brand can drop or repoint one without a template edit.
		 */
		$lvc_sister = apply_filters( 'lvc_sister_sites', array(
			array(
				'label' => 'Buy at Residencias Reef',
				'url'   => 'https://www.cozumel-real-estate.com/development/residencias-reef/',
			),
			array(
				'label' => 'Cozumel Shore Excursions',
				'url'   => 'https://www.cozumel-shore-excursions.com/',
			),
		) );
		?>
		<?php if ( $lvc_sister ) : ?>
			<nav class="lvc-footer__col" aria-label="Also from us">
				<h3 class="lvc-footer__heading">Also From Us</h3>
				<ul>
					<?php foreach ( $lvc_sister as $lvc_s ) : ?>
						<?php if ( empty( $lvc_s['url'] ) || empty( $lvc_s['label'] ) ) { continue; } ?>
						<li><a href="<?php echo esc_url( $lvc_s['url'] ); ?>" rel="noopener"><?php echo esc_html( $lvc_s['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<div class="lvc-footer__col lvc-footer__contact">
			<h3 class="lvc-footer__heading">Contact</h3>
			<ul>
				<?php if ( lvc_config( 'support_email' ) ) : ?>
					<li><span class="lvc-footer__contact-label">Email</span><a href="mailto:<?php echo esc_attr( lvc_config( 'support_email' ) ); ?>"><?php echo esc_html( lvc_config( 'support_email' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( lvc_config( 'phone' ) ) : ?>
					<li><span class="lvc-footer__contact-label">Call</span><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', lvc_config( 'phone' ) ) ); ?>"><?php echo esc_html( lvc_config( 'phone' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( $lvc_wa ) : ?>
					<li><span class="lvc-footer__contact-label">Message</span><a href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="lvc-footer__legal">
		<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( lvc_brand() ); ?></span>
		<nav aria-label="Legal">
			<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy</a>
			<a href="<?php echo esc_url( home_url( '/rental-policies/' ) ); ?>">Terms</a>
		</nav>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

