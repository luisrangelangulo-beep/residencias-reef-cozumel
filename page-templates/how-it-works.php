<?php
/**
 * Template Name: How It Works
 * Luxury Villa Theme Core — booking process explainer + FAQ. Brand-agnostic.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_faqs = array(
	array( 'q' => 'Do I need to know which villa I want?', 'a' => 'No. Most guests start with just dates and group size — we help compare destinations and villas before narrowing the shortlist.' ),
	array( 'q' => 'Can you confirm availability and rates?', 'a' => 'Yes. Rates, taxes, fees, and minimum stays vary by villa and season, so we confirm availability and pricing before booking.' ),
	array( 'q' => 'When should I inquire for holiday weeks?', 'a' => 'As early as possible — holiday weeks have longer minimum stays, premium rates, and limited availability.' ),
);

get_header();

if ( function_exists( 'lvc_jsonld' ) ) {
	$qas = array();
	foreach ( $lvc_faqs as $f ) {
		$qas[] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) );
	}
	lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qas ) );
}
?>
<main class="lvc-page lvc-how">
<section class="lvc-hero lvc-hero--page" style="<?php echo esc_attr( lvc_page_hero_style() ); ?>">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">How It Works</p>
			<h1 class="lvc-hero__title"><?php echo esc_html( lvc_page_hero_title( 'How Booking With Us Works' ) ); ?></h1>
			<p class="lvc-hero__sub"><?php echo esc_html( lvc_page_hero_intro( 'Booking a private villa isn’t like booking a hotel — availability, rates, staff, chef service, and access all need confirming. Here’s how we make it simple.' ) ); ?></p>
			<div class="lvc-hero__cta"><a class="lvc-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Start an Inquiry</a><a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></a></div>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lcv-page-content">
			<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt">
		<div class="lvc-sec-header"><p class="lvc-eyebrow">Common Questions</p><h2 class="lvc-sec-title">Booking <em>FAQs</em></h2></div>
		<div class="lvc-faq">
			<?php foreach ( $lvc_faqs as $f ) : ?>
				<details class="lvc-faq__item"><summary class="lvc-faq__q"><?php echo esc_html( $f['q'] ); ?></summary><p class="lvc-faq__a"><?php echo esc_html( $f['a'] ); ?></p></details>
			<?php endforeach; ?>
		</div>
	</section>
</main>
<?php
get_footer();

