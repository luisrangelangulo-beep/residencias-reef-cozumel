<?php
/**
 * Template Name: Legal
 * Luxury Villa Theme Core — generic legal page (Privacy Policy, Terms of Service,
 * Rental Policies, etc.). Renders the WP page content in a styled prose layout, so
 * the legal copy lives in the page (editable per brand) — not hardcoded in the theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="lvc-page lvc-legal">
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">Legal</p>
			<h1 class="lvc-hero__title"><?php the_title(); ?></h1>
			<?php $lvc_mod = get_the_modified_date(); if ( $lvc_mod ) : ?>
				<p class="lvc-hero__sub">Last updated: <?php echo esc_html( $lvc_mod ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lvc-prose">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</section>
</main>
<?php
get_footer();
