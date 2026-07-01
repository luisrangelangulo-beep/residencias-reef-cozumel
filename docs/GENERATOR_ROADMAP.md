# Roadmap: from skeleton → site generator

**Goal:** spin up a new destination site (St Barts, Anguilla, Los Cabos, …) by
**configuring a brand + running its Google Sheet** — fast.

**Honest status (2026-06-27):** the core is a solid *foundation* (config, CPT,
taxonomies, router, inquiry engine, pages, schema scaffold) but it is **not yet a
generator.** Three "build-once" pieces stand between here and "run the sheet → site up."
Build them **during the first real site (Republic / DR), validated on DR staging**, and
promote each up into this core. Then DR ships *and* the engine exists for every site after.

---

## The 3 build-once items (do these in the core, once)

### 1. 🔴 Sheet-sync receiver — THE keystone (`inc/sync/…`)
An admin-triggered (or REST/admin-ajax) endpoint that fetches the brand's **published
Google Sheet** (TSV via `wp_remote_get` + `fgetcsv($fh, 0, "\t")`), parses rows, and
**upserts villa CPT posts** idempotently (match by `wp_post_id` → `villa_key` → slug):
- `post_title` = current marketing name (`card_title`).
- **slug = community+lot per [`DR_NAMING_POLICY`](../../republic-villa-rentals-theme/docs/DR_NAMING_POLICY.md)** — derive from the LIVE/stable identifier, **NOT** the sheet `url`/`villa_key` (marketing-name drift).
- All ACF fields (see item 2), taxonomy terms (destination/area/collection/bedrooms/beach_access/amenity/property_type/ideal_for/catering), `rank_math_title`/`rank_math_description`, `fifu_image_url` + gallery fields, `off_market`, `villa_aliases`.
- Skip generator-only sheet columns (auto_generate, tone, generation_log, etc.).
- **Port from Punta Mita's receiver** (`pmvr-repo` `inc/inquiry/ajax-handler.php` sibling / sheet sync) — generalise the field map to read from `theme-config.php`.
> Without this there is no "run the sheet." Highest-leverage item.

### 2. 🟡 Full ACF field group (`inc/property/fields.php`)
Expand the lean ~8-field group to the **~38-field PMVR mirror** so the sheet has somewhere
to land. Mirror PMVR's two groups exactly (Core + Gallery), incl. **flat `faq_q1`–`faq_a4`**
(not a repeater), `card_title`, `tagline`, `editorial_text`, `intro_paragraph`,
`setting_positioning`, `indoor/outdoor_living`, `bedroom_desc`, `view_type`, `access_type`,
`verification_status`, `off_market`, `hero_image`, `feature_image`, `villa_aliases`,
`gallery_squares`, `gallery_slider`. (Full blueprint in the DR repo README.) Update
`property-single` + `inc/seo/schema.php` to read flat FAQs.

### 3. 🟡 Tokenized design system (`assets/` + rich templates)
Port the PMVR/Tulum **component CSS** into the core as a **tokenized system** (CSS variables
for palette + fonts + radii + spacing), and flesh out the rich templates: `property-single`
(all conversion sections), `property-archive` (filter bar), destination/area/collection/
bedrooms archives, and the **magazine** layer. Each brand then differentiates by **swapping
tokens (palette + fonts + hero imagery)** — not rebuilding. This is what reconciles
*"fast to generate"* with *"each site looks distinct, not a clone."*

---

## After the engine exists: per-site flow
See [`NEW_SITE_SETUP.md`](NEW_SITE_SETUP.md). In short: configure `theme-config.php` →
set brand tokens in `assets/brand.css` (the per-brand skin) → create destination/area/
collection terms → **run the sheet** → assign the core pages → QA → launch.

## Target sites (this core will generate)
Republic (DR — the build-out vehicle), then **St Barts, Anguilla, Los Cabos**, and onward.
Each is: a brand config + a token skin + that destination's Google Sheet.
