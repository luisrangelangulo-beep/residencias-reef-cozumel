<?php
/**
 * Template Name: Contact
 * Luxury Villa Theme Core — Contact page (inquiry form + details + FAQ).
 * Brand-agnostic; values from theme-config.php. Assign in WP → Page Attributes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_faqs = array(
	array( 'q' => 'How do I book a villa?', 'a' => 'Send your dates, group size, and what matters most. A specialist will shortlist villas in your preferred destination and confirm availability and rates before you commit.' ),
	array( 'q' => 'Do you charge platform or booking fees?', 'a' => 'No. You book direct with the villa team — there are no marketplace markups or platform fees.' ),
	array( 'q' => 'Are chef and staff included?', 'a' => 'It depends on the villa. Many include housekeeping and staff; chef service may be included or arranged. We confirm inclusions before you book.' ),
	array( 'q' => 'How quickly will you respond?', 'a' => 'We typically respond ' . lvc_config( 'response_time', 'within 24 hours' ) . '.' ),
);

get_header();

if ( function_exists( 'lvc_jsonld' ) ) {
	lvc_jsonld( array(
		'@context' => 'https://schema.org', '@type' => 'ContactPage',
		'name' => 'Contact ' . lvc_brand(), 'url' => get_permalink(),
		'about' => array( '@type' => 'Organization', 'name' => lvc_brand(), 'url' => home_url( '/' ), 'email' => lvc_config( 'support_email', '' ) ),
	) );
	$qas = array();
	foreach ( $lvc_faqs as $f ) {
		$qas[] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) );
	}
	lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qas ) );
}
?>
<main class="lvc-page lvc-contact">
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">Contact Us</p>
			<h1 class="lvc-hero__title">Contact <?php echo esc_html( lvc_brand() ); ?></h1>
			<p class="lvc-hero__sub">Reach our villa team directly. Send your dates and what you&rsquo;re looking for, or message us on WhatsApp for quick questions.</p>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lvc-contact__grid">
			<div class="lvc-contact__form">
				<p class="lvc-eyebrow">Villa Inquiry</p>
				<h2 class="lvc-sec-title">Start your <em>shortlist</em></h2>
				<?php get_template_part( 'template-parts/inquiry-form', null, array( 'submit_label' => 'Send Enquiry' ) ); ?>
			</div>
			<aside class="lvc-contact__details">
				<h2 class="lvc-sec-title">Reach us</h2>
				<ul class="lvc-contact__list">
					<?php if ( lvc_config( 'support_email' ) ) : ?>
						<li><span>Email</span><a href="mailto:<?php echo esc_attr( lvc_config( 'support_email' ) ); ?>"><?php echo esc_html( lvc_config( 'support_email' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( lvc_whatsapp_url() ) : ?>
						<li><span>WhatsApp</span><a href="<?php echo esc_url( lvc_whatsapp_url() ); ?>" target="_blank" rel="noopener">Message us</a></li>
					<?php endif; ?>
					<?php if ( lvc_config( 'phone' ) ) : ?>
						<li><span>Phone</span><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', lvc_config( 'phone' ) ) ); ?>"><?php echo esc_html( lvc_config( 'phone' ) ); ?></a></li>
					<?php endif; ?>
					<li><span>Response</span><?php echo esc_html( ucfirst( lvc_config( 'response_time', 'within 24 hours' ) ) ); ?></li>
				</ul>
			</aside>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt">
		<div class="lvc-sec-header"><p class="lvc-eyebrow">Common Questions</p><h2 class="lvc-sec-title">Contact <em>FAQs</em></h2></div>
		<div class="lvc-faq">
			<?php foreach ( $lvc_faqs as $f ) : ?>
				<details class="lvc-faq__item"><summary class="lvc-faq__q"><?php echo esc_html( $f['q'] ); ?></summary><p class="lvc-faq__a"><?php echo esc_html( $f['a'] ); ?></p></details>
			<?php endforeach; ?>
		</div>
	</section>
</main>
<?php
get_footer();
