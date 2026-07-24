<?php
/**
 * Luxury Villa Theme Core — JSON-LD schema + SEO hygiene.
 * ─────────────────────────────────────────────────────────────────────────
 * Emits typed schema (VacationRental, CollectionPage/ItemList, Article,
 * BreadcrumbList, FAQPage), suppresses Rank Math's own schema so they don't
 * collide (when theme_owns_schema), and noindexes thin/paged term archives.
 *
 * Templates call lvc_schema_property()/lvc_schema_collection()/lvc_schema_article().
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Echo a JSON-LD <script> block. */
if ( ! function_exists( 'lvc_jsonld' ) ) {
	function lvc_jsonld( array $data ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}

/** BreadcrumbList from a list of [name => url] pairs (last item = current, url optional). */
if ( ! function_exists( 'lvc_schema_breadcrumb' ) ) {
	function lvc_schema_breadcrumb( array $items ) {
		$list = array();
		$i    = 0;
		foreach ( $items as $name => $url ) {
			$i++;
			$entry = array( '@type' => 'ListItem', 'position' => $i, 'name' => $name );
			if ( $url ) {
				$entry['item'] = $url;
			}
			$list[] = $entry;
		}
		return array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list );
	}
}

/** Single property: VacationRental + Accommodation + breadcrumb. */
if ( ! function_exists( 'lvc_schema_property' ) ) {
	function lvc_schema_property( $post_id ) {
		$beds    = lvc_field( 'bed_count', $post_id );
		$guests  = lvc_field( 'guests_max', $post_id );
		$tier    = lvc_field( 'from_rate_tier', $post_id );
		$descr   = lvc_field( 'property_descr', $post_id );
		$aliases = lvc_field( 'villa_aliases', $post_id );
		$area    = function_exists( 'lvc_property_area_term' ) ? lvc_property_area_term( $post_id ) : null;
		$dest    = get_the_terms( $post_id, 'destination' );
		$area_n  = $area ? $area->name : '';
		$dest_n  = ( $dest && ! is_wp_error( $dest ) ) ? $dest[0]->name : '';

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => array( 'VacationRental', 'Accommodation' ),
			'name'     => get_the_title( $post_id ),
			'url'      => get_permalink( $post_id ),
		);
		if ( $descr ) {
			$schema['description'] = wp_trim_words( wp_strip_all_tags( $descr ), 50, '' );
		}
		if ( $aliases ) {
			$alt = array_values( array_filter( array_map( 'trim', explode( ',', $aliases ) ) ) );
			if ( $alt ) {
				$schema['alternateName'] = ( count( $alt ) === 1 ) ? $alt[0] : $alt;
			}
		}
		$img = lvc_property_image( $post_id, 'full' );
		if ( $img ) {
			$schema['image'] = $img;
		}
		if ( $beds ) {
			$schema['numberOfRooms'] = (int) $beds;
		}
		if ( $guests ) {
			$schema['occupancy'] = array( '@type' => 'QuantitativeValue', 'maxValue' => (int) $guests );
		}
		if ( $tier && function_exists( 'lvc_price_range' ) ) {
			$schema['priceRange'] = lvc_price_range( $tier );
		}
		// amenityFeature as typed objects (NOT bare strings).
		$amen = get_the_terms( $post_id, 'amenity' );
		if ( $amen && ! is_wp_error( $amen ) ) {
			$feat = array();
			foreach ( $amen as $a ) {
				$feat[] = array( '@type' => 'LocationFeatureSpecification', 'name' => $a->name, 'value' => true );
			}
			$schema['amenityFeature'] = $feat;
		}
		if ( $area_n || $dest_n ) {
			$addr = array( '@type' => 'PostalAddress' );
			if ( $area_n ) {
				$addr['addressLocality'] = $area_n;
			}
			if ( $dest_n ) {
				$addr['addressRegion'] = $dest_n;
			}
			$schema['address'] = $addr;
		}
		lvc_jsonld( $schema );

		$crumbs = array( lvc_brand() => home_url( '/' ), lvc_config( 'cpt_plural', 'Villas' ) => lvc_archive_url(), get_the_title( $post_id ) => '' );
		lvc_jsonld( lvc_schema_breadcrumb( $crumbs ) );

		// FAQ schema from flat faq_q1..faq_a4 (1:1 with the generator).
		$qas = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$q = lvc_field( 'faq_q' . $i, $post_id );
			$a = lvc_field( 'faq_a' . $i, $post_id );
			if ( $q && $a ) {
				$qas[] = array( '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $a ) ) );
			}
		}
		if ( count( $qas ) >= 2 ) {
			lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qas ) );
		}
	}
}

/** Taxonomy / archive: CollectionPage + ItemList of the current query. */
if ( ! function_exists( 'lvc_schema_collection' ) ) {
	function lvc_schema_collection() {
		global $wp_query;
		$items = array();
		$pos   = 0;
		if ( ! empty( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $p ) {
				$pos++;
				$items[] = array( '@type' => 'ListItem', 'position' => $pos, 'url' => get_permalink( $p ) );
			}
		}
		$obj  = get_queried_object();
		$name = $obj instanceof WP_Term ? $obj->name : lvc_config( 'cpt_plural', 'Villas' );
		lvc_jsonld( array(
			'@context'        => 'https://schema.org',
			'@type'           => 'CollectionPage',
			'name'            => $name,
			'mainEntity'      => array( '@type' => 'ItemList', 'numberOfItems' => count( $items ), 'itemListElement' => $items ),
		) );
	}
}

/** Magazine article: Article + breadcrumb. */
if ( ! function_exists( 'lvc_schema_article' ) ) {
	function lvc_schema_article( $post_id ) {
		$schema = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => get_the_title( $post_id ),
			'datePublished' => get_the_date( 'c', $post_id ),
			'dateModified'  => get_the_modified_date( 'c', $post_id ),
			'author'        => array( '@type' => 'Organization', 'name' => lvc_brand() ),
			'publisher'     => array( '@type' => 'Organization', 'name' => lvc_brand() ),
			'mainEntityOfPage' => get_permalink( $post_id ),
		);
		$img = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( $img ) {
			$schema['image'] = $img;
		}
		lvc_jsonld( $schema );
	}
}

/* ── SEO-plugin de-duplication (AIOSEO): let the theme own schema. ─────────────────── */
if ( lvc_config( 'theme_owns_schema', true ) ) {
	// True on the pages where the theme emits its own rich JSON-LD.
	$lvc_theme_owns = function () {
		return is_singular( lvc_config( 'cpt', 'villas' ) )
			|| is_tax( array_keys( (array) lvc_config( 'taxonomies', array() ) ) )
			|| is_post_type_archive( lvc_config( 'cpt', 'villas' ) );
	};
	// THIS SITE RUNS AIOSEO — suppress its schema on theme-owned pages so the
	// two don't both emit (duplicate JSON-LD). Homepage Organization/WebSite,
	// pages and magazine articles are left to AIOSEO.
	add_filter( 'aioseo_schema_output', function ( $output ) use ( $lvc_theme_owns ) {
		return $lvc_theme_owns() ? array() : $output;
	}, 99 );
	// No-op safety net if the SEO plugin is ever swapped back to Rank Math.
	add_filter( 'rank_math/json_ld', function ( $data ) use ( $lvc_theme_owns ) {
		if ( $lvc_theme_owns() ) {
			return array();
		}
		// Enrich the single authoritative Organization node (Rank Math's) in place
		// with the contact/area data the theme config holds — no duplicate node.
		// All additions guard on non-empty config, so an unfilled config is a no-op.
		$org_phone  = (string) lvc_config( 'phone', '' );
		$org_region = (string) lvc_config( 'region', '' );
		$org_same   = array_values( array_filter( array_map( 'strval', (array) lvc_config( 'social_profiles', array() ) ) ) );
		foreach ( $data as $key => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$type   = $node['@type'] ?? '';
			$is_org = ( 'Organization' === $type ) || ( is_array( $type ) && in_array( 'Organization', $type, true ) );
			if ( ! $is_org ) {
				continue;
			}
			if ( '' !== $org_phone && empty( $node['telephone'] ) ) {
				$data[ $key ]['telephone'] = $org_phone;
			}
			if ( '' !== $org_region && empty( $node['areaServed'] ) ) {
				$data[ $key ]['areaServed'] = $org_region;
			}
			if ( ! empty( $org_same ) && empty( $node['sameAs'] ) ) {
				$data[ $key ]['sameAs'] = $org_same;
			}
			// Rank Math can emit this Organization as a stub (name only, no url/logo)
			// even with a Knowledge Graph logo configured, which weakens the brand
			// entity. Fill the two missing identity properties from data the site
			// already owns. Guarded on empty: a no-op wherever Rank Math supplies them.
			if ( empty( $node['url'] ) ) {
				$data[ $key ]['url'] = home_url( '/' );
			}
			if ( empty( $node['logo'] ) ) {
				$org_logo = '';
				if ( class_exists( 'RankMath\Helper' ) ) {
					$org_logo = (string) RankMath\Helper::get_settings( 'titles.knowledgegraph_logo' );
				}
				if ( '' === $org_logo ) {
					$org_logo_id = get_theme_mod( 'custom_logo' );
					$org_logo    = $org_logo_id ? (string) wp_get_attachment_image_url( $org_logo_id, 'full' ) : '';
				}
				if ( '' !== $org_logo ) {
					$data[ $key ]['logo'] = array(
						'@type'      => 'ImageObject',
						'url'        => $org_logo,
						'contentUrl' => $org_logo,
					);
				}
			}
		}
		return $data;
	}, 99 );
}

/* ── Thin-content + paged noindex hygiene. ──────────────────────────────── */
if ( lvc_config( 'noindex_thin_terms', true ) ) {
	/**
	 * Shared rule: should the current query be noindexed?
	 *   - paginated archives (page 2+ is a near-copy of page 1);
	 *   - a villa-taxonomy term holding fewer than min_index_count villas;
	 *   - thin, near-duplicate listing archives: bedroom-count pages plus the
	 *     default category/tag archives.
	 * Kept in one place so the core (wp_robots) and AIOSEO outputs can never
	 * drift apart — they did, and only the last rule was reaching AIOSEO.
	 */
	$lvc_should_noindex = function () {
		if ( is_paged() ) {
			return true;
		}
		$taxes = array_keys( (array) lvc_config( 'taxonomies', array() ) );
		if ( ! empty( $taxes ) && is_tax( $taxes ) ) {
			$obj = get_queried_object();
			if ( $obj instanceof WP_Term && (int) $obj->count < (int) lvc_config( 'min_index_count', 1 ) ) {
				return true;
			}
		}
		return is_tax( 'bedrooms' ) || is_category() || is_tag();
	};

	// noindex, FOLLOW throughout — link equity still flows through to the
	// villas; this only keeps crawl budget off the near-duplicate archives.
	add_filter( 'wp_robots', function ( $robots ) use ( $lvc_should_noindex ) {
		if ( $lvc_should_noindex() ) {
			$robots['noindex'] = true;
			unset( $robots['index'] );
		}
		return $robots;
	}, 99 );

	// AIOSEO disables core wp_robots and owns the robots meta output, so the
	// rule above only takes effect when ALSO applied through AIOSEO's own
	// filter. 'noindex' => 'noindex' noindexes; leaving 'nofollow' empty
	// keeps FOLLOW.
	add_filter( 'aioseo_robots_meta', function ( $attributes ) use ( $lvc_should_noindex ) {
		if ( $lvc_should_noindex() ) {
			$attributes['noindex'] = 'noindex';
		}
		return (array) $attributes;
	} );
}

/**
 * Augment AIOSEO's existing Organization node with facts its UI can't set:
 * the legal entity behind the trading name, and the areas served. AIOSEO already
 * emits ONE Organization node (Search Appearance → Knowledge Graph), so we add
 * to it rather than output a second, duplicate node. foundingDate is set
 * natively in AIOSEO; we only fill it here as a fallback. No-op if AIOSEO is
 * inactive or emits no Organization node.
 */
add_filter( 'aioseo_schema_output', function ( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}
	foreach ( $graph as &$node ) {
		if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
			continue;
		}
		$type   = $node['@type'];
		$is_org = ( is_string( $type ) && false !== stripos( $type, 'Organization' ) )
			|| ( is_array( $type ) && in_array( 'Organization', $type, true ) );
		if ( ! $is_org ) {
			continue;
		}
		if ( empty( $node['legalName'] ) ) {
			$node['legalName'] = 'Retreats Luxury Oceanfront Rentals';
		}
		if ( empty( $node['foundingDate'] ) ) {
			$node['foundingDate'] = '2012-01-01';
		}
		if ( empty( $node['areaServed'] ) ) {
			$node['areaServed'] = array( 'Riviera Maya', 'Cozumel' );
		}
	}
	unset( $node );
	return $graph;
}, 20 );
