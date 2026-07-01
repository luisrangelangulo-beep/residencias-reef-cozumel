<?php
/**
 * Magazine / blog single article — hero, body, related properties bridge.
 * (The auto-TOC + inline-CTA injector from the Tulum theme can be layered in
 * via inc/seo/ later; this skeleton keeps the editorial structure + the
 * SEO-to-booking related-properties widget.)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$lvc_img = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	if ( function_exists( 'lvc_schema_article' ) ) {
		lvc_schema_article( get_the_ID() );
	}
	?>
	<main class="lvc-article">
		<header class="lvc-article__hero" <?php echo $lvc_img ? 'style="--lvc-article-img:url(\'' . esc_url( $lvc_img ) . '\')"' : ''; ?>>
			<p class="lvc-eyebrow"><?php echo esc_html( get_the_date() ); ?></p>
			<h1 class="lvc-article__title"><?php the_title(); ?></h1>
		</header>

		<article class="lvc-article__body lvc-section">
			<?php the_content(); ?>
		</article>

		<?php
		// Related properties: match the article's destination/area terms if shared.
		$lvc_related = function_exists( 'lvc_related_properties_for_post' ) ? lvc_related_properties_for_post( get_the_ID(), 2 ) : array();
		if ( $lvc_related ) : ?>
			<section class="lvc-section lvc-related">
				<h2 class="lvc-sec-title"><?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?> for this Experience</h2>
				<div class="lvc-grid lvc-grid--2">
					<?php foreach ( $lvc_related as $rid ) {
						get_template_part( 'template-parts/card-property', null, array( 'id' => $rid ) );
					} ?>
				</div>
			</section>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
