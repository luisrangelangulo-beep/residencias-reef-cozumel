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
?>
<footer class="lvc-footer">
	<div class="lvc-footer__inner">

		<div class="lvc-footer__brand">
			<span class="lvc-footer__name"><?php echo esc_html( lvc_brand() ); ?></span>
			<?php if ( lvc_config( 'brand_tagline' ) ) : ?>
				<p class="lvc-footer__tagline"><?php echo esc_html( lvc_config( 'brand_tagline' ) ); ?></p>
			<?php endif; ?>
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

		<div class="lvc-footer__col lvc-footer__contact">
			<h3 class="lvc-footer__heading">Contact</h3>
			<ul>
				<?php if ( lvc_config( 'support_email' ) ) : ?>
					<li><a href="mailto:<?php echo esc_attr( lvc_config( 'support_email' ) ); ?>"><?php echo esc_html( lvc_config( 'support_email' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( lvc_config( 'phone' ) ) : ?>
					<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', lvc_config( 'phone' ) ) ); ?>"><?php echo esc_html( lvc_config( 'phone' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( lvc_whatsapp_url() ) : ?>
					<li><a href="<?php echo esc_url( lvc_whatsapp_url() ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
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
