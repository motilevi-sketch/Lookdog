# Amazon: first links — 24 September 2026

The owner opened an amazon.com Associates account and sent links made with
SiteStripe. Short links were resolved server-side (a HEAD request, no page
content fetched) to read the store, the ASIN and the tracking ID.

## Tracking ID

The account has two tracking IDs. The first link carried `livethelife05-20`,
the second `lookdog-20` — SiteStripe uses whichever one is selected in its own
drop-down. The site now uses `lookdog-20` (option `lookdog_amazon`), so Amazon's
reports show LookDog separately. Links are built as
`https://www.amazon.com/dp/{ASIN}/?tag=lookdog-20`: direct, no redirect in
between, because a purchase through an intermediate redirect is excluded from
commission under the operating agreement.

## Links received

| ASIN | Product | Decision |
|---|---|---|
| B00CPDWT2M | Benebone Wishbone | **Not placed.** Hard nylon. Three guides (safe play 3344, chewing 5028, puppy 4524) tell readers to skip hard nylon chews because they fracture teeth. Placing it would sell against the site's own advice. Left to the owner; not placed without an explicit decision. |
| B0F7K28SDC | KONG Extreme Ball with Hole | **Placed** in the chewing guide (5028), directly under the thumbnail test and the sizing rule, before "Managing the House". |

Both links came from sponsored positions in an Amazon search (`sr=8-1-spons`,
`sr=8-2-spons`). Worth knowing when choosing: the top search results are paid.

## The block, as it renders

- Intro written for the spot: names only what the listing's own title says
  (Extreme line, heavy chewers, a hole for treats) and repeats the article's two
  rules — the thumbnail test and sizing for the biggest mouth.
- No price, no image, no star rating: none of those may be shown without API
  access, which needs three qualifying sales first.
- `rel="sponsored nofollow noopener"`, opens in a new tab.
- Disclosure printed with the link, verbatim: "As an Amazon Associate I earn
  from qualifying purchases."

The chewing guide is 975 words and now carries three shopping links (two
catalogue products and this one), slightly over the house rate of one per 800
words. Accepted because this block sits exactly where the article has just told
the reader what to look for.

## Second batch — same day

The owner searched with the terms suggested in chat and skipped the sponsored
results this time (positions 8-7 to 8-18, none marked `spons`). All four carry
`lookdog-20`.

| ASIN | Product (from the listing URL) | Placed in | Where exactly |
|---|---|---|---|
| B07N7VTKQC | AOFOOK front carrier, adjustable | 5298 Carrying a Small Dog | After the paragraph on front carriers and both hands free |
| B0BWC9FJ39 | JOEJOY booster seat, metal frame | 5035 My Dog Hates the Car | After the "What helps" list, pointing back to its first item, letting them see out |
| B0BZYDN8SJ | EHEYCIGA orthopaedic bed, waterproof, washable cover | 4499 Choosing a Dog Bed | After the list ending on the waterproof inner liner |
| B0GCXVX5NW | Stainless fountain, 2.1 gal (about 8 litres) | 4500 Trackers, Lights and Training Tech | After the fountain list ending on "keep a normal bowl as well" |

Each intro claims only what the listing URL states or the section above already
says, then repeats the section's own test:
- booster: height and containment, **not** crash protection; harness, never collar
- bed: "orthopaedic" proves nothing; palm test on arrival, return it if you reach the floor
- fountain: check replacement filters exist and what they cost before buying
- carrier: check the weight limit; keep trips short in warm weather

## Found in passing, not changed

The comparison articles 5098 and 5099 carry AliExpress prices typed into the
article body ("Booster Car Seat with Storage Pockets — $11.88", "Orthopedic Foam
Dog Bed ... — $9.02"). Those figures do not refresh with the nightly price
watch, so they drift from the product pages over time - one source of the
price mismatches the owner reported in mid-September.
