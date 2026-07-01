<?php
/**
 * Template Name: How It Works
 * Luxury Villa Theme Core — booking process explainer + FAQ. Brand-agnostic.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_steps = array(
	array( '01', 'Share your dates & group', 'Tell us your dates, guests, bedrooms, preferred destination, and must-haves like chef service, beach, or golf.' ),
	array( '02', 'We review villa fit', 'We compare villas by layout, location, service, and access — not just whether they appear available.' ),
	array( '03', 'Availability & rates confirmed', 'Rates, taxes, fees, minimum stays, and inclusions vary by villa and season. We verify the details before you decide.' ),
	array( '04', 'You get a focused shortlist', 'Instead of a long list, we narrow to villas that genuinely fit your trip.' ),
	array( '05', 'Confirm & plan the stay', 'Once you choose, we align booking details, transfers, groceries, chef preferences, and arrival.' ),
);

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
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">How It Works</p>
			<h1 class="lvc-hero__title">How to book a private <em>villa</em></h1>
			<p class="lvc-hero__sub">Booking a private villa isn&rsquo;t like booking a hotel — availability, rates, staff, chef service, and access all need confirming. Here&rsquo;s how we make it simple.</p>
			<div class="lvc-hero__cta"><a class="lvc-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Start an Inquiry</a><a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></a></div>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lvc-sec-header"><p class="lvc-eyebrow">The Process</p><h2 class="lvc-sec-title">From inquiry to <em>confirmed fit</em></h2></div>
		<div class="lvc-grid lvc-grid--3">
			<?php foreach ( $lvc_steps as $s ) : ?>
				<article class="lvc-help__step"><span class="lvc-help__num"><?php echo esc_html( $s[0] ); ?></span><h3><?php echo esc_html( $s[1] ); ?></h3><p><?php echo esc_html( $s[2] ); ?></p></article>
			<?php endforeach; ?>
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
