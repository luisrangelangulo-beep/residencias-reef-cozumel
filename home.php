<?php
/**
 * Blog posts index (WordPress "Posts page" — Settings > Reading).
 * Template hierarchy routes this page to home.php, not archive.php, so the
 * magazine card-grid markup needs its own copy here for /magazine/articles/.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_posts_page_id = (int) get_option( 'page_for_posts' );
$lvc_index_title   = $lvc_posts_page_id ? get_the_title( $lvc_posts_page_id ) : 'Articles';
?>
<main class="lvc-magindex">
	<section class="lvc-magindex__head lvc-section">
		<p class="lvc-eyebrow">Magazine</p>
		<h1 class="lvc-sec-title"><?php echo esc_html( $lvc_index_title ?: 'Articles' ); ?></h1>
	</section>

	<section class="lvc-section">
		<?php if ( have_posts() ) : ?>
			<div class="lvc-grid lvc-grid--3">
				<?php while ( have_posts() ) : the_post(); $img = get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>
					<a class="lvc-magcard" href="<?php the_permalink(); ?>">
						<?php if ( $img ) : ?><span class="lvc-magcard__img"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy"></span><?php endif; ?>
						<span class="lvc-magcard__body">
							<span class="lvc-magcard__date"><?php echo esc_html( get_the_date() ); ?></span>
							<span class="lvc-magcard__title"><?php the_title(); ?></span>
							<span class="lvc-magcard__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></span>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<nav class="lvc-pagination" aria-label="Pagination">
				<?php echo paginate_links( array( 'mid_size' => 1, 'prev_text' => '&larr;', 'next_text' => '&rarr;' ) ); ?>
			</nav>
		<?php else : ?>
			<p class="lvc-empty">No articles yet.</p>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
