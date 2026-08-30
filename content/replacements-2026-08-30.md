# Six withdrawn products replaced — 30 August 2026

## What happened

The nightly availability check had six products on strikes: four on two, two on
one. Rather than wait for the third strike, each was queried directly against
`aliexpress.affiliate.productdetail.get` in a batch that also carried two
products known to be alive, twice. Both times the controls came back and none of
the six did, on a call that returned HTTP 200 with no error — so the absence is
the supplier, not the endpoint going quiet. All six were marked unavailable on
the spot.

None of the six had ever been clicked, so no traffic was lost.

## The six, and what replaced them

| Withdrawn | Replacement | Feedback | Orders | Price |
|---|---|---|---|---|
| Silicone Light-Up Collar with Three Flash Modes | Rechargeable Silicone LED Collar with Three Modes | 95.8% | 430 | $3.74 |
| Insulated Steel Water Cup with Foldable Bowl | Stainless Steel Travel Water Bottle with Bowl | 96.5% | 440 | $5.56 |
| Large-Capacity Leak-Proof Dog Water Bottle | 800ml Dog Water Bottle with Fold-Out Trough | 96.9% | 1,282 | $9.20 |
| Reusable Lint Roller for Pet Hair | Reusable Pet Hair Remover for Sofas and Car Seats | 93.0% | 959 | $2.69 |
| Extra Large Plush Dog Sofa and Crate Mat | Extra Large Foam Dog Bed with Washable Cover | 97.9% | 248 | $69.60 |
| Teething Chew Stick with Treat Cavity | Rubber Squeaky Chew Ball for Teething Puppies | 98.0% | 571 | $1.61 |

Every replacement clears the same bar as the rest of the catalogue: 84% positive
feedback (the 4.2-of-5 equivalent) and at least 200 orders.

Two of the six are not like-for-like, and the product copy says so rather than
pretending otherwise:

- The teething stick had a treat cavity; nothing with a cavity clears the
  feedback bar in this catalogue — half that niche sits below 84%. The chew ball
  answers the same need (a teething puppy with something legal to chew) and its
  Cons say plainly that it does not stuff.
- The bed is the most expensive item on the site at $69.60. Its Cons say the
  foam density is nowhere published, so "orthopedic" here is a shape rather than
  a specification, and that a dog with diagnosed joint disease is better served
  elsewhere.

## Verification

Each replacement's affiliate link was followed to its landing page. AliExpress
uses two ID spaces — the API's `3256…` and the site's `1005…` — which differ by
exactly 2^51; all six landed on the item that arithmetic predicts, at the price
recorded on the product page. The links go where they claim to.

## Two defects found while doing it

1. **The withdrawn-product notice had never rendered.** It was hooked on
   `woocommerce_before_add_to_cart_form`, which lives inside the add-to-cart
   template that the same file removes for withdrawn products — so the button
   disappeared and nothing explained why. No product had ever reached three
   strikes before, so nothing had exercised it. Moved to
   `woocommerce_single_product_summary` at priority 29.
2. **Archive cards still pointed at dead listings.** The button is only removed
   on the single product page; a category or tag grid still rendered one, and
   `/out/{id}` happily forwarded to an affiliate URL that answers 200 and lands
   nowhere useful. `/out/` now sends a withdrawn product to its own page.

The withdrawn pages name their replacement, through a new `_lookdog_replaced_by`
meta key. Nothing sets it automatically: the nightly job still refuses to choose
a substitute, because judging one is not a thing a cron job should do alone.
