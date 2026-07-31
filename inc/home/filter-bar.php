<?php
/**
 * Homepage villa finder.
 *
 * Arrival and departure are advisory because WordPress does not contain a
 * live availability calendar. Area, guests, and bedrooms filter the real
 * Villas archive; dates remain in the URL for the inquiry handoff.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_filter_meta_values' ) ) {
	function lvc_filter_meta_values( $key ) {
		$allowed = array( 'bed_count', 'guests_max' );
		if ( ! in_array( $key, $allowed, true ) ) {
			return array();
		}

		$cache_key = 'lvc_filter_vals_' . $key;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$rows = $wpdb->get_col(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT DISTINCT pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
					AND p.post_type = %s
					AND p.post_status = 'publish'
					AND pm.meta_value REGEXP '^[0-9]+$'
					AND CAST(pm.meta_value AS UNSIGNED) > 0",
				$key,
				lvc_config( 'cpt', 'villas' )
			)
		);

		$values = array_values( array_unique( array_map( 'absint', (array) $rows ) ) );
		sort( $values, SORT_NUMERIC );
		set_transient( $cache_key, $values, 12 * HOUR_IN_SECONDS );
		return $values;
	}
}

if ( ! function_exists( 'lvc_filter_buckets' ) ) {
	function lvc_filter_buckets( $values, $max_options = 7 ) {
		$values = array_values( array_filter( array_map( 'absint', (array) $values ) ) );
		if ( count( $values ) <= $max_options ) {
			return $values;
		}
		return array_merge( array_slice( $values, 0, $max_options - 1 ), array( $values[ $max_options - 1 ] ) );
	}
}

add_action( 'save_post', 'lvc_filter_flush_cache' );
add_action( 'deleted_post', 'lvc_filter_flush_cache' );
function lvc_filter_flush_cache( $post_id = 0 ) {
	if ( $post_id && lvc_config( 'cpt', 'villas' ) !== get_post_type( $post_id ) ) {
		return;
	}
	delete_transient( 'lvc_filter_vals_bed_count' );
	delete_transient( 'lvc_filter_vals_guests_max' );
}

if ( ! function_exists( 'lvc_render_filter_bar' ) ) {
	function lvc_render_filter_bar() {
		$areas = get_terms(
			array(
				'taxonomy'   => 'area',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		$areas  = is_wp_error( $areas ) ? array() : $areas;
		$beds   = lvc_filter_buckets( lvc_filter_meta_values( 'bed_count' ) );
		$guests = lvc_filter_buckets( lvc_filter_meta_values( 'guests_max' ) );
		$today  = current_time( 'Y-m-d' );
		?>
		<form class="lvc-fbar" method="get" action="<?php echo esc_url( lvc_archive_url() ); ?>" role="search" aria-label="Find matching villas">
			<div class="lvc-fbar__inner">
				<label class="lvc-fbar__field">
					<span class="lvc-fbar__label"><?php echo lvc_fbar_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Area</span>
					<select class="lvc-fbar__input" name="<?php echo esc_attr( lvc_filter_param_for_taxonomy( 'area' ) ); ?>" aria-label="Area">
						<option value="">Any area</option>
						<?php foreach ( $areas as $area ) : ?>
							<option value="<?php echo esc_attr( $area->slug ); ?>"><?php echo esc_html( $area->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="lvc-fbar__field">
					<span class="lvc-fbar__label"><?php echo lvc_fbar_icon( 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Arrival</span>
					<input class="lvc-fbar__input" type="date" name="arrival" min="<?php echo esc_attr( $today ); ?>" aria-label="Arrival date">
				</label>
				<label class="lvc-fbar__field">
					<span class="lvc-fbar__label"><?php echo lvc_fbar_icon( 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Departure</span>
					<input class="lvc-fbar__input" type="date" name="departure" min="<?php echo esc_attr( $today ); ?>" aria-label="Departure date">
				</label>
				<label class="lvc-fbar__field">
					<span class="lvc-fbar__label"><?php echo lvc_fbar_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Guests</span>
					<select class="lvc-fbar__input" name="guests" aria-label="Minimum guests">
						<option value="">Add guests</option>
						<?php foreach ( $guests as $guest_count ) : ?>
							<option value="<?php echo esc_attr( $guest_count ); ?>"><?php echo esc_html( $guest_count . '+ guests' ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="lvc-fbar__field">
					<span class="lvc-fbar__label"><?php echo lvc_fbar_icon( 'bed' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Bedrooms</span>
					<select class="lvc-fbar__input" name="beds" aria-label="Minimum bedrooms">
						<option value="">Any</option>
						<?php foreach ( $beds as $bed_count ) : ?>
							<option value="<?php echo esc_attr( $bed_count ); ?>"><?php echo esc_html( $bed_count . '+ bedrooms' ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button type="submit" class="lvc-fbar__submit"><?php echo lvc_fbar_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Find Matching Villas</button>
			</div>
			<p class="lvc-fbar__note">Area, guests, and bedrooms narrow the villa grid. Dates are carried forward so our team can confirm availability.</p>
		</form>
		<?php
	}
}

if ( ! function_exists( 'lvc_fbar_icon' ) ) {
	function lvc_fbar_icon( $name ) {
		$paths = array(
			'pin'    => '<path d="M8 1.5a4.5 4.5 0 0 0-4.5 4.5c0 3.4 4.5 8.5 4.5 8.5s4.5-5.1 4.5-8.5A4.5 4.5 0 0 0 8 1.5Z"/><circle cx="8" cy="6" r="1.7"/>',
			'cal'    => '<rect x="2.2" y="3.2" width="11.6" height="10.6" rx="1.4"/><path d="M2.2 6.4h11.6M5.4 1.8v2.6M10.6 1.8v2.6"/>',
			'user'   => '<circle cx="8" cy="5.2" r="2.6"/><path d="M2.9 14a5.1 5.1 0 0 1 10.2 0"/>',
			'bed'    => '<path d="M2 12.5v-8M2 8.6h12v3.9M14 12.5v-2M4.6 6.6h3.1v2H4.6z"/>',
			'search' => '<circle cx="7.2" cy="7.2" r="4.6"/><path d="m10.6 10.6 3 3"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		return '<svg class="lvc-fbar__icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}
