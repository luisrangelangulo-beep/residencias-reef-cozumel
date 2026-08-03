<?php
/**
 * Magazine index — collapse the paged URL space onto the hub.
 *
 * THE BUG
 * -------
 * /magazine/articles/ is the posts page. page-templates/magazine.php rendered
 * one featured post plus a 9-per-page grid, and inc/seo/schema.php noindexes
 * any is_paged() request (the shared $lvc_should_noindex rule, applied to both
 * wp_robots and AIOSEO). Measured live before the fix:
 *
 *   /magazine/articles/          indexable
 *   /magazine/articles/page/2/   noindex
 *
 * With 32 published posts that put 21 guides behind noindex URLs, leaving them
 * with no indexable internal link from the sitewide-linked hub. The noindex
 * rule is correct and stays — thin paged archives should not be indexed. The
 * mistake was paginating a small curated index at all.
 *
 * WHY THIS FILE EXISTS SEPARATELY FROM THE TEMPLATE FIX
 * ----------------------------------------------------
 * The grid runs on its own WP_Query, not the main query, so pre_get_posts
 * cannot reach it — that half of the fix lives in the template. But making the
 * grid complete does NOT remove /magazine/articles/page/2/. The main query
 * still resolves those URLs, home.php still includes the template, and the
 * template now renders the SAME complete grid at every page number: an
 * unbounded set of 200-status duplicates of the hub, noindexed and with no
 * canonical pointing home.
 *
 * That trap was found on punta-mita-vacation-rentals.com only by re-probing
 * page/2 AFTER unpaginating. The defect is created by the fix, so no amount of
 * checking beforehand surfaces it.
 *
 * Nothing links to these URLs now that the pagination control is gone, but
 * crawl history and external links still reach them, so they are redirected
 * rather than left to be discovered.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', function () {
	if ( is_admin() || ! is_home() || is_front_page() || ! is_paged() ) {
		return;
	}
	$posts_page = (int) get_option( 'page_for_posts' );
	$target     = $posts_page ? get_permalink( $posts_page ) : home_url( '/' );
	if ( ! $target ) {
		return;
	}
	wp_safe_redirect( $target, 301 );
	exit;
} );
