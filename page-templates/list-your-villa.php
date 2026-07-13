<?php
/**
 * Template Name: For Owners
 * Luxury Villa Theme Core — owner marketing page. Owner inquiries route via
 * inquiry_type=owner (handled by inc/inquiry/ajax-handler.php). Brand-agnostic.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_faqs = array(
	array( 'q' => 'Do you manage villas?', 'a' => 'Our focus is marketing, presentation, SEO, and qualified direct-booking inquiries. We can work alongside your existing manager or rental team.' ),
	array( 'q' => 'Can my villa appear on the site?', 'a' => 'Potentially. We review location, quality, capacity, service, and availability before adding a villa to the collection.' ),
	array( 'q' => 'Do I need to leave my current manager?', 'a' => 'No. Many owners simply want stronger marketing, better SEO, and more qualified direct inquiries.' ),
	array( 'q' => 'Do you guarantee bookings or rankings?', 'a' => 'No — and no serious partner should. We focus on visibility, presentation, and qualified inquiry flow.' ),
);

get_header();

if ( function_exists( 'lvc_jsonld' ) ) {
	$lvc_service = array(
		'@context' => 'https://schema.org', '@type' => 'Service',
		'name' => 'Villa Owner Marketing',
		'serviceType' => 'Luxury villa marketing, SEO, and direct-booking inquiry support',
		'provider' => array( '@type' => 'Organization', 'name' => lvc_brand(), 'url' => home_url( '/' ), 'email' => lvc_config( 'support_email', '' ) ),
	);
	if ( lvc_config( 'region' ) ) {
		$lvc_service['areaServed'] = lvc_config( 'region' );
	}
	lvc_jsonld( $lvc_service );
}
?>
<main class="lvc-page lvc-owner">
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">For Villa Owners</p>
			<h1 class="lvc-hero__title">List Your Villa <em>With Us</em></h1>
			<p class="lvc-hero__sub">If you own a luxury villa, we help present it, get it found, and turn searches into qualified direct-booking inquiries.</p>
			<div class="lvc-hero__cta"><a class="lvc-btn" href="#owner-inquiry">Speak With Us</a></div>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lcv-page-content">
			<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt" id="owner-inquiry">
		<div class="lvc-contact__grid">
			<div class="lvc-contact__form">
				<p class="lvc-eyebrow">Owner Inquiry</p>
				<h2 class="lvc-sec-title">Tell us about <em>your villa</em></h2>
				<?php get_template_part( 'template-parts/inquiry-form', null, array( 'inquiry_type' => 'owner', 'property_name' => 'Villa Owner Inquiry', 'submit_label' => 'Submit Owner Inquiry' ) ); ?>
			</div>
			<aside class="lvc-contact__details">
				<h2 class="lvc-sec-title">Owner FAQs</h2>
				<div class="lvc-faq">
					<?php foreach ( $lvc_faqs as $f ) : ?>
						<details class="lvc-faq__item"><summary class="lvc-faq__q"><?php echo esc_html( $f['q'] ); ?></summary><p class="lvc-faq__a"><?php echo esc_html( $f['a'] ); ?></p></details>
					<?php endforeach; ?>
				</div>
			</aside>
		</div>
	</section>
</main>
<?php
get_footer();
