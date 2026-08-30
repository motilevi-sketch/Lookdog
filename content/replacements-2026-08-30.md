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

---

# Five duplicate listings removed — same day

Found while gathering data for the comparison articles: the catalogue held five
products **twice**.

AliExpress numbers the same item in two ID spaces that differ by exactly 2^51 —
the search and detail APIs answer with a `3256…` id, the site's own item URLs use
a `1005…` one. The import de-duplicated on the ID string, so an item harvested
under one number and later under the other came in twice, each copy priced from
whenever its own data was read. The catalogue was offering one cooling mat at
$3.48 and $6.66, and one waste-bag dispenser at $2.27 and $4.11.

| Kept | Retired | Was priced |
|---|---|---|
| Water-Fill Ice Gel Cooling Cushion (3665) | Water-Fill Gel Cooling Cushion (4480) | $3.48 / $6.66 |
| Ice Silk Cooling Bed with Raised Sides (3679) | Ice-Silk Cooling Bed with Raised Oval Rim (4473) | $9.37 / $12.81 |
| Waterproof Real-Time GPS Tracker (4065) | Real-Time GPS Collar with Movement Alerts (4156) | $8.62 / $8.72 |
| Fine-Tipped Tick Removal Tweezers (4788) | Two-in-One Tick Removal Tool (4271) | $2.83 / $3.31 |
| Leash-Mounted Waste Bag Dispenser (4244) | Lead-Mounted Waste Bag Dispenser (5012) | $4.11 / $2.27 |

Proof that these were the same item rather than similar ones: refreshing the five
survivors' prices moved the waste-bag dispenser from $4.11 to **$2.27**, exactly
what its "duplicate" had been showing. The two prices were one product read at
two moments, not two products.

Handling: the duplicate is drafted (not deleted) and carries `_lookdog_duplicate_of`;
its URL 301s to the copy that was kept; the beds guide's photo strip, which
happened to open with one of the retired records, now points at the keeper. The
surviving waste-bag dispenser was also in Feeding & Care, which is the wrong
shelf for it, and is now in Travel Gear alongside Best Sellers.

The durable fix is `lookdog_ae_id_variants()` in `lookdog-harvest.php`: both the
harvester's duplicate check and `lookdog_toy_find_existing()` now match on both
numbers, so the same product cannot enter twice under its two names. Verified by
looking up a product stored under its `3256…` id using its `1005…` one.

Catalogue: 243 → 238 published products, none of them lost.
