# Site content controls

The theme separates structured layout from editorial content. Current copy
remains the fallback, so empty controls never blank an existing page.

## Control center

Use the top-level **Site Content** menu. The same screen is also available at
**Appearance → Site Content**.

- The "Where to edit every page" map links directly to every core page, area,
  collection, and property editor.
- The retired ACF "Homepage" screen is migrated automatically into this screen.
- Image fields include both a URL input and a Media Library picker/preview.

## Homepage

Open **Site Content → Homepage — Hero** and **Homepage — Main Sections**.

- Hero Image is the primary homepage hero control.
- Hero eyebrow, heading lines, introduction, buttons, and shortlist panel are
  editable.
- Major homepage section headings and introductions are editable.
- Area and collection card images/copy come from their taxonomy terms, with
  property imagery and theme copy retained as safe fallbacks.
- The static Home page Featured Image remains a fallback when the central Hero
  Image is empty.

## Villas archive

Open **Site Content → Villas Archive**.

- Edit the `/villas/` hero image, H1, and introduction.

## Core WordPress pages

For About, Contact, FAQ, How It Works, List Your Villa, Villa Request,
Magazine, and Riviera Maya Villa Rentals:

- **Page Hero & Media → Hero Image URL** is the primary page hero.
- **Page Hero & Media → Feature / Card Image URL** is the page-card image and
  final hero fallback.
- **Page Hero & Media → Hero Introduction** is the primary hero summary.
- **Page title** controls the hero H1.
- **Excerpt** remains the hero-introduction fallback.
- **Featured Image** remains the shared WordPress image fallback.
- **Page content** supplies the editable editorial section. Designed template
  sections and forms remain intact around it.

## Area and collection pages

Edit the relevant **Area** or **Collection** taxonomy term:

- Hero Image URL
- H1 / intro
- positioning copy
- highlights
- FAQ questions and answers

The same term image and description also feed the corresponding homepage card
when that term is featured there.

## Property pages

Edit the Villa:

- **Hero Image (Property Page)** is the dedicated wide hero.
- **Feature Image (Cards/Grid)** is the stable card crop.
- WordPress **Featured Image** is the shared fallback.
- **Full Gallery URLs (Primary)** is the complete ordered gallery.
- **Gallery Preview URLs (Optional)** may contain up to six curated preview
  images. When empty, the first six Full Gallery images are used.

Legacy gallery values are merged and deduplicated at render time, so older
properties retain every image while they are gradually normalized.

