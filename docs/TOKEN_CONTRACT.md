# Styling contract (`assets/brand.css`)

The core templates output **semantic class names** and expect a set of **CSS custom
properties** (design tokens). They ship with **no styling** — define everything here,
per brand, and the same markup restyles completely.

> This contract grows as templates land (Phase 2+). Treat the variable names below as
> the stable interface; templates will only ever reference these tokens + the `lvc-`
> class namespace, never hardcoded colors/fonts.

## Design tokens — define on `:root`
```css
:root {
  /* Surfaces (dark theme is the baseline for these brands) */
  --lvc-bg:            #0E1518;   /* page background */
  --lvc-bg-alt:        #0A1114;   /* alternating sections */
  --lvc-card:          #111111;   /* cards / panels */
  --lvc-border:        rgba(255,255,255,0.08);

  /* Text */
  --lvc-text:          #FBFBFB;   /* headings / primary */
  --lvc-soft:          rgba(251,251,251,0.86); /* body */
  --lvc-muted:         #9A9A9A;   /* labels / meta */

  /* Brand accent (the one value that most defines a brand) */
  --lvc-accent:        #00AA7B;
  --lvc-accent-hover:  #00C99A;
  --lvc-accent-soft:   rgba(0,170,123,0.12);

  /* Type */
  --lvc-font-display:  'Albert Sans', system-ui, sans-serif;
  --lvc-font-body:     'Lato', system-ui, sans-serif;

  /* Rhythm */
  --lvc-px:            5%;        /* horizontal page padding */
  --lvc-radius:        4px;
}
```

## Class namespace
All structural hooks use the `lvc-` prefix, grouped by component, e.g.:

| Component | Example classes |
|---|---|
| Layout | `.lvc-section`, `.lvc-section--alt`, `.lvc-container` |
| Hero | `.lvc-hero`, `.lvc-hero__title`, `.lvc-hero__sub`, `.lvc-hero__cta` |
| Buttons | `.lvc-btn`, `.lvc-btn--ghost` |
| Property card | `.lvc-card`, `.lvc-card__img`, `.lvc-card__name`, `.lvc-card__meta` |
| Grids | `.lvc-grid`, `.lvc-grid--3`, `.lvc-grid--2` |
| Filter bar | `.lvc-filter`, `.lvc-filter__select`, `.lvc-filter__chip` |
| Inquiry form | `.lvc-form`, `.lvc-form__row`, `.lvc-form__group`, `.lvc-form__submit` |
| FAQ | `.lvc-faq`, `.lvc-faq__item`, `.lvc-faq__q`, `.lvc-faq__a` |
| Section header | `.lvc-eyebrow`, `.lvc-sec-title` |

## Rules
- Brands **only** edit `theme-config.php` + this stylesheet. No template edits.
- Keep it dark-theme-first (per the Luxury UX baseline) unless a brand explicitly differs.
- Don't reintroduce inline `<style>` in templates — that's exactly what this core removes.
