<?php
/**
 * Theme header — site <head> + fixed navigation.
 * Brand logo/name from config; menu from the 'primary' nav location.
 * No styling here (see assets/brand.css); structure + hooks only.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'lvc-body' ); ?>>
<?php wp_body_open(); ?>

<header class="lvc-header" data-lvc-header>
	<div class="lvc-header__inner">
		<a class="lvc-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( lvc_brand() ); ?>">
			<?php
			$lvc_logo = (string) lvc_config( 'brand_logo_svg', '' );
			if ( $lvc_logo ) {
				echo $lvc_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from config
			} else {
				echo '<span class="lvc-header__name">' . esc_html( lvc_brand() ) . '</span>';
				$tag = (string) lvc_config( 'brand_tagline', '' );
				if ( $tag ) {
					echo '<span class="lvc-header__tagline">' . esc_html( $tag ) . '</span>';
				}
			}
			?>
		</a>

		<nav class="lvc-nav" aria-label="Primary">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'lvc-nav__list',
					'fallback_cb'    => false,
					'depth'          => 2,
				) );
			}
			?>
		</nav>

		<div class="lvc-header__actions">
			<?php if ( lvc_whatsapp_url() ) : ?>
				<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_whatsapp_url() ); ?>" target="_blank" rel="noopener">Speak With a Specialist</a>
			<?php endif; ?>
			<a class="lvc-btn" href="<?php echo esc_url( lvc_archive_url() ); ?>">Request Availability &rarr;</a>
		</div>

		<button class="lvc-header__toggle" type="button" aria-label="Open menu" aria-expanded="false" data-lvc-drawer-toggle>
			<span></span><span></span><span></span>
		</button>
	</div>

	<div class="lvc-drawer" data-lvc-drawer hidden>
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'lvc-drawer__list',
				'fallback_cb'    => false,
				'depth'          => 2,
			) );
		}
		?>
	</div>
</header>
