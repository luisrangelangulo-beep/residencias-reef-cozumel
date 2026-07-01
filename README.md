# residencias-reef-cozumel

WordPress theme for [residencias-reef-cozumel.com](https://residencias-reef-cozumel.com) —
a Hello Elementor child theme built on
[`luxury-villa-theme-core`](https://github.com/luisrangelangulo-beep/luxury-villa-theme-core),
following the same pattern as `Los-Cabos-Luxury-Villas`.

## Site model
- **CPT `villas`** (150 posts) — already registered by CPT UI; `register_cpt: false`.
- **`area` taxonomy** — hierarchical, plays the destination+area role in one tree:
  `Riviera Maya` (root) → `Cozumel` / `Playa Del Carmen` / `Puerto Aventuras` / `Akumal` /
  `Tulum` → sub-areas (`Residencias Reef Cozumel`, `Playacar`, `Sian Ka'an`, `Soliman Bay`,
  `Town Jungle`, `Tulum Beach Zone`, `Tankah Bay`). Already registered; `register_taxonomies: false`.
- **`bedrooms` taxonomy** — flat, already registered. No `destination`/`amenities`/`beach_access`
  taxonomies exist on this site (unlike Los Cabos).
- **ACF "Property fields"** on `villas`: `h1_property_title`, `bedrooms`, `bathrooms`,
  `max_guests`, `view_type`, `community`, `property_description`, `indoor_living`,
  `outdoor_living`, `bedroom_description`, `destination_description`, `gallery_squares`,
  `gallery_slider`.
- **ACF "Area Pages"** on `area` (created to match Los Cabos's live group): `h1_title`,
  `h1_sub_paragraph`, `h2_title`, `h2_area_paragraph`, `featured_properties_title/paragraph`,
  `why_stay_here_title`, `why_stay_info`, `key_highlights`, `key_highlights_text`.

## Templates ported from Los Cabos (same pattern, re-skinned)
- `single-villas.php` — dedicated villa single (wins over the core's generic
  `template-parts/property-single.php` via the `template-router.php` patch).
- `page-templates/area-lander.php` — one reusable "Area Lander" template assigned to
  13 separate WP Pages (one per non-empty area term); page-slug↔term map lives in
  `inc/property/area-lander-map.php` (single source of truth — Los Cabos duplicated
  this list across two files, this doesn't).
- `page.php` + `template-parts/editorial-sidebar.php` — generic editorial page layout.
- `front-page.php` — homepage, area cards + collection filters point at this site's
  real areas instead of Los Cabos's neighborhoods.

## Brand identity
- Accent: reef blue `#4C7FB8` (deep/desaturated — not the literal SaaS blue reference) on
  the same dark-theme baseline as every other site. See `assets/brand.css`.
- Fonts unchanged from Los Cabos: Albert Sans (display) + Libre Franklin (body).

## Deploy
Manual `workflow_dispatch` to the dormant `hello-elementor-child` directory on
`wwwloscabosluxur`-style cPanel (`residenciasreefc` @ `50.6.226.162`, same box as
Los Cabos/Republic/RMOF). **Not activated** — same phased approach as Los Cabos:
deploy to the dormant child theme, verify via Live Preview, activate only once
everything is built and verified.

## Open items before activation
- `support_email` / `phone` / `whatsapp_url` in `theme-config.php` are placeholders —
  confirm real contact details.
- "Area Pages" ACF fields are empty on all 13 area terms — needs copy per area
  (same as Los Cabos's 5-of-7 gap at this stage).
- Data-hygiene note: the live `area` taxonomy has a duplicate "Tankah Bay" term
  (one empty child of Tulum, one populated direct child of Riviera Maya) — not
  touched by this build, flagging for cleanup separately.
