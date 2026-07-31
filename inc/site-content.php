<?php
/**
 * Native editable content controls.
 *
 * These controls deliberately use the WordPress Settings API rather than an
 * ACF options page so they remain available with ACF Free, ACF Pro, or no ACF.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_site_content' ) ) {
	function lvc_site_content( $key, $default = '' ) {
		$options = get_option( 'lvc_site_content', array() );
		$value   = is_array( $options ) && isset( $options[ $key ] ) ? $options[ $key ] : '';
		return '' !== trim( (string) $value ) ? $value : $default;
	}
}

if ( ! function_exists( 'lvc_site_content_fields' ) ) {
	function lvc_site_content_fields() {
		return array(
			'homepage' => array(
				'label'  => 'Homepage',
				'fields' => array(
					'home_hero_image'       => array( 'label' => 'Hero Image URL', 'type' => 'url', 'help' => 'Recommended: WebP, at least 2000px wide. A Home page Featured Image takes priority when one is set.' ),
					'home_hero_kicker'      => array( 'label' => 'Hero Eyebrow', 'type' => 'text' ),
					'home_hero_title'       => array( 'label' => 'Hero Heading — Main Line', 'type' => 'text' ),
					'home_hero_accent'      => array( 'label' => 'Hero Heading — Accent Line', 'type' => 'text' ),
					'home_hero_intro'       => array( 'label' => 'Hero Introduction', 'type' => 'textarea' ),
					'home_primary_label'    => array( 'label' => 'Primary Button Label', 'type' => 'text' ),
					'home_secondary_label'  => array( 'label' => 'Secondary Button Label', 'type' => 'text' ),
					'home_featured_title'   => array( 'label' => 'Featured Stays Heading', 'type' => 'text' ),
					'home_featured_intro'   => array( 'label' => 'Featured Stays Introduction', 'type' => 'textarea' ),
					'home_collection_title' => array( 'label' => 'Collections Heading', 'type' => 'text' ),
					'home_collection_intro' => array( 'label' => 'Collections Introduction', 'type' => 'textarea' ),
					'home_final_title'      => array( 'label' => 'Final CTA Heading', 'type' => 'text' ),
					'home_final_intro'      => array( 'label' => 'Final CTA Introduction', 'type' => 'textarea' ),
				),
			),
			'archive' => array(
				'label'  => 'Villas Archive',
				'fields' => array(
					'archive_hero_image' => array( 'label' => 'Hero Image URL', 'type' => 'url', 'help' => 'Used on /villas/. Recommended: WebP, at least 2000px wide.' ),
					'archive_title'      => array( 'label' => 'Archive H1', 'type' => 'text' ),
					'archive_intro'      => array( 'label' => 'Archive Introduction', 'type' => 'textarea' ),
				),
			),
		);
	}
}

add_action( 'admin_init', function () {
	register_setting(
		'lvc_site_content_group',
		'lvc_site_content',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => function ( $input ) {
				$clean = array();
				foreach ( lvc_site_content_fields() as $section ) {
					foreach ( $section['fields'] as $key => $field ) {
						$value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
						$clean[ $key ] = 'url' === $field['type'] ? esc_url_raw( $value ) : sanitize_textarea_field( $value );
					}
				}
				return $clean;
			},
		)
	);
} );

add_action( 'admin_menu', function () {
	add_theme_page(
		'Site Content',
		'Site Content',
		'edit_theme_options',
		'lvc-site-content',
		'lvc_render_site_content_page'
	);
} );

function lvc_render_site_content_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$options = get_option( 'lvc_site_content', array() );
	?>
	<div class="wrap">
		<h1>Site Content</h1>
		<p>Edit the structured homepage and villa-archive content here. Property content remains inside each Villa; area and collection content remains inside its taxonomy term.</p>
		<form action="options.php" method="post">
			<?php settings_fields( 'lvc_site_content_group' ); ?>
			<?php foreach ( lvc_site_content_fields() as $section ) : ?>
				<h2><?php echo esc_html( $section['label'] ); ?></h2>
				<table class="form-table" role="presentation"><tbody>
				<?php foreach ( $section['fields'] as $key => $field ) : $value = isset( $options[ $key ] ) ? $options[ $key ] : ''; ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php if ( 'textarea' === $field['type'] ) : ?>
								<textarea class="large-text" rows="4" id="<?php echo esc_attr( $key ); ?>" name="lvc_site_content[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea>
							<?php else : ?>
								<input class="large-text" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $key ); ?>" name="lvc_site_content[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
							<?php endif; ?>
							<?php if ( ! empty( $field['help'] ) ) : ?><p class="description"><?php echo esc_html( $field['help'] ); ?></p><?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody></table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

