<?php
/**
 * Generic page template.
 *
 * Gives About, How It Works, Contact-style editorial pages a designed hero,
 * readable content layout, and a conversion/sidebar path back to villas.
 * Ported from Los Cabos's page.php (same lvc-ed-* classes / editorial.css).
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// Pages designed entirely in Elementor already have their own hero/CTA —
	// wrapping them in the generic editorial hero would double it up.
	$lvc_elementor_built = (bool) get_post_meta( get_the_ID(), '_elementor_data', true );

	if ( $lvc_elementor_built ) {
		?>
		<main class="lvc-page-elementor">
			<?php the_content(); ?>
		</main>
		<?php
		continue;
	}

	$lvc_page_image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	$lvc_excerpt    = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 34 );
	?>
	<main class="lvc-page-modern">
		<section class="lvc-ed-hero" <?php echo $lvc_page_image ? 'style="--lvc-ed-hero-img:url(\'' . esc_url( $lvc_page_image ) . '\')"' : ''; ?>>
			<div class="lvc-ed-wrap">
				<span class="lvc-ed-kicker"><?php echo esc_html( lvc_brand() ); ?></span>
				<h1 class="lvc-ed-title"><?php the_title(); ?></h1>
				<?php if ( $lvc_excerpt ) : ?><p class="lvc-ed-sub"><?php echo esc_html( $lvc_excerpt ); ?></p><?php endif; ?>
			</div>
		</section>

		<section class="lvc-ed-section">
			<div class="lvc-ed-wrap lvc-ed-layout">
				<article class="lvc-ed-content">
					<?php the_content(); ?>
				</article>
				<?php get_template_part( 'template-parts/editorial-sidebar' ); ?>
			</div>
		</section>

		<section class="lvc-ed-section lvc-ed-cta">
			<div class="lvc-ed-narrow">
				<span class="lvc-ed-kicker">Villa Planning</span>
				<h2 class="lvc-ed-title">Ready to compare Riviera Maya villas?</h2>
				<p class="lvc-ed-sub">Tell us your dates, group size, preferred area, and villa priorities. We will help narrow the search to options that fit your trip.</p>
				<div class="lvc-ed-btns">
					<a class="lvc-ed-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request Villa Matches</a>
					<a class="lvc-ed-btn lvc-ed-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse Villas</a>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
