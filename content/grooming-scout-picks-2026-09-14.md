# Grooming — the five the user kept from the scout — 14 September 2026

The user reviewed the product scout's Grooming, $20–40 bucket in wp-admin and
marked five candidates "keep". Task: publish them by category, cross-link them
into existing articles, and write a new article for any product whose topic
had no editorial home.

## Published (4 of the 5 — see the duplicate found below)

| Post | Product | ae_id | Price | Feedback | Orders |
|------|---------|-------|-------|----------|--------|
| 5254 | LED Light Dog Nail Clippers | 3256811990737948 | $22.52 | 98.0% | 181 |
| 5261 | 6.5 Inch Dog Grooming Scissors Kit | 3256807448670880 | $19.39 | 98.0% | 92 |
| 5268 | Foldable Pet Grooming Table | 3256812175873563 | $19.20 | 100.0% | 91 |
| 5275 | Fenice 7.5 Inch Professional Grooming Scissors Set | 3256806307076300 | $38.77 | 100.0% | 41 |

All four: category Grooming (71), external/affiliate product, featured image
plus five gallery images, buy button, Cons and Things to Consider sections,
affiliate disclosure, SureRank title/description set.

## Photo check, before anything was created

Every candidate's photos were downloaded and visually inspected before import
— this catalogue has produced real cat photos and heavy vendor-banner clutter
before, and the check was not skipped this time either.

All five were genuinely dog-only. One needed a fix anyway: the Fenice 7.5"
scissors set's own main listing photo is a marketing banner (brand logo,
"30 degree standard arc", spec callouts) covering most of the frame. Of its
six photos, the sixth is a plain product shot with no text at all — that one
was made the featured image instead, and the bannered original kept its place
in the gallery rather than being dropped, since the rest of the site's product
photography includes marketing shots in the gallery below a clean lead image.

## The fifth product: a duplicate, caught after publishing, corrected

The fifth kept item — "Pet Bath Hammock for Small Dogs Cats... Restraint Bag
for Sink Bathtub" (ae_id 3256812406219591) — was created as post 5282 and
written up as a sling that clips to a sink or tub's edges.

Checking its featured image's MD5 against the rest of the catalogue afterwards
found an exact byte match with an already-published product: post 5195,
"Foldable Grooming Hammock with Leg Openings" ($21.25, live since the 5
September batch). Same manufacturer photo, two AliExpress listing IDs. 5195's
own existing copy describes the real mechanism correctly — a mesh sling on its
own folding frame, legs through four openings, feet hanging clear of the
floor — which is not what the new listing's title implied and not what I had
written for it (a sink-clip sling was invented from the title, not the photo).

**Post 5282 was trashed.** Publishing it next to 5195 would have put the same
photo on two product pages with two different, and in the new one's case
wrong, descriptions of what it does.

The new article (below) links to 5195 instead, with the mechanism corrected to
match what 5195's page already says. 5195 was tagged `wont-hold-still-for-bath`
so its own product page now carries the "Before you buy" link to that article,
which it did not have before.

Net result: **four new products**, not five, plus one existing product newly
linked into a guide it had no editorial connection to before.

## New article: the one product topic with no home

Checked what the site already covers:

- Nails — covered in depth in `dog-grooming-at-home-coat-types-tools` (4497).
- Scissors near the face, feet and sanitary area, and where the dog stands
  while clipped — covered in `clipping-your-dog-at-home` (5252).
- A dog who will not hold still specifically for a **bath** — not covered.
  4497's bathing section is shampoo, temperature and rinsing; nothing on site
  addressed the holding-still problem itself, which is what the (surviving)
  hammock product and a bath mat both answer.

Wrote **"My Dog Won't Hold Still for a Bath"** (post 5289,
`/dog-wont-hold-still-for-a-bath/`, category Buying Guides). Shape: myth and
correction — neither of the last two grooming articles used it (reference
sheet, then chronological), so this kept the run of shapes from repeating.
Opens on the usual advice ("get someone to help hold them"), then splits the
real problem in two: a dog fighting for grip versus a dog who is frightened,
which look the same from the doorway and need opposite fixes. Names the
product only for the traction case, states plainly that restraint does
nothing for fear and can make it worse, and sends a severely fearful case to
a fear-free groomer or a vet rather than to another product.

Links: one product link (the hammock, in the traction section, after
corrected mechanism), one out-link to 4497 at the bathing bridge, one to the
Grooming archive at the close. ~800 words.

## Cross-links added

`lookdog-related-reading.php`'s `lookdog_problem_reading_map()` had no entries
for the clipping guide (5252) at all — nothing on the site linked to it through
that mechanism, only three hand-placed in-body links to three pre-existing
products. Added three tag-based rows, then tagged the products:

| Tag | Guide | Applied to |
|-----|-------|------------|
| `overgrown-nails` | 4497 (nail-specific blurb) | 5254 (nail clippers) |
| `clipping-at-home` | 5252 | 5261, 5268, 5275 (both scissors sets, the table) |
| `wont-hold-still-for-bath` | 5289 (new) | 5195 (the hammock that survived) |

Each product's "Before you buy" box now resolves to the most specific guide
rather than falling back to the general category link to 4497.

## Verified

- All four new products: HTTP-reachable via `lookdog_toy_create()`'s own
  return (status `created`, 6 images each), correct category, correct
  featured image (Fenice's swap confirmed by re-fetching the live file).
- New article: HTTP 200, one H1, no unrendered shortcodes, pull quote present
  verbatim, all three links resolve, block comments balanced, no loose HTML
  chunks from `parse_blocks()`.
- `lookdog_reading_for_product()` checked live for all five product IDs
  (4 new + 5195) — each now resolves to the intended guide, not the default.
- Trashed post 5282 confirmed `post_status = trash`.
