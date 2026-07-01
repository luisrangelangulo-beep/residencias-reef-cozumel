<?php
/**
 * Template Name: Villa Request
 * Luxury Villa Theme Core — concierge "villa match" page (advisory + inquiry form).
 * Brand-agnostic; set 'region' in theme-config.php for schema areaServed.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( function_exists( 'lvc_jsonld' ) ) {
	$lvc_service = array(
		'@context' => 'https://schema.org', '@type' => 'Service',
		'name' => 'Villa Matching & Concierge',
		'serviceType' => 'Luxury villa matching, direct booking support, concierge planning',
		'provider' => array( '@type' => 'Organization', 'name' => lvc_brand(), 'url' => home_url( '/' ) ),
	);
	if ( lvc_config( 'region' ) ) {
		$lvc_service['areaServed'] = lvc_config( 'region' );
	}
	lvc_jsonld( $lvc_service );
}
?>
<main class="lvc-page lvc-request">
	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">Villa Request &amp; Concierge</p>
			<h1 class="lvc-hero__title">Request a <em>villa match</em></h1>
			<p class="lvc-hero__sub">Tell us your dates, group size, and priorities. We compare villas across our destinations, verify availability, and guide you to the right fit — booked direct.</p>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt lvc-help">
		<div class="lvc-sec-header"><p class="lvc-eyebrow">How it works</p><h2 class="lvc-sec-title">From first inquiry to <em>confirmed fit</em></h2></div>
		<div class="lvc-grid lvc-grid--3">
			<div class="lvc-help__step"><span class="lvc-help__num">01</span><h3>Share your trip</h3><p>Dates, group size, and what matters most — beachfront, golf, chef service, or space for a large group.</p></div>
			<div class="lvc-help__step"><span class="lvc-help__num">02</span><h3>We shortlist &amp; advise</h3><p>A specialist hand-picks villas that fit and confirms availability, rates, and inclusions.</p></div>
			<div class="lvc-help__step"><span class="lvc-help__num">03</span><h3>Book direct</h3><p>Reserve directly with the villa team — no platform fees — with concierge support through check-out.</p></div>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lvc-contact__grid">
			<div class="lvc-contact__form">
				<p class="lvc-eyebrow">Villa Request</p>
				<h2 class="lvc-sec-title">Tell us what you&rsquo;re <em>looking for</em></h2>
				<?php get_template_part( 'template-parts/inquiry-form', null, array( 'submit_label' => 'Request a Villa Match' ) ); ?>
			</div>
			<aside class="lvc-contact__details">
				<h2 class="lvc-sec-title">Helpful to share</h2>
				<ul class="lvc-contact__list">
					<li><span>Dates</span>Travel window or flexible dates</li>
					<li><span>Group</span>Guests + bedrooms needed</li>
					<li><span>Destination</span>Preferred destination or area</li>
					<li><span>Priorities</span>Beach, golf, chef, privacy, celebrations</li>
				</ul>
			</aside>
		</div>
	</section>
</main>
<?php
get_footer();
