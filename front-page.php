<?php
/**
 * Front page â€” Residencias Reef Cozumel.
 * Conversion-focused direct booking homepage.
 *
 * Positions Residencias Reef/Cozumel as the trust hook while creating a clear
 * upgrade path into higher-value Riviera Maya and Tulum private villa stays.
 *
 * @package ResidenciasReefCozumel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_cpt  = lvc_config( 'cpt', 'villas' );
$lvc_req  = lvc_page_url( 'request' );
$lvc_arch = lvc_archive_url();
$lvc_wa   = lvc_whatsapp_url();

if ( ! function_exists( 'lvc_area_image' ) ) {
	function lvc_area_image( $slug ) {
		if ( function_exists( 'lvc_area_card_image' ) ) {
			return lvc_area_card_image( $slug );
		}

		$term = get_term_by( 'slug', $slug, 'area' );
		if ( $term instanceof WP_Term ) {
			foreach ( array( 'area_hero_image_url', 'hero_image_url' ) as $field ) {
				$term_image = lvc_field( $field, 'area_' . $term->term_id, '' );
				if ( ! $term_image ) {
					$term_image = lvc_field( $field, 'term_' . $term->term_id, '' );
				}
				if ( $term_image ) {
					return $term_image;
				}
			}
		}
		$q = new WP_Query( array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array( array( 'taxonomy' => 'area', 'field' => 'slug', 'terms' => $slug ) ),
		) );
		$img = $q->have_posts() ? lvc_property_image( $q->posts[0], 'large' ) : '';
		wp_reset_postdata();
		return $img;
	}
}

if ( ! function_exists( 'lvc_home_term_image' ) ) {
	function lvc_home_term_image( $taxonomy, $slug ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term instanceof WP_Term ) {
			foreach ( array( $taxonomy . '_feature_image_url', 'feature_image_url', $taxonomy . '_hero_image_url', 'hero_image_url' ) as $field ) {
				$img = lvc_field( $field, $taxonomy . '_' . $term->term_id, '' );
				if ( $img ) {
					return $img;
				}
			}
		}

		$q = new WP_Query( array(
			'post_type'      => lvc_config( 'cpt', 'villas' ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slug ) ),
		) );
		$img = $q->have_posts() ? lvc_property_image( $q->posts[0], 'large' ) : '';
		wp_reset_postdata();
		return $img;
	}
}

if ( ! function_exists( 'lvc_home_term_description' ) ) {
	function lvc_home_term_description( $taxonomy, $slug, $fallback = '' ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term instanceof WP_Term ) {
			$description = trim( wp_strip_all_tags( term_description( $term->term_id, $taxonomy ) ) );
			if ( $description ) {
				return wp_trim_words( $description, 28, 'â€¦' );
			}
		}
		return $fallback;
	}
}

if ( ! function_exists( 'lvc_home_rows' ) ) {
	function lvc_home_rows( $key, $defaults, $columns = 1 ) {
		$value = trim( lvc_site_content( $key, '' ) );
		if ( '' === $value ) {
			return $defaults;
		}
		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$row = array_map( 'trim', explode( '|', $line, $columns ) );
			$row = array_pad( $row, $columns, '' );
			$rows[] = $row;
		}
		return $rows ? $rows : $defaults;
	}
}

$lvc_home_page_id  = (int) get_option( 'page_on_front' );
$lvc_home_hero_img = lvc_site_content( 'home_hero_image' );
if ( ! $lvc_home_hero_img && $lvc_home_page_id ) {
	$lvc_home_hero_img = lvc_field( 'hero_image_url', $lvc_home_page_id, '' );
}
if ( ! $lvc_home_hero_img && $lvc_home_page_id ) {
	$lvc_home_hero_img = get_the_post_thumbnail_url( $lvc_home_page_id, 'full' );
}
if ( ! $lvc_home_hero_img && $lvc_home_page_id ) {
	$lvc_home_hero_img = lvc_field( 'feature_image_url', $lvc_home_page_id, '' );
}
if ( ! $lvc_home_hero_img ) {
	$lvc_home_hero_img = lvc_field( 'home_hero_image_url', 'option' );
}
if ( ! $lvc_home_hero_img ) {
	$lvc_home_hero_term = get_term_by( 'slug', 'riviera-maya', 'area' );
	// ACF needs a term reference string; passing the WP_Term object returns null,
	// so this fallback never fired.
	$lvc_home_hero_img  = $lvc_home_hero_term ? lvc_field( 'hero_image_url', 'term_' . $lvc_home_hero_term->term_id ) : '';
}
if ( ! $lvc_home_hero_img ) {
	$lvc_home_hero_img = lvc_area_image( 'riviera-maya' );
}

$lvc_home_hero_kicker     = lvc_site_content( 'home_hero_kicker', lvc_brand() );
$lvc_home_hero_title      = lvc_site_content( 'home_hero_title', 'Residencias Reef Cozumel Beachfront Condos' );
$lvc_home_hero_accent     = lvc_site_content( 'home_hero_accent', '& Handpicked Riviera Maya Villas' );
$lvc_home_hero_intro      = lvc_site_content( 'home_hero_intro', 'Start with relaxed beachfront condos in Cozumel, or let us match your group with private villas in Tulum, Soliman Bay, Tulum Town, Playa del Carmen, Akumal, Puerto Aventuras, and beyond.' );
$lvc_home_primary_label   = lvc_site_content( 'home_primary_label', 'Submit Your Villa Request' );
$lvc_home_secondary_label = lvc_site_content( 'home_secondary_label', 'Browse Villas & Condos' );
$lvc_home_featured_title  = lvc_site_content( 'home_featured_title', 'Cozumel condos & selected Riviera Maya villas' );
$lvc_home_featured_intro  = lvc_site_content( 'home_featured_intro', 'Start with a focused selection, then use the full villa archive or an area page to continue browsing.' );
$lvc_home_match_title     = lvc_site_content( 'home_match_title', 'Tell us your dates. We will shortlist the right stay.' );
$lvc_home_match_intro     = lvc_site_content( 'home_match_intro', 'Share your group size, dates, preferred area, and service needs. We will help decide whether a simple Cozumel condo or a higher-service private villa makes more sense.' );
$lvc_home_match_label     = lvc_site_content( 'home_match_label', 'Get Villa Matches' );
$lvc_home_proof_points    = lvc_home_rows( 'home_proof_points', array(
	array( 'Cozumel condos', 'for simple beachfront stays' ),
	array( 'Private villas', 'for families & groups' ),
	array( 'Tulum areas', 'matched by trip style' ),
	array( 'Chef, transfers & tours', 'on request' ),
), 2 );
$lvc_home_position_title  = lvc_site_content( 'home_position_title', 'Start with Cozumel. Upgrade into the right Riviera Maya villa.' );
$lvc_home_position_intro  = lvc_site_content( 'home_position_intro', 'Residencias Reef Cozumel is a useful entry point for couples, divers, and smaller groups who want a relaxed beachfront condo. But many travelers need more space, service, privacy, or a better location for a family trip, celebration, retreat, or luxury stay.' );
$lvc_home_position_points = lvc_home_rows( 'home_position_points', array(
	array( 'Condos for simpler Cozumel stays with beach access and practical nightly rates.' ),
	array( 'Private villas for larger groups, chef service, private pools, events, and higher-service trips.' ),
	array( 'Clear guidance across Cozumel, Tulum, Soliman Bay, Tulum Town, Playa del Carmen, Akumal, and Puerto Aventuras.' ),
) );
$lvc_home_paths_title     = lvc_site_content( 'home_paths_title', 'Choose the stay that fits the real trip' );
$lvc_home_paths_intro     = lvc_site_content( 'home_paths_intro', 'A beachfront condo can be perfect for a low-friction Cozumel stay. A private villa is usually the better fit when the group, budget, and service expectations are higher.' );
$lvc_home_paths_cards     = lvc_home_rows( 'home_paths_cards', array(
	array( 'Cozumel Hook', 'Residencias Reef condos', 'Best for couples, small families, divers, and guests who want beach access, a kitchen, pool access, and a quieter island base without paying for a full private villa.', 'View Cozumel Stays' ),
	array( 'Money Path', 'Private Riviera Maya villas', 'Better for families, larger groups, celebrations, retreats, and guests who want private pools, more bedrooms, chef service, staff, and a more exclusive setting.', 'Request Villa Matches' ),
), 4 );
$lvc_home_upgrade_title   = lvc_site_content( 'home_upgrade_title', 'When a private villa is the better fit' );
$lvc_home_upgrade_intro   = lvc_site_content( 'home_upgrade_intro', 'Condos are easy and affordable. Villas are where space, privacy, service, and trip value become much stronger.' );
$lvc_home_upgrade_cards   = lvc_home_rows( 'home_upgrade_cards', array(
	array( '8+ Guests', 'More bedrooms and privacy', 'Groups usually need larger layouts, multiple living areas, private pools, and fewer shared spaces.' ),
	array( 'Chef Service', 'Meals become part of the stay', 'Private villas work better for breakfast service, celebrations, dinners, groceries, and custom dining.' ),
	array( 'Special Occasions', 'Better for celebrations', 'Birthdays, family reunions, retreats, and milestone trips need a property that supports the full experience.' ),
), 3 );
$lvc_home_area_title      = lvc_site_content( 'home_area_title', 'Choose the right Riviera Maya area' );
$lvc_home_area_intro      = lvc_site_content( 'home_area_intro', 'The right villa starts with the right location. These are the areas most guests compare first.' );
$lvc_home_collection_title = lvc_site_content( 'home_collection_title', 'High-intent villa collections' );
$lvc_home_collection_intro = lvc_site_content( 'home_collection_intro', 'These are the commercial pages guests use when they already know the kind of trip they want.' );
$lvc_home_tulum_title      = lvc_site_content( 'home_tulum_title', 'Tulum is not one market: match the area to the trip' );
$lvc_home_tulum_intro      = lvc_site_content( 'home_tulum_intro', 'For higher-value villa inquiries, the most important question is often whether the group should stay beachfront, close to restaurants, in a quieter bay, or in town.' );
$lvc_home_compare_title    = lvc_site_content( 'home_compare_title', 'Cozumel condo or private villa?' );
$lvc_home_compare_intro    = lvc_site_content( 'home_compare_intro', 'This comparison helps guests self-select, which protects the low-cost condo traffic while pushing qualified groups toward better villa inquiries.' );
$lvc_home_compare_condo_title = lvc_site_content( 'home_compare_condo_title', 'Choose a Cozumel condo if...' );
$lvc_home_compare_condo_points = lvc_home_rows( 'home_compare_condo_points', array(
	array( 'You are a couple, small family, or diving-focused group.' ),
	array( 'You want a simple beachfront base at a lower nightly rate.' ),
	array( 'You do not need chef service, a large private pool, or full villa staff.' ),
) );
$lvc_home_compare_villa_title = lvc_site_content( 'home_compare_villa_title', 'Choose a private villa if...' );
$lvc_home_compare_villa_points = lvc_home_rows( 'home_compare_villa_points', array(
	array( 'You need more bedrooms, privacy, and living space.' ),
	array( 'You want chef service, staff, groceries, transfers, or celebration planning.' ),
	array( 'Your group is comparing Tulum, Soliman Bay, Akumal, Playa del Carmen, or Puerto Aventuras.' ),
) );
$lvc_home_steps_title      = lvc_site_content( 'home_steps_title', 'A simpler way to book a private villa' );
$lvc_home_steps            = lvc_home_rows( 'home_steps', array(
	array( 'Share your trip details', 'Tell us your dates, group size, bedroom needs, preferred area, and service needs.' ),
	array( 'Review matched villas', 'We help compare realistic options based on location, layout, service level, privacy, and fit.' ),
	array( 'Plan the stay', 'Concierge planning can include airport transfers, private chef, groceries, tours, spa, diving, and activities.' ),
), 2 );
$lvc_home_concierge_title  = lvc_site_content( 'home_concierge_title', 'Beyond the villa: complete stay planning' );
$lvc_home_concierge_intro  = lvc_site_content( 'home_concierge_intro', 'Luxury villa trips work best when the details are handled before arrival.' );
$lvc_home_concierge_cards  = lvc_home_rows( 'home_concierge_cards', array(
	array( 'Airport Transfers', 'Private transportation from CancÃºn International Airport and Cozumel arrival points.' ),
	array( 'Private Chef', 'In-villa dining, celebrations, breakfast service, and group meals.' ),
	array( 'Diving & Snorkeling', 'Cozumel reefs, cenotes, Riviera Maya snorkeling, and Caribbean boat days.' ),
	array( 'Tours & Activities', 'Mayan ruins, beach clubs, fishing, cenotes, ATVs, and family activities.' ),
	array( 'Spa & Wellness', 'In-villa massage, wellness sessions, yoga, and relaxation services.' ),
), 2 );
$lvc_home_final_title      = lvc_site_content( 'home_final_title', 'Tell us what your group needs. We will help narrow the search.' );
$lvc_home_final_intro      = lvc_site_content( 'home_final_intro', 'Share your dates, preferred area, group size, and villa priorities. We will help identify whether a Cozumel condo or a stronger Riviera Maya villa option makes the most sense.' );
$lvc_home_final_primary_label = lvc_site_content( 'home_final_primary_label', 'Request Villa Matches' );
$lvc_home_final_secondary_label = lvc_site_content( 'home_final_secondary_label', 'Chat on WhatsApp' );

$lvc_area_cards = array(
	array( 'Cozumel', 'cozumel', '/cozumel/', 'Residencias Reef condos and island stays for divers, couples, and guests who want a relaxed beachfront base.' ),
	array( 'Tulum', 'tulum', '/tulum-villa-rentals/', 'Private villas across Tulum Beach, Soliman Bay, Tankah Bay, Tulum Town, Aldea Zama, La Veleta, and Sian Ka\'an.' ),
	array( 'Playa del Carmen', 'playa-del-carmen', '/playa-del-carmen/', 'Beachfront and Playacar villas close to dining, beach clubs, nightlife, shopping, and easy arrival logistics.' ),
	array( 'Akumal', 'akumal', '/akumal/', 'Bayfront and beachfront villas for families, snorkeling trips, quieter beach stays, and multi-generational groups.' ),
);

$lvc_tulum_areas = array(
	array( 'Soliman Bay', 'soliman-bay', '/soliman-bay/', 'Quiet beachfront villas north of Tulum, often chosen for family trips, calm water, chef service, and more privacy than the main beach zone.' ),
	array( 'Tulum Beach Zone', 'tulum-beach-zone', '/tulum-beach-zone-villas/', 'For guests who want the restaurants, beach clubs, wellness scene, and boutique-hotel atmosphere close by.' ),
	array( 'Tulum Town', 'town-jungle', '/tulum-town-jungle-villas/', 'Better value, easier logistics, restaurants, nightlife access, and more practical options for longer stays or flexible groups.' ),
	array( 'Tankah Bay', 'tankah-bay-riviera-maya', '/tankah-bay/', 'Beachfront and bayfront villas between Tulum and Akumal, with a quieter residential feel and good access to snorkeling and cenotes.' ),
	array( 'Aldea Zama', 'aldea-zama', '/tulum-villa-rentals/', 'Modern private villas between Tulum Town and the beach road, often a good fit for groups that want style and convenience.' ),
	array( 'La Veleta', 'la-veleta', '/tulum-villa-rentals/', 'Contemporary villas and private homes with strong value for groups that prefer restaurants, nightlife, and town access.' ),
);

$lvc_collection_filters = array(
	array( 'All Villas', $lvc_arch ),
	array( 'Cozumel', home_url( '/cozumel/' ) ),
	array( 'Tulum', home_url( '/tulum-villa-rÛny¶‰ËkºwµçxØÕÉ•´íµ…É¥¸èÀ…ÕÑ¼€È¸ÉÉ•´íµ…àµİ¥‘Ñ èÄÀĞÁÁáô¹±Øµ™¥±Ñ•ÈµÁ¥±±í‘¥ÍÁ±…äé¥¹±¥¹”µ™±•àí…±¥¸µ¥Ñ•µÌé•¹Ñ•Èí©ÕÍÑ¥™äµ½¹Ñ•¹Ğé•¹Ñ•Èí‰½É‘•ÈèÅÁàÍ½±¥Ù…È ´µ±ÙŒµ‰½É‘•È¤í‰…­É½Õ¹éÉ‰„ ÈÔÔ°ÈÔÔ°ÈÔÔ°¸ÀÈÔ¤í½±½ÈéÙ…È ´µ±ÙŒµÍ½™Ğ¤…¥µÁ½ÉÑ…¹ĞíÁ…‘‘¥¹œè¸ØÉÉ•´€¸åÉ•´í™½¹ĞµÍ¥é”è¸ÜáÉ•´í±•ÑÑ•ÈµÍÁ…¥¹œè¸Àá•´íÑ•áĞµÑÉ…¹Í™½É´éÕÁÁ•É…Í•ô¹±Øµ™¥±Ñ•ÈµÁ¥±°é¡½Ù•Éí‰½É‘•Èµ½±½ÈéÙ…È ´µ±ÙŒµ…•¹Ğ¤í½±½ÈéÙ…È ´µ±ÙŒµ…•¹Ğ¤…¥µÁ½ÉÑ…¹Ğí‰…­É½Õ¹éÙ…È ´µ±ÙŒµ…•¹ĞµÍ½™Ğ¥ô¹±ØµÙ¥±±„µÉ¥‘í‘¥ÍÁ±…äéÉ¥íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌéÉ•Á•…Ğ Ì±µ¥¹µ…à À°Å™È¤¤í…ÀèÄ¸ÌÕÉ•µô¹±Øµ¡½µ”µÁ…¥¹…Ñ¥½¹í‘¥ÍÁ±…äé™±•àí©ÕÍÑ¥™äµ½¹Ñ•¹Ğé•¹Ñ•Èí…±¥¸µ¥Ñ•µÌé•¹Ñ•Èí…Àè¸ĞÕÉ•´íµ…É¥¸µÑ½ÀèÈ¸ÕÉ•´í™±•àµİÉ…ÀéİÉ…Áô¹±Øµ¡½µ”µÁ…¥¹…Ñ¥½¸€¹Á…”µ¹Õµ‰•ÉÍí‘¥ÍÁ±…äé¥¹±¥¹”µ™±•àí…±¥¸µ¥Ñ•µÌé•¹Ñ•Èí©ÕÍÑ¥™äµ½¹Ñ•¹Ğé•¹Ñ•Èíµ¥¸µİ¥‘Ñ èĞÉÁàíµ¥¸µ¡•¥¡ĞèĞÉÁàíÁ…‘‘¥¹œè¸ÔÕÉ•´€¸áÉ•´í‰½É‘•ÈèÅÁàÍ½±¥Ù…È ´µ±ÙŒµ‰½É‘•È¤í½±½ÈéÙ…È ´µ±ÙŒµÍ½™Ğ¤í‰…­É½Õ¹éÉ‰„ ÈÔÔ°ÈÔÔ°ÈÔÔ°¸ÀÈ¥ô¹±Øµ¡½µ”µÁ…¥¹…Ñ¥½¸€¹Á…”µ¹Õµ‰•ÉÌ¹ÕÉÉ•¹Ñí‰…­É½Õ¹éÙ…È ´µ±ÙŒµ…•¹Ğ¤í‰½É‘•Èµ½±½ÈéÙ…È ´µ±ÙŒµ…•¹Ğ¤í½±½Èè™™™ô($¹±Øµ…É•„µÉ¥‘í‘¥ÍÁ±…äéÉ¥íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌéÉ•Á•…Ğ Ğ±µ¥¹µ…à À°Å™È¤¤í…ÀèÅÉ•µô¹±ØµÑÕ±Õ´µÉ¥‘í‘¥ÍÁ±…äéÉ¥íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌéÉ•Á•…Ğ Ì±µ¥¹µ…à À°Å™È¤¤í…ÀèÅÉ•µô¹±Øµ…É•„µÑ¥±•íÁ½Í¥Ñ¥½¸éÉ•±…Ñ¥Ù”íµ¥¸µ¡•¥¡ĞèÌÌÁÁàí‘¥ÍÁ±…äé™±•àí…±¥¸µ¥Ñ•µÌé™±•àµ•¹íÁ…‘‘¥¹œèÄ¸ÌÕÉ•´í‰½É‘•ÈèÅÁàÍ½±¥Ù…È ´µ±ÙŒµ‰½É‘•È¤í‰…­É½Õ¹éÙ…È ´µ±ÙŒµ…É¤Ù…È ´µ…É•„µ¥µœ±¹½¹”¤•¹Ñ•È½½Ù•È¹¼µÉ•Á•…Ğí½Ù•É™±½Üé¡¥‘‘•¹ô¹±ØµÑÕ±Õ´µÉ¥€¹±Øµ…É•„µÑ¥±•íµ¥¸µ¡•¥¡ĞèÈàÕÁáô¹±Øµ…É•„µÑ¥±”é‰•™½É•í½¹Ñ•¹ĞèœœíÁ½Í¥Ñ¥½¸é…‰Í½±ÕÑ”í¥¹Í•ĞèÀí‰…­É½Õ¹é±¥¹•…ÈµÉ…‘¥•¹Ğ ÄàÁ‘•œ±É‰„ ÄÀ°ÄÈ°ÄÔ°¸ÄÈ¤±É‰„ ÄÀ°ÄÈ°ÄÔ°¸äÈ¤¥ô¹±Øµ…É•„µÑ¥±•}}‰½‘åíÁ½Í¥Ñ¥½¸éÉ•±…Ñ¥Ù”íèµ¥¹‘•àèÅô¹±Øµ…É•„µÑ¥±” Ííµ…É¥¸èÀí™½¹Ğµ™…µ¥±äéÙ…È ´µ±ÙŒµ™½¹Ğµ‘¥ÍÁ±…ä¤í™½¹ĞµÍ¥é”èÄ¸ÌÕÉ•´í™½¹Ğµİ•¥¡ĞèÌÀÀí½±½ÈéÙ…È ´µ±ÙŒµÑ•áĞ¥ô¹±Øµ…É•„µÑ¥±”Áíµ…É¥¸è¸ÔÕÉ•´€À€Àí½±½ÈéÙ…È ´µ±ÙŒµÍ½™Ğ¤í™½¹ĞµÍ¥é”è¸àÙÉ•´í±¥¹”µ¡•¥¡ĞèÄ¸ÔÕô¹±Øµ…É•„µÑ¥±”ÍÁ…¹í‘¥ÍÁ±…äé‰±½¬íµ…É¥¸µÑ½Àè¸àÕÉ•´í½±½ÈéÙ…È ´µ±ÙŒµ…•¹Ğ¤í™½¹ĞµÍ¥é”è¸àÉÉ•µô¹±Øµ™¥¹…°µÑ…í‰…­É½Õ¹éÙ…È ´µ±ÙŒµ‰œµ‘••À¤í‰½É‘•ÈµÑ½ÀèÅÁàÍ½±¥É‰„ ÈÔÔ°ÈÔÔ°ÈÔÔ°¸ÄÈ¤íÑ•áĞµ…±¥¸é•¹Ñ•Éô¹±Øµ™¥¹…°µÑ„€¹±Øµ¡½µ”µ½Áåíµ…àµİ¥‘Ñ èØàÁÁàíµ…É¥¸èÅÉ•´…ÕÑ¼€Ä¸ÙÉ•µô¹±Øµ™¥¹…°µÑ„€¹±Øµ¡½µ”µ‰Ñ¹Íí©ÕÍÑ¥™äµ½¹Ñ•¹Ğé•¹Ñ•Éô($¹±Øµ¡½µ”µ½µÁ…É•íÑ•áĞµ…±¥¸é•¹Ñ•Èíµ…àµİ¥‘Ñ èÜØÁÁàíµ…É¥¸èÈ¸ÉÉ•´…ÕÑ¼€Àí½±½ÈéÙ…È ´µ±ÙŒµÍ½™Ğ¤í™½¹ĞµÍ¥é”è¸äÕÉ•´í±¥¹”µ¡•¥¡ĞèÄ¸İô¹±Øµ¡½µ”µ½µÁ…É”…í½±½ÈéÙ…È ´µ±ÙŒµ…•¹Ğ¤íÑ•áĞµ‘•½É…Ñ¥½¸éÕ¹‘•É±¥¹”íÑ•áĞµÕ¹‘•É±¥¹”µ½™™Í•ĞèÍÁáô¹±Øµ¡½µ”µ½µÁ…É”„é¡½Ù•Éí½±½ÈéÙ…È ´µ±ÙŒµ…•¹Ğµ¡½Ù•È¥ô(%µ•‘¥„¡µ…àµİ¥‘Ñ èÄÄÀÁÁà¥ì¹±Øµ¡½µ”µ¡•É½}}É¥°¹±Øµ¥¹ÑÉ¼µÉ¥°¹±ØµÁ…Ñ µÉ¥°¹±Øµ½µÁ…É•íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌèÅ™Éô¹±Øµ…É•„µÉ¥°¹±ØµÙ¥±±„µÉ¥°¹±ØµÑÕ±Õ´µÉ¥‘íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌéÉ•Á•…Ğ È±µ¥¹µ…à À°Å™È¤¥ô¹±ØµÕÁÉ…‘”µÉ¥°¹±Øµ½¹¥•É”µÉ¥‘íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌéÉ•Á•…Ğ Ì±µ¥¹µ…à À°Å™È¤¥ô¹±Øµ¡½µ”µÁ…¹•±í‰½É‘•Èµ±•™ĞèÀíÁ…‘‘¥¹œµ±•™ĞèÁõõµ•‘¥„¡µ…àµİ¥‘Ñ èÜÈÁÁà¥ì¹±Øµ¡½µ”µİÉ…À°¹±Øµ¡½µ”µ¹…ÉÉ½İíİ¥‘Ñ é…±Œ ÄÀÀ”€´€ÉÉ•´¥ô¹±Øµ¡½µ”µ¡•É½íµ¥¸µ¡•¥¡Ğé…ÕÑ¼íÁ…‘‘¥¹œèÙÉ•´€À€ÑÉ•µô¹±Øµ¡½µ”µ¡•É¼ Åí™½¹ĞµÍ¥é”é±…µÀ È¸ÍÉ•´°ÄÉÙÜ°Ì¸ÕÉ•´¥ô¹±Øµ¡½µ”µ‰Ñ¹Íí‘¥ÍÁ±…äéÉ¥íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌèÅ™Éô¹±Øµ¡½µ”µµ…Ñ¡}}™…ÑÌ°¹±Øµ…É•„µÉ¥°¹±ØµÍÑ•ÁÌ°¹±Øµ½¹¥•É”µÉ¥°¹±ØµÙ¥±±„µÉ¥°¹±ØµÁ…Ñ µÉ¥°¹±ØµÕÁÉ…‘”µÉ¥°¹±ØµÑÕ±Õ´µÉ¥°¹±Øµ½µÁ…É•íÉ¥µÑ•µÁ±…Ñ”µ½±Õµ¹ÌèÅ™Éô¹±ÙŒµÁÉ½½™}}¥Ñ•µíİ¥‘Ñ èÄÀÀ”íÑ•áĞµ…±¥¸é•¹Ñ•Éô¹±Øµ™¥±Ñ•ÈµÁ¥±±Íí©ÕÍÑ¥™äµ½¹Ñ•¹Ğé™±•àµÍÑ…ÉÑõô(ğ½ÍÑå±”ø((ñµ…¥¸±…ÍÌô‰±ÙŒµ¡½µ”µµ½‘•É¸ˆø($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µ¡•É¼ˆ€ğıÁ¡À•¡¼€‘±Ù}¡½µ•}¡•É½}¥µœ€ü€ÍÑå±”ôˆ´µ¡½µ”µ¡•É¼µ¥µœéÕÉ°¡pœœ€¸•Í}ÕÉ° €‘±Ù}¡½µ•}¡•É½}¥µœ€¤€¸€pœ¤ˆœ€è€œœì€üø…É¥„µ±…‰•°ô‰I•Í¥‘•¹¥…ÌI••˜½éÕµ•°…¹ÁÉ¥Ù…Ñ”I¥Ù¥•É„5…å„Ù¥±±„É•¹Ñ…±Ìˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…À±Øµ¡½µ”µ¡•É½}}É¥ˆøñ‘¥ØøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•ÈˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}¡•É½}­¥­•È€¤ì€üøğ½ÍÁ…¸øñ ÄøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}¡•É½}Ñ¥Ñ±”€¤ì€üø€ñ•´øğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}¡•É½}…•¹Ğ€¤ì€üøğ½•´øğ½ ÄøñÀ±…ÍÌô‰±Øµ¡½µ”µ¡•É½}}ÍÕˆˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}¡•É½}¥¹ÑÉ¼€¤ì€üøğ½Àøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¹Ì±Øµ¡½µ”µ¡•É½}}…Ñ¥½¹Ìˆøñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘±Ù}É•Ä€¤ì€üøˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}ÁÉ¥µ…Éå}±…‰•°€¤ì€üøğ½„øñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸±Øµ¡½µ”µ‰Ñ¸´µ¡½ÍĞˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘±Ù}…É €¤ì€üøˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}Í•½¹‘…Éå}±…‰•°€¤ì€üøğ½„øğ½‘¥Øøğ½‘¥Øøñ…Í¥‘”±…ÍÌô‰±Øµ¡½µ”µµ…Ñ ˆøñ ÈøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}µ…Ñ¡}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}µ…Ñ¡}¥¹ÑÉ¼€¤ì€üøğ½Àøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µµ…Ñ¡}}™…ÑÌˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µµ…Ñ¡}}™…ĞˆøñÍÑÉ½¹œù¥É•Ğğ½ÍÑÉ½¹œøñÍÁ…¸ù9¼=Qµ…É­ÕÀğ½ÍÁ…¸øğ½‘¥Øøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µµ…Ñ¡}}™…ĞˆøñÍÑÉ½¹œù1½…°ğ½ÍÑÉ½¹œøñÍÁ…¸ùY¥±±„Õ¥‘…¹”ğ½ÍÁ…¸øğ½‘¥Øøğ½‘¥Øøñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘±Ù}É•Ä€¤ì€üøˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}µ…Ñ¡}±…‰•°€¤ì€üøğ½„øğ½…Í¥‘”øğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±ØµÁÉ½½˜ˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…À±ÙŒµÁÉ½½™}}¥¹¹•ÈˆøğıÁ¡À™½É•… € €‘±Ù}¡½µ•}ÁÉ½½™}Á½¥¹ÑÌ…Ì€‘Á½¥¹Ğ€¤€è€üøñ‘¥Ø±…ÍÌô‰±ÙŒµÁÉ½½™}}¥Ñ•´ˆøñÍÑÉ½¹œøğıÁ¡À•¡¼•Í}¡Ñµ° €‘Á½¥¹ÑlÁt€¤ì€üøğ½ÍÑÉ½¹œø€ğıÁ¡À•¡¼•Í}¡Ñµ° €‘Á½¥¹ÑlÅt€¤ì€üøğ½‘¥ØøğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸ˆ¥ô‰™•…Ğˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù•…ÑÕÉ•MÑ…åÌğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}™•…ÑÕÉ•‘}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}™•…ÑÕÉ•‘}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ¹…Ø±…ÍÌô‰±Øµ™¥±Ñ•ÈµÁ¥±±Ìˆ…É¥„µ±…‰•°ô‰Y¥±±„½±±•Ñ¥½¸™¥±Ñ•ÉÌˆøğıÁ¡À™½É•… € €‘±Ù}½±±•Ñ¥½¹}™¥±Ñ•ÉÌ…Ì€‘™¥±Ñ•È€¤€è€üøñ„±…ÍÌô‰±Øµ™¥±Ñ•ÈµÁ¥±°ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘™¥±Ñ•ÉlÅt€¤ì€üøˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘™¥±Ñ•ÉlÁt€¤ì€üøğ½„øğıÁ¡À•¹‘™½É•… ì€üøğ½¹…ØøğıÁ¡À€‘±Ù}½éÕµ•°€ô¹•Ü]A}EÕ•Éä …ÉÉ…ä €Á½ÍÑ}ÑåÁ”œ€ôø€‘±Ù}ÁĞ°€Á½ÍÑÍ}Á•É}Á…”œ€ôø€Ì°€Á½ÍÑ}ÍÑ…ÑÕÌœ€ôø€ÁÕ‰±¥Í œ°€™¥•±‘Ìœ€ôø€¥‘Ìœ°€¹½}™½Õ¹‘}É½İÌœ€ôøÑÉÕ”°€½É‘•É‰äœ€ôø€‘…Ñ”œ°€½É‘•Èœ€ôø€Mœ°€Ñ…á}ÅÕ•Éäœ€ôø…ÉÉ…ä …ÉÉ…ä €Ñ…á½¹½µäœ€ôø€…É•„œ°€™¥•±œ€ôø€Í±Õœœ°€Ñ•ÉµÌœ€ôø€½éÕµ•°œ°€¥¹±Õ‘•}¡¥±‘É•¸œ€ôøÑÉÕ”€¤€¤€¤€¤ì€‘±Ù}™•…ÑÕÉ•‘}¥‘Ì€ô…ÉÉ…å}µ…À €¥¹ÑÙ…°œ°€‘±Ù}½éÕµ•°´ùÁ½ÍÑÌ€¤ì€‘±Ù}µ½É”€ô¹•Ü]A}EÕ•Éä …ÉÉ…ä €Á½ÍÑ}ÑåÁ”œ€ôø€‘±Ù}ÁĞ°€Á½ÍÑÍ}Á•É}Á…”œ€ôø€Ø°€Á½ÍÑ}ÍÑ…ÑÕÌœ€ôø€ÁÕ‰±¥Í œ°€™¥•±‘Ìœ€ôø€¥‘Ìœ°€¹½}™½Õ¹‘}É½İÌœ€ôøÑÉÕ”°€Á½ÍÑ}}¹½Ñ}¥¸œ€ôø€‘±Ù}™•…ÑÕÉ•‘}¥‘Ì°€½É‘•É‰äœ€ôø€‘…Ñ”œ°€½É‘•Èœ€ôø€Mœ€¤€¤ì€‘±Ù}™•…ÑÕÉ•‘}¥‘Ì€ô…ÉÉ…å}µ•É” €‘±Ù}™•…ÑÕÉ•‘}¥‘Ì°…ÉÉ…å}µ…À €¥¹ÑÙ…°œ°€‘±Ù}µ½É”´ùÁ½ÍÑÌ€¤€¤ì€‘±Ù}Ù¥±±…Ì€ô¹•Ü]A}EÕ•Éä …ÉÉ…ä €Á½ÍÑ}ÑåÁ”œ€ôø€‘±Ù}ÁĞ°€Á½ÍÑÍ}Á•É}Á…”œ€ôø€ä°€Á½ÍÑ}ÍÑ…ÑÕÌœ€ôø€ÁÕ‰±¥Í œ°€Á½ÍÑ}}¥¸œ€ôø€‘±Ù}™•…ÑÕÉ•‘}¥‘Ì°€½É‘•É‰äœ€ôø€Á½ÍÑ}}¥¸œ°€¹½}™½Õ¹‘}É½İÌœ€ôøÑÉÕ”€¤€¤ì¥˜€ €‘±Ù}Ù¥±±…Ì´ù¡…Ù•}Á½ÍÑÌ ¤€¤€è€üøñ‘¥Ø±…ÍÌô‰±ØµÙ¥±±„µÉ¥ˆøğıÁ¡Àİ¡¥±”€ €‘±Ù}Ù¥±±…Ì´ù¡…Ù•}Á½ÍÑÌ ¤€¤€è€‘±Ù}Ù¥±±…Ì´ùÑ¡•}Á½ÍĞ ¤ì•Ñ}Ñ•µÁ±…Ñ•}Á…ÉĞ €Ñ•µÁ±…Ñ”µÁ…ÉÑÌ½…ÉµÁÉ½Á•ÉÑäœ°¹Õ±°°…ÉÉ…ä €¥œ€ôø•Ñ}Ñ¡•}% ¤€¤€¤ì•¹‘İ¡¥±”ì€üøğ½‘¥Øøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¹ÌˆÍÑå±”ô‰©ÕÍÑ¥™äµ½¹Ñ•¹Ğé•¹Ñ•Èíµ…É¥¸µÑ½ÀèÉÉ•´ˆøñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸±Øµ¡½µ”µ‰Ñ¸´µ¡½ÍĞˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘±Ù}…É €¤ì€üøˆùY¥•Ü±°Y¥±±…Ì€™…µÀì½¹‘½Ìğ½„øğ½‘¥ØøğıÁ¡ÀİÁ}É•Í•Ñ}Á½ÍÑ‘…Ñ„ ¤ì•¹‘¥˜ì€üøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸±Øµ¡½µ”µÍ•Ñ¥½¸´µ…±Ğˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…À±Øµ¥¹ÑÉ¼µÉ¥ˆøñ‘¥ØøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù¥É•Ğ	½½­¥¹œ½±±•Ñ¥½¸ğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}Á½Í¥Ñ¥½¹}Ñ¥Ñ±”€¤ì€üøğ½ Èøğ½‘¥Øøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µÁ…¹•°±Øµ¡½µ”µ½ÁäˆøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}Á½Í¥Ñ¥½¹}¥¹ÑÉ¼€¤ì€üøğ½ÀøñÕ°øğıÁ¡À™½É•… € €‘±Ù}¡½µ•}Á½Í¥Ñ¥½¹}Á½¥¹ÑÌ…Ì€‘Á½¥¹Ğ€¤€è€üøñ±¤øğıÁ¡À•¡¼•Í}¡Ñµ° €‘Á½¥¹ÑlÁt€¤ì€üøğ½±¤øğıÁ¡À•¹‘™½É•… ì€üøğ½Õ°øğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸ˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•ÈˆùQİ¼	½½­¥¹œA…Ñ¡Ìğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}Á…Ñ¡Í}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}Á…Ñ¡Í}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±ØµÁ…Ñ µÉ¥ˆøğıÁ¡À™½É•… € €‘±Ù}¡½µ•}Á…Ñ¡Í}…É‘Ì…Ì€‘¥¹‘•à€ôø€‘…É€¤€è€üøñ…ÉÑ¥±”±…ÍÌô‰±ØµÁ…Ñ µ…ÉˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•ÈˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÁt€¤ì€üøğ½ÍÁ…¸øñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÅt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÉt€¤ì€üøğ½Àøñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸ğıÁ¡À•¡¼€À€ôôô€‘¥¹‘•à€ü€œ±Øµ¡½µ”µ‰Ñ¸´µ¡½ÍĞœ€è€œœì€üøˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €À€ôôô€‘¥¹‘•à€ü¡½µ•}ÕÉ° €œ½½éÕµ•°¼œ€¤€è€‘±Ù}É•Ä€¤ì€üøˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÍt€¤ì€üøğ½„øğ½…ÉÑ¥±”øğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸±Øµ¡½µ”µÍ•Ñ¥½¸´µ…±Ğˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù]¡•¸Ñ¼UÁÉ…‘”ğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}ÕÁÉ…‘•}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}ÕÁÉ…‘•}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±ØµÕÁÉ…‘”µÉ¥ˆøğıÁ¡À™½É•… € €‘±Ù}¡½µ•}ÕÁÉ…‘•}…É‘Ì…Ì€‘…É€¤€è€üøñ…ÉÑ¥±”±…ÍÌô‰±ØµÕÁÉ…‘”µ…ÉˆøñÍÁ…¸øğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÁt€¤ì€üøğ½ÍÁ…¸øñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÅt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÉt€¤ì€üøğ½Àøğ½…ÉÑ¥±”øğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸±Øµ¡½µ”µÍ•Ñ¥½¸´µ…±Ğˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù]¡•É”Ñ¼MÑ…äğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}…É•…}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}…É•…}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±Øµ…É•„µÉ¥ˆøğıÁ¡À™½É•… € €‘±Ù}…É•…}…É‘Ì…Ì€‘…É•„€¤€è€‘…É•…}¥µœ€ô±Ù}…É•…}¥µ…” €‘…É•…lÅt€¤ì€‘…É•…}‘•ÍŒ€ô±Ù}¡½µ•}Ñ•Éµ}‘•ÍÉ¥ÁÑ¥½¸ €…É•„œ°€‘…É•…lÅt°€‘…É•…lÍt€¤ì€üøñ„±…ÍÌô‰±Øµ…É•„µÑ¥±”ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° ¡½µ•}ÕÉ° €‘…É•…lÉt€¤€¤ì€üøˆÍÑå±”ôˆğıÁ¡À•¡¼€‘…É•…}¥µœ€ü€œ´µ…É•„µ¥µœéÕÉ° œ€¸•Í}ÕÉ° €‘…É•…}¥µœ€¤€¸€œ¤œ€è€œœì€üøˆøñ‘¥Ø±…ÍÌô‰±Øµ…É•„µÑ¥±•}}‰½‘äˆøñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É•…lÁt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É•…}‘•ÍŒ€¤ì€üøğ½ÀøñÍÁ…¸ùáÁ±½É”€ğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É•…lÁt€¤ì€üø€™É…ÉÈìğ½ÍÁ…¸øğ½‘¥Øøğ½„øğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥ØøñÀ±…ÍÌô‰±Øµ¡½µ”µ½µÁ…É”ˆù9½ĞÍÕÉ”İ¡¥ Ñ¼¡½½Í”ü½µÁ…É”€ñ„¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° ¡½µ•}ÕÉ° €œ½½éÕµ•°µÙÌµÑÕ±Õ´µÙÌµÁ±…å„µ‘•°µ…Éµ•¸µÙ¥±±„µÉ•¹Ñ…±Ì¼œ€¤€¤ì€üøˆù½éÕµ•°ÙÌQÕ±Õ´ÙÌA±…å„‘•°…Éµ•¸ğ½„ø½È€ñ„¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° ¡½µ•}ÕÉ° €œ½ÑÕ±Õ´µÙÌµÁ±…å„µ‘•°µ…Éµ•¸µ½µÁ…É¥Í½¸µÕ¥‘”¼œ€¤€¤ì€üøˆùQÕ±Õ´ÙÌA±…å„‘•°…Éµ•¸ğ½„ø¸ğ½Àøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸ˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù	É½İÍ”‰ä½±±•Ñ¥½¸ğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½±±•Ñ¥½¹}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½±±•Ñ¥½¹}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±Øµ…É•„µÉ¥ˆøğıÁ¡À™½É•… € €‘±Ù}½±±•Ñ¥½¹Ì…Ì€‘½±±•Ñ¥½¸€¤€è€‘½±±•Ñ¥½¹}¥µœ€ô±Ù}¡½µ•}Ñ•Éµ}¥µ…” €½±±•Ñ¥½¸œ°€‘½±±•Ñ¥½¹lÅt€¤ì¥˜€ €„€‘½±±•Ñ¥½¹}¥µœ€˜˜€„•µÁÑä €‘½±±•Ñ¥½¹lÑt€¤€¤ì€‘½±±•Ñ¥½¹}¥µœ€ô€‘½±±•Ñ¥½¹lÑtìô€‘½±±•Ñ¥½¹}‘•ÍŒ€ô±Ù}¡½µ•}Ñ•Éµ}‘•ÍÉ¥ÁÑ¥½¸ €½±±•Ñ¥½¸œ°€‘½±±•Ñ¥½¹lÅt°€‘½±±•Ñ¥½¹lÍt€¤ì€üøñ„±…ÍÌô‰±Øµ…É•„µÑ¥±”ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘½±±•Ñ¥½¹lÉt€¤ì€üøˆÍÑå±”ôˆğıÁ¡À•¡¼€‘½±±•Ñ¥½¹}¥µœ€ü€œ´µ…É•„µ¥µœéÕÉ° œ€¸•Í}ÕÉ° €‘½±±•Ñ¥½¹}¥µœ€¤€¸€œ¤œ€è€œœì€üøˆøñ‘¥Ø±…ÍÌô‰±Øµ…É•„µÑ¥±•}}‰½‘äˆøñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘½±±•Ñ¥½¹lÁt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘½±±•Ñ¥½¹}‘•ÍŒ€¤ì€üøğ½ÀøñÍÁ…¸ùáÁ±½É”½±±•Ñ¥½¸€™É…ÉÈìğ½ÍÁ…¸øğ½‘¥Øøğ½„øğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸±Øµ¡½µ”µÍ•Ñ¥½¸´µ…±Ğˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•ÈˆùQÕ±Õ´Y¥±±„É•…Ìğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}ÑÕ±Õµ}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}ÑÕ±Õµ}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±ØµÑÕ±Õ´µÉ¥ˆøğıÁ¡À™½É•… € €‘±Ù}ÑÕ±Õµ}…É•…Ì…Ì€‘…É•„€¤€è€‘…É•…}¥µœ€ô±Ù}…É•…}¥µ…” €‘…É•…lÅt€¤ì€‘…É•…}‘•ÍŒ€ô±Ù}¡½µ•}Ñ•Éµ}‘•ÍÉ¥ÁÑ¥½¸ €…É•„œ°€‘…É•…lÅt°€‘…É•…lÍt€¤ì€üøñ„±…ÍÌô‰±Øµ…É•„µÑ¥±”ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° ¡½µ•}ÕÉ° €‘…É•…lÉt€¤€¤ì€üøˆÍÑå±”ôˆğıÁ¡À•¡¼€‘…É•…}¥µœ€ü€œ´µ…É•„µ¥µœéÕÉ° œ€¸•Í}ÕÉ° €‘…É•…}¥µœ€¤€¸€œ¤œ€è€œœì€üøˆøñ‘¥Ø±…ÍÌô‰±Øµ…É•„µÑ¥±•}}‰½‘äˆøñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É•…lÁt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É•…}‘•ÍŒ€¤ì€üøğ½ÀøñÍÁ…¸ùáÁ±½É”€ğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É•…lÁt€¤ì€üøÙ¥±±…Ì€™É…ÉÈìğ½ÍÁ…¸øğ½‘¥Øøğ½„øğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸ˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù½éÕµ•°½ÈY¥±±„UÁÉ…‘”ğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½µÁ…É•}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½µÁ…É•}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±Øµ½µÁ…É”ˆøñ…ÉÑ¥±”±…ÍÌô‰±Øµ½µÁ…É”µ…Éˆøñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½µÁ…É•}½¹‘½}Ñ¥Ñ±”€¤ì€üøğ½ ÌøñÕ°øğıÁ¡À™½É•… € €‘±Ù}¡½µ•}½µÁ…É•}½¹‘½}Á½¥¹ÑÌ…Ì€‘Á½¥¹Ğ€¤€è€üøñ±¤øğıÁ¡À•¡¼•Í}¡Ñµ° €‘Á½¥¹ÑlÁt€¤ì€üøğ½±¤øğıÁ¡À•¹‘™½É•… ì€üøğ½Õ°øğ½…ÉÑ¥±”øñ…ÉÑ¥±”±…ÍÌô‰±Øµ½µÁ…É”µ…Éˆøñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½µÁ…É•}Ù¥±±…}Ñ¥Ñ±”€¤ì€üøğ½ ÌøñÕ°øğıÁ¡À™½É•… € €‘±Ù}¡½µ•}½µÁ…É•}Ù¥±±…}Á½¥¹ÑÌ…Ì€‘Á½¥¹Ğ€¤€è€üøñ±¤øğıÁ¡À•¡¼•Í}¡Ñµ° €‘Á½¥¹ÑlÁt€¤ì€üøğ½±¤øğıÁ¡À•¹‘™½É•… ì€üøğ½Õ°øğ½…ÉÑ¥±”øğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸±Øµ¡½µ”µÍ•Ñ¥½¸´µ…±Ğˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù!½Ü%Ğ]½É­Ìğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}ÍÑ•ÁÍ}Ñ¥Ñ±”€¤ì€üøğ½ Èøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±ØµÍÑ•ÁÌˆøğıÁ¡À™½É•… € €‘±Ù}¡½µ•}ÍÑ•ÁÌ…Ì€‘¥¹‘•à€ôø€‘ÍÑ•À€¤€è€üøñ‘¥Ø±…ÍÌô‰±ØµÍÑ•ÀˆøñÍÁ…¸±…ÍÌô‰±ØµÍÑ•Á}}¹Õ´ˆøğıÁ¡À•¡¼•Í}¡Ñµ° ÍÑÉ}Á… €¡ÍÑÉ¥¹œ¤€ €‘¥¹‘•à€¬€Ä€¤°€È°€œÀœ°MQI}A}1P€¤€¤ì€üøğ½ÍÁ…¸øñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘ÍÑ•ÁlÁt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘ÍÑ•ÁlÅt€¤ì€üøğ½Àøğ½‘¥ØøğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸ˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µİÉ…Àˆøñ¡•…‘•È±…ÍÌô‰±Øµ¡½µ”µ¡•…ˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•Èˆù½¹¥•É”M•ÉÙ¥•Ìğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½¹¥•É•}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}½¹¥•É•}¥¹ÑÉ¼€¤ì€üøğ½Àøğ½¡•…‘•Èøñ‘¥Ø±…ÍÌô‰±Øµ½¹¥•É”µÉ¥ˆøğıÁ¡À™½É•… € €‘±Ù}¡½µ•}½¹¥•É•}…É‘Ì…Ì€‘…É€¤€è€üøñ‘¥Ø±…ÍÌô‰±Øµ½¹¥•É”µ…Éˆøñ ÌøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÁt€¤ì€üøğ½ ÌøñÀøğıÁ¡À•¡¼•Í}¡Ñµ° €‘…É‘lÅt€¤ì€üøğ½Àøğ½‘¥ØøğıÁ¡À•¹‘™½É•… ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(($ñÍ•Ñ¥½¸±…ÍÌô‰±Øµ¡½µ”µÍ•Ñ¥½¸±Øµ™¥¹…°µÑ„ˆøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µ¹…ÉÉ½ÜˆøñÍÁ…¸±…ÍÌô‰±Øµ¡½µ”µ­¥­•ÈˆùMÑ…ÉĞA±…¹¹¥¹œğ½ÍÁ…¸øñ È±…ÍÌô‰±Øµ¡½µ”µÑ¥Ñ±”ˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}™¥¹…±}Ñ¥Ñ±”€¤ì€üøğ½ ÈøñÀ±…ÍÌô‰±Øµ¡½µ”µ½ÁäˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}™¥¹…±}¥¹ÑÉ¼€¤ì€üøğ½Àøñ‘¥Ø±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¹Ìˆøñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸ˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘±Ù}É•Ä€¤ì€üøˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}™¥¹…±}ÁÉ¥µ…Éå}±…‰•°€¤ì€üøğ½„øğıÁ¡À¥˜€ €‘±Ù}İ„€¤€è€üøñ„±…ÍÌô‰±Øµ¡½µ”µ‰Ñ¸±Øµ¡½µ”µ‰Ñ¸´µ¡½ÍĞˆ¡É•˜ôˆğıÁ¡À•¡¼•Í}ÕÉ° €‘±Ù}İ„€¤ì€üøˆÑ…É•Ğô‰}‰±…¹¬ˆÉ•°ô‰¹½½Á•¹•ÈˆøğıÁ¡À•¡¼•Í}¡Ñµ° €‘±Ù}¡½µ•}™¥¹…±}Í•½¹‘…Éå}±…‰•°€¤ì€üøğ½„øğıÁ¡À•¹‘¥˜ì€üøğ½‘¥Øøğ½‘¥Øøğ½Í•Ñ¥½¸ø(ğ½µ…¥¸ø(ğıÁ¡À•Ñ}™½½Ñ•È ¤ì€üø