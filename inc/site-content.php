<?php
/**
 * Central content controls and editor map.
 *
 * Uses the native Settings API so the controls work with any ACF edition.
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
			'homepage_hero' => array(
				'label'       => 'Homepage â€” Hero',
				'description' => 'The hero configured here is the primary homepage image and copy.',
				'fields'      => array(
					'home_hero_image'       => array( 'label' => 'Hero Image', 'type' => 'image', 'help' => 'Recommended: WebP or JPG, at least 2000px wide.' ),
					'home_hero_kicker'      => array( 'label' => 'Eyebrow', 'type' => 'text' ),
					'home_hero_title'       => array( 'label' => 'Heading â€” Main Line', 'type' => 'text' ),
					'home_hero_accent'      => array( 'label' => 'Heading â€” Accent Line', 'type' => 'text' ),
					'home_hero_intro'       => array( 'label' => 'Introduction', 'type' => 'textarea' ),
					'home_primary_label'    => array( 'label' => 'Primary Button', 'type' => 'text' ),
					'home_secondary_label'  => array( 'label' => 'Secondary Button', 'type' => 'text' ),
					'home_match_title'      => array( 'label' => 'Shortlist Panel Heading', 'type' => 'text' ),
					'home_match_intro'      => array( 'label' => 'Shortlist Panel Text', 'type' => 'textarea' ),
					'home_match_label'      => array( 'label' => 'Shortlist Panel Button', 'type' => 'text' ),
				),
			),
			'homepage_sections' => array(
				'label'       => 'Homepage â€” Main Sections',
				'description' => 'Major homepage editorial copy. Area and collection card copy comes from the corresponding taxonomy term.',
				'fields'      => array(
					'home_featured_title'    => array( 'label' => 'Featured Stays Heading', 'type' => 'text' ),
					'home_featured_intro'    => array( 'label' => 'Featured Stays Introduction', 'type' => 'textarea' ),
					'home_position_title'    => array( 'label' => 'Direct Booking Heading', 'type' => 'text' ),
					'home_position_intro'    => array( 'label' => 'Direct Booking Text', 'type' => 'textarea' ),
					'home_paths_title'       => array( 'label' => 'Booking Paths Heading', 'type' => 'text' ),
					'home_paths_intro'       => array( 'label' => 'Booking Paths Introduction', 'type' => 'textarea' ),
					'home_upgrade_title'     => array( 'label' => 'Upgrade Heading', 'type' => 'text' ),
					'home_upgrade_intro'     => array( 'label' => 'Upgrade Introduction', 'type' => 'textarea' ),
					'home_area_title'        => array( 'label' => 'Areas Heading', 'type' => 'text' ),
					'home_area_intro'        => array( 'label' => 'Areas Introduction', 'type' => 'textarea' ),
					'home_collection_title'  => array( 'label' => 'Collections Heading', 'type' => 'text' ),
					'home_collection_intro'  => array( 'label' => 'Collections Introduction', 'type' => 'textarea' ),
					'home_tulum_title'       => array( 'label' => 'Tulum Areas Heading', 'type' => 'text' ),
					'home_tulum_intro'       => array( 'label' => 'Tulum Areas Introduction', 'type' => 'textarea' ),
					'home_compare_title'     => array( 'label' => 'Condo vs Villa Heading', 'type' => 'text' ),
					'home_compare_intro'     => array( 'label' => 'Condo vs Villa Introduction', 'type' => 'textarea' ),
					'home_steps_title'       => array( 'label' => 'How It Works Heading', 'type' => 'text' ),
					'home_concierge_title'   => array( 'label' => 'Concierge Heading', 'type' => 'text' ),
					'home_concierge_intro'   => array( 'label' => 'Concierge Introduction', 'type' => 'textarea' ),
					'home_final_title'       => array( 'label' => 'Final CTA Heading', 'type' => 'text' ),
					'home_final_intro'       => array( 'label' => 'Final CTA Introduction', 'type' => 'textarea' ),
				),
			),
			'archive' => array(
				'label'       => 'Villas Archive',
				'description' => 'Controls the hero on /villas/. Villa cards themselves are edited inside each Villa.',
				'fields'      => array(
					'archive_hero_image' => array( 'label' => 'Hero Image', 'type' => 'image', 'help' => 'Recommended: WebP or JPG, at least 2000px wide.' ),
					'archive_title'      => array( 'label' => 'Archive H1', 'type' => 'text' ),
					'archive_intro'      => array( 'label' => 'Archive Introduction', 'type' => 'textarea' ),
				),
			),
		);
	}
}

add_action( 'admin_init', function () {
	/*
	 * One-time, non-destructive migration from the old ACF Homepage screen.
	 * Existing URLs remain in their original options as a rollback fallback.
	 */
	if ( ! get_option( 'lvc_site_content_migrated', false ) ) {
		$options = get_option( 'lvc_site_content', array() );
		$options = is_array( $options ) ? $options : array();
		$changed = false;
		foreach ( array(
			'home_hero_image'    => 'options_home_hero_image_url',
			'archive_hero_image' => 'options_archive_hero_image_url',
		) as $new_key => $legacy_key ) {
			if ( empty( $options[ $new_key ] ) ) {
				$legacy = get_option( $legacy_key, '' );
				if ( $legacy ) {
					$options[ $new_key ] = esc_url_raw( $legacy );
					$changed             = true;
				}
			}
		}
		if ( $changed ) {
			update_option( 'lvc_site_content', $options, false );
		}
		update_option( 'lvc_site_content_migrated', 1, false );
	}

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
						$value         = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
						$clean[ $key ] = 'image' === $field['type'] ? esc_url_raw( $value ) : sanitize_textarea_field( $value );
					}
				}
				return $clean;
			},
		)
	);
} );

add_action( 'admin_menu', function () {
	add_menu_page(
		'Site Content',
		'Site Content',
		'edit_theme_options',
		'lvc-site-content',
		'lvc_render_site_content_page',
		'dashicons-edit-page',
		58
	);
	add_submenu_page(
		'themes.php',
		'Site Content',
		'Site Content',
		'edit_theme_options',
		'lvc-site-content',
		'lvc_render_site_content_page'
	);
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false === strpos( (string) $hook, 'lvc-site-content' ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'media-editor',
		"document.addEventListener('click',function(e){var b=e.target.closest('.lvc-pick-image');if(!b)return;e.preventDefault();var w=wp.media({title:'Choose image',button:{text:'Use this image'},multiple:false});w.on('select',function(){var a=w.state().get('selection').first().toJSON();var row=b.closest('.lvc-image-control');row.querySelector('input').value=a.url;row.querySelector('img').src=a.url;row.querySelector('img').hidden=false;});w.open();});"
	);
} );

if ( ! function_exists( 'lvc_content_edit_link' ) ) {
	function lvc_content_edit_link( $slug ) {
		$page = get_page_by_path( $slug );
		return $page ? get_edit_post_link( $page->ID, '' ) : '';
	}
}

function lvc_render_site_content_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$options = get_option( 'lvc_site_content', array() );
	$pages   = array(
		'Home page'          => array( 'type' => 'settings', 'note' => 'Use the Homepage panels below.' ),
		'Villas archive'     => array( 'type' => 'settings', 'note' => 'Use the Villas Archive panel below.' ),
		'About'              => array( 'slug' => 'about-us' ),
		'Contact'            => array( 'slug' => 'contact' ),
		'FAQ'                => array( 'slug' => 'faq' ),
		'How It Works'       => array( 'slug' => 'how-it-works' ),
		'List Your Villa'    => array( 'slug' => 'list-your-villa' ),
		'Villa Request'      => array( 'slug' => 'property-request' ),
		'Magazine'           => array( 'slug' => 'magazine' ),
		'Riviera Maya Guide' => array( 'slug' => 'riviera-maya-villa-rentals' ),
	);
	?>
	<div class="wrap lvc-content-admin">
		<h1>Site Content</h1>
		<p class="lvc-admin-lead">One control center for the custom-designed parts of the website. Changes here do not alter URLs, SEO metadata, or villa inventory.</p>

		<details class="lvc-editor-map" open>
			<summary>Where to edit every page</summary>
			<div class="lvc-editor-map__grid">
				<?php foreach ( $pages as $label => $page ) : ?>
					<div class="lvc-editor-map__item">
						<strong><?php echo esc_html( $label ); ?></strong>
						<?php if ( ! empty( $page['slug'] ) ) :
							$link = lvc_content_edit_link( $page['slug'] ); ?>
							<span>Title = H1 Â· Excerpt = hero text Â· Featured Image = hero Â· Page Content = body</span>
							<?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>">Edit page â†’</a><?php else : ?><em>Page not found</em><?php endif; ?>
						<?php else : ?>
							<span><?php echo esc_html( $page['note'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<div class="lvc-editor-map__item"><strong>Area pages</strong><span>Areas â†’ Edit term: name, hero, introduction, highlights and FAQs.</span><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=area&post_type=' . lvc_config( 'cpt', 'villas' ) ) ); ?>">Edit areas â†’</a></div>
				<div class="lvc-editor-map__item"><strong>Collection pages</strong><span>Collections â†’ Edit term: name, hero, introduction, highlights and FAQs.</span><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=collection&post_type=' . lvc_config( 'cpt', 'villas' ) ) ); ?>">Edit collections â†’</a></div>
				<div class="lvc-editor-map__item"><strong>Property pages</strong><span>Villas â†’ Edit Villa: hero, card image, galleries, facts, descriptions and FAQs.</span><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . lvc_config( 'cpt', 'villas' ) ) ); ?>">Edit villas â†’</a></div>
			</div>
		</details>

		<form action="options.php" method="post">
			<?php settings_fields( 'lvc_site_content_group' ); ?>
			<?php foreach ( lvc_site_content_fields() as $section_key => $section ) : ?>
				<details class="lvc-content-panel" <?php echo 'homepage_hero' === $section_key ? 'open' : ''; ?>>
					<summary><?php echo esc_html( $section['label'] ); ?></summary>
					<div class="lvc-content-panel__body">
						<p><?php echo esc_html( $section['description'] ); ?></p>
						<table class="form-table" role="presentation"><tbody>
						<?php foreach ( $section['fields'] as $key => $field ) : $value = isset( $options[ $key ] ) ? $options[ $key ] : ''; ?>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
								<td>
									<?php if ( 'textarea' === $field['type'] ) : ?>
										<textarea class="large-text" rows="4" id="<?php echo esc_attr( $key ); ?>" name="lvc_site_content[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea>
									<?php elseif ( 'image' === $field['type'] ) : ?>
										<div class="lvc-image-control">
											<input class="large-text" type="url" id="<?php echo esc_attr( $key ); ?>" name="lvc_site_content[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="https://â€¦">
											<button type="button" class="button lvc-pick-image">Choose from Media Library</button>
											<img src="<?php echo esc_url( $value ); ?>" alt="" <?php echo $value ? '' : 'hidden'; ?>>
										</div>
									<?php else : ?>
										<input class="large-text" type="text" id="<?php echo esc_attr( $key ); ?>" name="lvc_site_content[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
									<?php endif; ?>
									<?php if ( ! empty( $field['help'] ) ) : ?><p class="description"><?php echo esc_html( $field['help'] ); ?></p><?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody></table>
					</div>
				</details>
			<?php endforeach; ?>
			<?php submit_button( 'Save Site Content' ); ?>
		</form>
	</div>
	<style>
		.lvc-content-admin{max-width:1240px}.lvc-admin-lead{font-size:15px;max-width:850px}
		.lvc-editor-map,.lvc-content-panel{margin:18px 0;background:#fff;border:1px solid #c3c4c7;box-shadow:0 1px 1px rgba(0,0,0,.04)}
		.lvc-editor-map>summary,.lvc-content-panel>summary{cursor:pointer;padding:15px 18px;font-size:16px;font-weight:600}
		.lvc-editor-map__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1px;background:#dcdcde;border-top:1px solid #dcdcde}
		.lvc-editor-map__item{display:flex;flex-direction:column;gap:5px;padding:15px;background:#fff}.lvc-editor-map__item span{color:#646970;line-height:1.45}.lvc-editor-map__item a{font-weight:600}
		.lvc-content-panel__body{padding:0 18px 10px;border-top:1px solid #dcdcde}.lvc-image-control{display:grid;grid-template-columns:1fr auto;gap:8px;max-width:900px}.lvc-image-control img{grid-column:1/-1;width:280px;max-height:150px;object-fit:cover;border:1px solid #dcdcde;background:#f6f7f7}
		@media(max-width:900px){.lvc-editor-map__grid{grid-template-columns:1fr}.lvc-image-control{grid-template-columns:1fr}.lvc-image-control img{grid-column:auto}}
	</style>
	<?php
}

