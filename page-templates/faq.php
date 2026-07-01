<?php
/**
 * Template Name: FAQ
 * Luxury Villa Theme Core — standalone FAQ page (accordion + FAQPage schema).
 * Brand-agnostic default questions; customise the $lvc_faqs array per site.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_faqs = array(
	array( 'q' => 'How do I book a villa?', 'a' => 'Send your dates, group size, and priorities. A specialist shortlists villas that fit and confirms availability, rates, and inclusions before you commit — booked direct with the villa team.' ),
	array( 'q' => 'Do you charge platform or booking fees?', 'a' => 'No. You book direct — there are no marketplace markups or platform fees.' ),
	array( 'q' => 'What is included — staff, chef, housekeeping?', 'a' => 'It varies by villa. Many include housekeeping and staff; chef service may be included or arranged on request. We confirm inclusions before booking.' ),
	array( 'q' => 'What are the rates, taxes, and minimum stays?', 'a' => 'Rates, taxes, fees, and minimum stays vary by villa and season — holiday weeks carry longer minimums and premium rates. We verify the exact figures before you decide.' ),
	array( 'q' => 'How do payments and deposits work?', 'a' => 'Terms are confirmed in writing before booking and handled directly with the villa team. Specifics vary by property.' ),
	array( 'q' => 'What is the cancellation policy?', 'a' => 'Cancellation terms vary by villa and season and are shared in full before you book.' ),
	array( 'q' => 'How quickly will you respond?', 'a' => 'We typically respond ' . lvc_config( 'response_time', 'within 24 hours' ) . '.' ),
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
<main class="lvc-page lvc-faq-page">
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">FAQ</p>
			<h1 class="lvc-hero__title">Frequently asked <em>questions</em></h1>
			<p class="lvc-hero__sub">Everything you need to know about booking a private villa with <?php echo esc_html( lvc_brand() ); ?>.</p>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lvc-faq">
			<?php foreach ( $lvc_faqs as $f ) : ?>
				<details class="lvc-faq__item"><summary class="lvc-faq__q"><?php echo esc_html( $f['q'] ); ?></summary><p class="lvc-faq__a"><?php echo esc_html( $f['a'] ); ?></p></details>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt lvc-cta">
		<div class="lvc-cta__inner">
			<h2 class="lvc-sec-title">Still have a question?</h2>
			<p>Send your dates and what you&rsquo;re looking for — we&rsquo;ll help.</p>
			<div class="lvc-hero__cta"><a class="lvc-btn" href="<?php echo esc_url( lvc_page_url( 'contact' ) ); ?>">Contact Us</a></div>
		</div>
	</section>
</main>
<?php
get_footer();
