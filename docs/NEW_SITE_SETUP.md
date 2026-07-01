# Spinning up a new site from the core

This theme core is a **virgin skeleton**: PHP structure, query logic, schema, and
the inquiry engine — **no CSS, no brand specifics**. To launch a new luxury rental
site (e.g. Los Cabos, Cayman, Republic) you do three things: configure, style, fill.

## 1. Pull the core
Copy the core files into your new child theme (or start the repo from this one).
It's a Hello Elementor child (`Template: hello-elementor` in `style.css`).

## 2. Configure — edit `theme-config.php` (the only PHP you must touch)
Set the brand values:

| Key | Example |
|---|---|
| `brand_name` / `brand_tagline` | `Los Cabos Luxury Villas` / `Private estates on the Baja coast.` |
| `support_email` / `owner_email` | `support@los-cabos-luxury-villas.com` |
| `phone` / `whatsapp_url` | `+1 …` / `https://wa.link/…` |
| `cpt` + `cpt_singular/plural` | `villa` / `Villa` / `Villas` (or `chalet`, `condo`) |
| `cpt_archive_slug` / `cpt_rewrite_slug` | `luxury-villas` |
| `register_cpt` | `false` if CPT-UI / a plugin already registers it |
| `taxonomies` | add/remove per site (e.g. drop `beach_access`, add `ski_access`) |
| `pages` | the slugs of your contact/request/about/etc. pages |
| `geo` | destination lat/lng for schema |

Prefer not to edit the file? Override any key via the `lvc_config` filter from a
small mu-plugin:
```php
add_filter( 'lvc_config', function ( $c ) {
    $c['brand_name']    = 'Los Cabos Luxury Villas';
    $c['support_email'] = 'support@los-cabos-luxury-villas.com';
    $c['cpt']           = 'villa';
    return $c;
} );
```

## 3. Style — create `assets/brand.css`
The core emits semantic class names + CSS-variable hooks but ships zero styling.
Define the design tokens and rules in `assets/brand.css` — see
[`TOKEN_CONTRACT.md`](TOKEN_CONTRACT.md) for the exact variables and class names
the templates expect. It auto-enqueues when present.

## 4. Fill — content (run the sheet)
> Assumes the 3 build-once engine items are in the core — see [`GENERATOR_ROADMAP.md`](GENERATOR_ROADMAP.md).
- Create the WP **pages** at the slugs in `pages` (contact, request, about, legal, faq, …) and assign the matching core page templates (Page Attributes → Template).
- Create the **destination / area / collection / bedrooms** terms (or let the sheet sync create them).
- **Run the destination's Google Sheet** → the sync upserts the villas: CPT posts at the
  **community+lot slug** (per the naming policy, NOT the sheet `url` column), all ACF fields,
  taxonomy terms, `rank_math_title`/`description`, `fifu_image_url` + galleries, aliases.
- Flush permalinks (Settings → Permalinks → Save) so CPT/taxonomy URLs resolve.

## 0. Per-site prerequisites (before the above)
One-time environment setup per brand — this is the part that isn't "instant":
- **Domain + hosting** (cPanel), fresh **WordPress** + Hello Elementor + this child theme.
- **Plugins:** ACF Pro, Rank Math, FIFU (Featured Image From URL).
- **Novamira connector** (per-domain) + a **deploy workflow** (`deploy-theme.yml`, manual `workflow_dispatch`, like the existing brands).
- **Brand assets:** logo, the Cloudflare **R2 image bucket**, and the destination's published **Google Sheet** (the content pipeline).
- If migrating an existing ranking site: capture the **URL inventory + redirect map** first (see the Republic/DR docs for the pattern) and preserve the ranking slugs.

## 5. QA & launch
- Every URL **200/301** (no 404s); schema valid (Rich Results Test); inquiry form delivers; mobile full-fidelity; sitemap correct in Rank Math + submitted to GSC.
- Purge caches (WP Rocket + Cloudflare) after the sheet run and after any redirect changes.

## What you do NOT do
- You don't rewrite templates per brand — structure is shared.
- You don't touch the inquiry engine, schema, or query logic — they're config-driven.
- You don't hardcode brand strings in templates — they all read `lvc_config()`.

## Function namespace
All core functions use the `lvc_` prefix (luxury villa core). Don't rename them
per brand — that's the point of a shared core.
