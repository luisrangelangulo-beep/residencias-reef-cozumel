<?php
/**
 * Luxury Villa Theme Core — shared template helpers.
 * ─────────────────────────────────────────────────────────────────────────
 * Small, brand-agnostic helpers used across templates. All read from
 * theme-config.php so nothing brand-specific is hardcoded in templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** URL of the property archive (e.g. /luxury-villas/). */
if ( ! function_exists( 'lvc_archive_url' ) ) {
	function lvc_archive_url() {
		$url = get_post_type_archive_link( lvc_config( 'cpt', 'villa' ) );
		return $url ?: home_url( '/' . trim( (string) lvc_config( 'cpt_archive_slug', 'luxury-villas' ), '/' ) . '/' );
	}
}

/** URL of a configured page by key (contact, request, about, how, owners, magazine). */
if ( ! function_exists( 'lvc_page_url' ) ) {
	function lvc_page_url( $key ) {
		$pages = (array) lvc_config( 'pages', array() );
		$slug  = isset( $pages[ $key ] ) ? $pages[ $key ] : $key;
		return home_url( '/' . trim( (string) $slug, '/' ) . '/' );
	}
}

/** Editable page hero values: standard WP fields first, hardcoded copy last. */
if ( ! function_exists( 'lvc_page_hero_title' ) ) {
	function lvc_page_hero_title( $default = '' ) {
		$page_id = get_queried_object_id();
		$title   = $page_id ? get_the_title( $page_id ) : '';
		return trim( (string) $title ) !== '' ? $title : $default;
	}
}

if ( ! function_exists( 'lvc_page_hero_intro' ) ) {
	function lvc_page_hero_intro( $default = '' ) {
		$page_id = get_queried_object_id();
		$excerpt = $page_id ? get_post_field( 'post_excerpt', $page_id ) : '';
		return trim( (string) $excerpt ) !== '' ? $excerpt : $default;
	}
}

if ( ! function_exists( 'lvc_page_hero_image' ) ) {
	function lvc_page_hero_image() {
		$page_id = get_queried_object_id();
		if ( ! $page_id ) {
			return '';
		}
		$image = get_the_post_thumbnail_url( $page_id, 'full' );
		if ( ! $image ) {
			$image = lvc_field( 'hero_image_url', $page_id, '' );
		}
		return $image;
	}
}

if ( ! function_exists( 'lvc_page_hero_style' ) ) {
	function lvc_page_hero_style() {
		$image = lvc_page_hero_image();
		return $image ? '--lvc-hero-img:url(\'' . esc_url( $image ) . '\')' : '';
	}
}

/** Renderable body copy from the current WordPress Page editor. */
if ( ! function_exists( 'lvc_page_body' ) ) {
	function lvc_page_body() {
		$page_id = get_queried_object_id();
		$content = $page_id ? get_post_field( 'post_content', $page_id ) : '';
		return trim( (string) $content ) !== '' ? apply_filters( 'the_content', $content ) : '';
	}
}

/** Filterable WhatsApp URL (empty if not configured). */
if ( ! function_exists( 'lvc_whatsapp_url' ) ) {
	function lvc_whatsapp_url() {
		return apply_filters( 'lvc_whatsapp_url', (string) lvc_config( 'whatsapp_url', '' ) );
	}
}

/**
 * Best-available image URL for a property — one pipeline for cards AND
 * heroes (portfolio pattern, per PMVR/Tulum/Republic).
 *
 * card (default): feature_image → featured image → hero_image → gallery.
 * hero: two-tier size guard over [hero_image, feature_image] — ≥1600px
 *       preferred (a hero-grade card image IS the hero), ≥1000px accepted,
 *       unknown width (0 = measurement failed) passes so a network hiccup
 *       never demotes a real photo — then featured image, then the first
 *       gallery URL that clears ≥1000.
 */
/**
 * URLs from a gallery field, which stores them as "url, url, url".
 *
 * A greedy /https?:\/\/[^\s"'<>]+/ does NOT exclude commas, so every URL but
 * the last came back with a trailing comma attached and 404'd — the card and
 * hero resolvers both shipped that bug. Split on the actual separators
 * instead of pattern-matching around them.
 */
if ( ! function_exists( 'lvc_image_list' ) ) {
	function lvc_image_list( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}
		// Same separators single-villas.php has always used — deliberately NOT
		// splitting on spaces, since a filename may contain a literal one.
		$urls = array();
		foreach ( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $value ) ) ) as $url ) {
			if ( preg_match( '#^https?://#i', $url ) ) {
				$urls[] = $url;
			}
		}
		return array_values( array_unique( $urls ) );
	}
}

if ( ! function_exists( 'lvc_property_image' ) ) {
	function lvc_property_image( $post_id, $size = 'large', $context = 'card' ) {
		$width_ok = static function ( $url, $min ) {
			if ( ! function_exists( 'lvc_remote_image_width' ) ) {
				return true;
			}
			$w = lvc_remote_image_width( $url );
			return 0 === $w || $w >= $min;
		};

		if ( 'hero' === $context ) {
			$candidates = array();
			foreach ( array( 'hero_image', 'feature_image' ) as $field ) {
				$u = trim( (string) get_post_meta( $post_id, $field, true ) );
				if ( '' !== $u ) {
					$candidates[] = $u;
				}
			}
			foreach ( array( 1600, 1000 ) as $min ) {
				foreach ( $candidates as $u ) {
					if ( $width_ok( $u, $min ) ) {
						return esc_url( $u );
					}
				}
			}
			$img = get_the_post_thumbnail_url( $post_id, $size );
			if ( $img ) {
				return esc_url( $img );
			}
			foreach ( array( 'gallery_slider', 'gallery_squares', 'gallery' ) as $field ) {
				foreach ( lvc_image_list( get_post_meta( $post_id, $field, true ) ) as $candidate ) {
					if ( $width_ok( $candidate, 1000 ) ) {
						return esc_url( $candidate );
					}
				}
			}
			return '';
		}

		/*
		 * Card: curated fields first. Without them the card image resolved to
		 * whichever URL happened to be first in the gallery — true for 99 of
		 * 150 villas — so cards swapped whenever a gallery was re-ordered or
		 * re-synced. The gallery fallback is kept last so nothing renders blank.
		 *
		 * Every candidate is checked against the CONFIRMED-DEAD cache (404/410,
		 * measured by lvc_remote_image_width). The hero path already rejected
		 * dead URLs via $width_ok, but cards did not, so ~30% of the grid
		 * rendered broken <img> tags pointing at gone files. Skipping a dead
		 * candidate lets the chain continue — a villa whose curated image is
		 * gone now falls through to a live gallery photo instead of nothing.
		 * Unknown/unmeasured URLs still pass, so a network hiccup never blanks
		 * a working card.
		 */
		$alive = static function ( $url ) {
			return ! ( function_exists( 'lvc_image_url_is_dead' ) && lvc_image_url_is_dead( $url ) );
		};

		$curated = trim( (string) get_post_meta( $post_id, 'feature_image', true ) );
		if ( $curated && $alive( $curated ) ) {
			return esc_url( $curated );
		}
		$img = get_the_post_thumbnail_url( $post_id, $size );
		if ( ! $img ) {
			$hero_fallback = trim( (string) get_post_meta( $post_id, 'hero_image', true ) );
			if ( $hero_fallback && $alive( $hero_fallback ) ) {
				$img = $hero_fallback;
			}
		}
		if ( ! $img ) {
			foreach ( array( 'gallery_squares', 'gallery_slider', 'gallery' ) as $field ) {
				// First LIVE gallery URL, not merely the first one.
				foreach ( lvc_image_list( get_post_meta( $post_id, $field, true ) ) as $candidate ) {
					if ( $alive( $candidate ) ) {
						$img = $candidate;
						break 2;
					}
				}
			}
		}
		return $img ? esc_url( $img ) : '';
	}
}

/*
 * Liveness verdicts are non-autoloaded options, so resolving a 30-card grid
 * one card at a time would add ~30 single-row queries per render. These two
 * helpers collect the candidate URLs a loop is about to ask about and prime
 * them in ONE query. Candidates come from post meta, which WP_Query has
 * already cached, so priming itself costs no extra queries.
 */
if ( ! function_exists( 'lvc_property_image_candidates' ) ) {
	function lvc_property_image_candidates( $post_id ) {
		$urls = array();
		foreach ( array( 'feature_image', 'hero_image' ) as $field ) {
			$u = trim( (string) get_post_meta( $post_id, $field, true ) );
			if ( '' !== $u ) {
				$urls[] = $u;
			}
		}
		// Only the first few gallery URLs: the resolver stops at the first live
		// one, and priming entire galleries would cost more than it saves.
		foreach ( array( 'gallery_squares', 'gallery_slider', 'gallery' ) as $field ) {
			$urls = array_merge( $urls, array_slice( lvc_image_list( get_post_meta( $post_id, $field, true ) ), 0, 3 ) );
		}
		return array_slice( array_values( array_unique( $urls ) ), 0, 5 );
	}
}

if ( ! function_exists( 'lvc_prime_image_liveness' ) ) {
	function lvc_prime_image_liveness( $post_ids ) {
		if ( ! function_exists( 'wp_prime_option_caches' ) ) {
			return;
		}
		$names = array();
		foreach ( (array) $post_ids as $pid ) {
			foreach ( lvc_property_image_candidates( (int) $pid ) as $url ) {
				$names[ 'lvc_imgw_' . md5( trim( $url ) ) ] = true;
			}
		}
		if ( $names ) {
			wp_prime_option_caches( array_keys( $names ) );
		}
	}
}

/*
 * Prime once per property loop, wherever it runs — the main archive query,
 * taxonomy pages, and the secondary WP_Querys behind the signature band,
 * related villas, and the homepage grid all pass through here.
 */
add_filter(
	'the_posts',
	function ( $posts, $query ) {
		if ( empty( $posts ) || count( $posts ) > 100 ) {
			return $posts;
		}
		// ID-only queries (villa index, counts) never render a card.
		if ( 'ids' === $query->get( 'fields' ) ) {
			return $posts;
		}
		$cpt   = (string) lvc_config( 'cpt', 'villas' );
		$types = (array) $query->get( 'post_type' );
		if ( ! in_array( $cpt, $types, true ) && ! $query->is_post_type_archive( $cpt ) && ! $query->is_tax() ) {
			return $posts;
		}
		lvc_prime_image_liveness( wp_list_pluck( $posts, 'ID' ) );
		return $posts;
	},
	10,
	2
);

/* ── Image CDN helpers — right-sized variants via Photon (i0.wp.com) ─────
 * Same pattern as THV/RMOF/Republic: free, no account, and Photon never
 * upscales, so HD originals pass through untouched while phones stop
 * downloading 2000px files for 400px card slots.
 */
if ( ! function_exists( 'lvc_cdn_img' ) ) {
	function lvc_cdn_img( $url, $width ) {
		$url = trim( (string) $url );
		if ( '' === $url || 0 !== strpos( $url, 'http' ) ) {
			return $url;
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return $url;
		}
		if ( preg_match( '/^i[0-3]\.wp\.com$/', $host ) ) {
			return add_query_arg( 'w', (int) $width, $url );
		}
		// Do not proxy remote R2/custom-CDN assets through Photon. Several
		// valid origin images return 404 after that rewrite, and CSS hero
		// backgrounds have no onerror fallback.
		$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( ! $site_host || strtolower( $host ) !== strtolower( $site_host ) ) {
			return $url;
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return 'https://i0.wp.com/' . $host . $path . '?w=' . (int) $width . '&ssl=1';
	}
}
if ( ! function_exists( 'lvc_cdn_srcset' ) ) {
	function lvc_cdn_srcset( $url, array $widths ) {
		$parts = array();
		foreach ( $widths as $w ) {
			$parts[] = lvc_cdn_img( $url, $w ) . ' ' . (int) $w . 'w';
		}
		return implode( ', ', $parts );
	}
}

/**
 * Legacy field-name aliases (canonical => legacy).
 *
 * The live property data was imported under the original RMOF generator schema
 * (`bedrooms`, `bathrooms`, `max_guests`, `property_description`,
 * `h1_property_title`), while theme-core templates, the Schema.org builder, and
 * the sheet-sync all use the newer canonical names (`bed_count`, `bath_count`,
 * `guests_max`, `property_descr`, `h1_title`). Until the existing villas are
 * re-synced to the canonical names, `lvc_field()` reads canonical-first and
 * falls back to the legacy name so cards, single templates, and schema populate
 * from either schema. Filterable so a fully-migrated brand can drop the shim.
 */
if ( ! function_exists( 'lvc_field_aliases' ) ) {
	function lvc_field_aliases() {
		return apply_filters( 'lvc_field_aliases', array(
			'h1_title'       => 'h1_property_title',
			'bed_count'      => 'bedrooms',
			'bath_count'     => 'bathrooms',
			'guests_max'     => 'max_guests',
			'property_descr' => 'property_description',
			'bedroom_desc'   => 'bedroom_description',
		) );
	}
}

/**
 * ACF field with a graceful fallback chain when the plugin or value is absent.
 * Reads the canonical field name first, then any legacy alias (see
 * lvc_field_aliases()). Safe to call even if ACF is not active.
 */
if ( ! function_exists( 'lvc_field' ) ) {
	function lvc_field( $name, $post_id = null, $default = '' ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}
		$value = get_field( $name, $post_id );
		if ( null === $value || '' === $value || array() === $value ) {
			$aliases = lvc_field_aliases();
			if ( isset( $aliases[ $name ] ) ) {
				$value = get_field( $aliases[ $name ], $post_id );
			}
		}
		return ( null === $value || '' === $value || array() === $value ) ? $default : $value;
	}
}

/**
 * Order a list of bedroom terms by their leading integer.
 *
 * Term names are strings ("10 Bedrooms"), so any name-ordered get_terms()
 * call renders the dropdown as 1, 10, 11 … 2, 3. Every filter bar that
 * offers a bedrooms select must pass its terms through this instead of
 * relying on orderby=name.
 */
if ( ! function_exists( 'lvc_sort_bedroom_terms' ) ) {
	function lvc_sort_bedroom_terms( $terms ) {
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return $terms;
		}
		usort(
			$terms,
			static function ( $a, $b ) {
				return (int) $a->name <=> (int) $b->name;
			}
		);
		return $terms;
	}
}

/** The active brand name (for headings, schema, email subjects). */
if ( ! function_exists( 'lvc_brand' ) ) {
	function lvc_brand() {
		return (string) lvc_config( 'brand_name', get_bloginfo( 'name' ) );
	}
}

/**
 * The most specific `area` term assigned to a property.
 *
 * Villas here are tagged at every level at once (e.g. a Soliman Bay villa
 * carries Riviera Maya + Tulum + Soliman Bay simultaneously, so its microarea
 * shows up on its own area-lander page). `get_the_terms()` does not guarantee
 * root-first-or-leaf-first order, so picking `[0]` can silently surface the
 * broadest term ("Riviera Maya") instead of the actual neighborhood — this
 * picks the term with the most ancestors instead, breaking ties by term_id
 * for a stable result.
 */
if ( ! function_exists( 'lvc_property_area_term' ) ) {
	function lvc_property_area_term( $post_id ) {
		$terms = get_the_terms( $post_id, 'area' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}
		$deepest       = null;
		$deepest_depth = -1;
		foreach ( $terms as $term ) {
			$depth = count( get_ancestors( $term->term_id, 'area' ) );
			if ( $depth > $deepest_depth || ( $depth === $deepest_depth && $deepest && $term->term_id < $deepest->term_id ) ) {
				$deepest       = $term;
				$deepest_depth = $depth;
			}
		}
		return $deepest;
	}
}
