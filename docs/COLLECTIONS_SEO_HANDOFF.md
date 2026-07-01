# Collection Pages — SEO Handoff

**Site:** residencias-reef-cozumel.com
**Date:** 2026-07-01
**For:** SEO specialist (no dev/code access needed for this task)

## What was just built

A new `collection` taxonomy on the `villas` post type — a way to group villas by
*feature* instead of by location. Four starter terms exist, no villas are tagged yet:

- Beachfront
- Family Villas
- Large Groups
- Private Chef

Each term will have its own page at `residencias-reef-cozumel.com/collections/{term-slug}/`
(e.g. `/collections/beachfront/`), the same way each area already has a page (Cozumel,
Tulum, etc.). Two new fields were added per term — **Hero Image URL** and **Intro / Landing
Copy** — visible when editing a term under **Villas → Collections** in wp-admin.

**Important caveat:** the new theme that will actually *display* the hero/intro copy
isn't activated on the live site yet (still in a build-and-verify phase). Filling in the
fields now is prep work — it won't visibly change the live page today, but it needs to be
ready before that theme goes live. Don't wait on this; the goal is to have it all filled
in before that switch happens.

---

## Your three tasks

### 1. Tag villas with the right collection(s)

Go to **Villas → All Villas** in wp-admin. Open a villa, and in the right-hand sidebar
there's a **Collections** box (same place taxonomies like Area already show). Check every
collection that genuinely applies — a villa can be in more than one (e.g. a big beachfront
villa can be both "Beachfront" and "Large Groups").

**Only tag what's actually true.** Don't tag a villa "Private Chef" unless chef service is
actually available/marketed for that property — mistagging creates a trust problem if a
guest clicks through expecting something the villa doesn't offer.

**Minimum viable page:** don't publish/rely on a collection page until it has **at least
3 villas** tagged. A collection with 1–2 villas reads as thin content to Google (and to a
visitor) — see the "thin content" note below.

### 2. Fill in the Hero Image URL + Intro copy per collection

For each of the 4 terms (**Villas → Collections → click the term name → scroll to the
custom fields**):

**Hero Image URL** — one real photo URL from one of the villas actually tagged in that
collection. Not a stock photo, not a generic graphic. Pick the single best "hero" shot —
this is the first thing a visitor sees on that page. Get the image URL from the Media
Library or from the villa's own gallery field.

**Intro copy** — 2–4 sentences, written like a person, not an SEO template. What to include:
- What makes this collection real (not marketing fluff) — e.g. for Beachfront: which areas
  actually have beachfront villas (don't claim "beachfront" broadly if it's really just
  Cozumel and Akumal)
- Who it's for — Large Groups should speak to the group-trip planner, not a couple
- One natural mention of the keyword phrase you're targeting (see keyword guidance below) —
  it should read naturally, not stuffed

**What to avoid:**
- Generic filler ("Discover the best beachfront villas for an unforgettable stay...") —
  this brand is dark-luxury and direct-booking, not OTA-style copy
- Don't repeat the exact same paragraph structure across all 4 terms — vary it, or it reads
  templated to both Google and the visitor
- Don't put a price, a discount, or urgency language in this copy

### 3. Propose additional collection terms if the data supports it

You can add more terms than the 4 starters (e.g. "Ocean View," "Pet Friendly," "Pool
Villas") **if — and only if — GSC data or actual villa inventory supports it.** See below
for how to decide. Don't add a term speculatively; every collection term needs both real
demand *and* enough villas to tag under it.

---

## How to use your GSC data to prioritize this

You have three windows: last 7 days, last month, last 3 months. Use them differently:

**3-month window → find the demand signal.** Go to **Search Console → Performance → Search
results**, set the date range to the last 3 months, and look at the **Queries** tab. Filter
for feature-based terms: "beachfront," "private pool," "family," "large group," "pet
friendly," "chef," combined with "villa," "Cozumel," "Tulum," "Riviera Maya," etc. Sort by
impressions. This tells you what people are already searching for in relation to this site
— that's your evidence for which of the 4 starter collections to build out *first*, and
whether any of the "propose additional terms" candidates are worth adding.

**1-month window → check what's already ranking, so you don't cannibalize.** Before you
finalize the intro copy's target phrase for, say, "Beachfront," check whether an *area*
page (e.g. `/cozumel-luxury-villas/`) is already ranking for "beachfront villas cozumel."
If it is, don't have the Beachfront collection page target that exact same phrase — that's
duplicate-intent competition between two of your own pages. Instead:
- Area pages own: **"[feature] villas in [specific destination]"** (e.g. "beachfront villas
  in Cozumel")
- Collection pages should own the **broader, cross-destination version**: **"[feature]
  villas in the Riviera Maya"** (e.g. "beachfront villas Riviera Maya") — since this site
  spans Cozumel, Tulum, Playa del Carmen, and Akumal, the collection page's real job is to
  be the one page that shows beachfront options *across all of them*, not to re-target a
  single destination an area page already owns.

**Last-7-days window → sanity check after you publish.** After tagging villas and filling
in copy, check this window weekly for a few weeks to see if `/collections/` URLs start
getting impressions at all (Search Console → Pages, filter by URL contains `/collections/`).
If a collection page gets zero impressions after several weeks of being live and indexed,
that's a signal either the keyword has no real demand or the page needs stronger internal
linking (see below) — not necessarily a copy problem.

---

## Pitfalls specific to this being brand new

**Thin content risk.** A collection page with 0–2 villas tagged will likely get "Crawled –
not indexed" or "Discovered – not indexed" in GSC Coverage. Don't be surprised by this —
it's expected until enough villas are tagged. Don't try to fix it with more copy; fix it by
tagging more villas.

**Duplicate-content risk vs. area pages.** This is the biggest real risk with this
taxonomy on this specific site (see the 1-month-window guidance above). Because
Residencias Reef Cozumel spans multiple destinations, a "Beachfront" collection page and a
"Cozumel" area page can end up saying nearly the same thing about the same villas. Keep
them differentiated by scope (single destination vs. cross-destination) as described
above, not by rewording the same claim two different ways.

**Internal linking — the pages won't get found without it.** New taxonomy pages start as
orphans (no incoming links) until something links to them. Once the theme activates, ask
for: a link from the homepage to the top 1–2 collection pages, a link from each villa
single page to the collection(s) it's tagged under, and a link from the relevant area
page(s) to the collection page (e.g. Cozumel's page linking to "Beachfront" if Cozumel has
beachfront villas). Flag this to the dev/build side if it isn't already planned — a
collection page with great copy but zero internal links won't rank.
