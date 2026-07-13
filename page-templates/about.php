<?php
/**
 * Template Name: About
 * Luxury Villa Theme Core — brand-agnostic About page. Copy is generic; brand
 * values come from theme-config.php via lvc_ helpers. Customise per site.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_cpt    = lvc_config( 'cpt', 'villa' );
$lvc_count  = (int) ( wp_count_posts( $lvc_cpt )->publish ?? 0 );
$lvc_dests  = (int) wp_count_terms( array( 'taxonomy' => 'area', 'hide_empty' => false ) );

get_header();
?>
<main class="lvc-page lvc-about">
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">About Us</p>
			<h1 class="lvc-hero__title">About <em>Residencias Reef Cozumel</em></h1>
			<p class="lvc-hero__sub"><?php echo esc_html( lvc_brand() ); ?> helps guests compare luxury villas with clear context on location, service, access, and rates — built for direct inquiries, not anonymous browsing.</p>
			<div class="lvc-hero__cta">
				<a class="lvc-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request a Villa Match</a>
				<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></a>
			</div>
		</div>
	</section>

	<section class="lvc-trust">
		<div class="lvc-trust__inner">
			<?php if ( $lvc_count ) : ?><span><strong><?php echo esc_html( $lvc_count ); ?>+</strong> Luxury Villas</span><?php endif; ?>
			<?php if ( $lvc_dests > 1 ) : ?><span><strong><?php echo esc_html( $lvc_dests ); ?></strong> Areas</span><?php endif; ?>
			<span><strong>Direct</strong> Booking — No Fees</span>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lcv-page-content">
			<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt lvc-cta">
		<div class="lvc-cta__inner">
			<h2 class="lvc-sec-title">Tell us what you need</h2>
			<p>Send your dates, group size, and preferred destination — we&rsquo;ll shortlist realistic villa options that fit your trip.</p>
			<div class="lvc-hero__cta">
				<a class="lvc-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request a Villa Match</a>
				<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_page_url( 'contact' ) ); ?>">Contact Us</a>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
