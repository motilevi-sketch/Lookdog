# Typed prices removed from article bodies — 25 September 2026

Owner's request: take the prices out of the articles.

## Why

Seven articles carried AliExpress prices typed into the text: after product
headings ("Booster Car Seat with Storage Pockets — $11.88") and as a Price
column in comparison tables. The nightly price watch updates product pages, not
article text, so these figures drifted from the product pages from the day they
were written. That is one source of the mismatches reported in mid-September.

## What changed

| Post | Status | Change |
|---|---|---|
| 5089 Trackers compared | published | Price column removed from the table; "we list at $8.62" became "we list" |
| 5090 Slow feeders compared | published | Price column removed |
| 5091 Cooling mats compared | published | Price column removed |
| 5092 Harnesses compared | published | Price column removed |
| 5098 Car seat, hammock or tether | published | Price removed from 9 product headings; "nearly six times the price" → "several times the price", "a sixth of the money" → "a fraction of the money" |
| 5099 What a dog bed actually fixes | published | Price removed from 8 product headings |
| 5100 Why the same brush does not work | **draft** | Same treatment, so it cannot go live with stale prices |

The note under each comparison table ("Prices are the seller's figures on the
day of writing and they move...") now reads: "Current prices are on each product
page, with the date they were last checked."

61 figures found by scanning every post and page for a currency sign followed by
digits. 60 removed.

## Deliberately kept

- 5252 Clipping Your Dog at Home: "...rather than for you and a $30 clipper."
  A rough figure for a type of tool, not the price of a listed product. It does
  not drift with a listing.
- Generic thresholds with no currency sign, such as "a GPS tracker for under
  about fifteen dollars" (5089) and "comfort is worth three dollars" (5091).
  They are rules of thumb, not prices.

## Checks

- Block comment counts identical before and after in every post.
- Every table row has the same number of cells after the column was removed.
- Live pages re-fetched with a cache-buster: no dollar figure in any article body.

## Rollback

The original content of each post is stored in the option
`lookdog_price_strip_backup_{post_id}`, and WordPress kept a revision of each.
