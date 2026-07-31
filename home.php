<?php
/**
 * WordPress posts index.
 *
 * The Magazine page and the posts-page fallback intentionally share one
 * implementation so image handling, pagination, schema, and editorial layout
 * cannot drift between two near-identical templates.
 *
 * @package ResidenciasReefCozumel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_magazine_template = locate_template( 'page-templates/magazine.php' );
if ( $lvc_magazine_template ) {
	include $lvc_magazine_template;
	return;
}

get_header();
?>
<main class="lvc-page-modern">
	<section class="lvc-ed-section">
		<div class="lvc-ed-wrap">
			<h1><?php esc_html_e( 'Magazine', 'residencias-reef-cozumel' ); ?></h1>
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
				<?php endwhile; ?>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
