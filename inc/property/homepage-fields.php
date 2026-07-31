<?php
/**
 * Legacy homepage options compatibility.
 *
 * The former ACF "Homepage" top-level screen duplicated the native Site
 * Content screen. Its saved values are migrated non-destructively by
 * inc/site-content.php and remain available as front-end fallbacks.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

