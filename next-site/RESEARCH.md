# The next site — what the numbers say before we pick a niche

Research record, 16 September 2026. Nothing here is built yet. This document
exists to make the niche decision from measurement rather than from taste, and
to write down what was actually checked so it can be re-checked later.

**The short version:** the niche is not the decision. The commission per
converted order is, and on the current model it is **29 cents**. Every finding
below is downstream of that number.

## 1. What LookDog can and cannot tell us yet

The instinct is to read LookDog's performance as evidence about dogs. It is not
evidence about anything yet, and it is worth being precise about why.

**The site is 23 days old.** Oldest published item is the snuffle mat, dated
2026-08-24. In that time it has accumulated 262 products, 27 posts and 10 pages.

**Live traffic, read from `lookdog_click_log` on 16 September:**

| | Count |
| --- | --- |
| Entries in the log window (~3–16 Sep) | 400 |
| Classified automated | 382 |
| Classified human | 18 |
| Distinct IP hashes behind the 18 | 12 |
| Human clicks from the single busiest hash | 7 |

So roughly **eleven genuine outbound clicks in two weeks**, and one source
accounts for seven of the eighteen — most likely the owner's own browsing from a
logged-out browser. 289 of the 382 automated clicks come from one hash on 3–4
September: one crawler, one visit, not a trend.

The `/go/` campaign counters say the same thing in a different way. Sixteen slugs
each carry between 6 and 8 hits, arriving at a rate of about one per slug per
day, in tight timestamp clusters. Human campaign traffic is lumpy and
concentrated. A flat rate across every slug is something walking the
`/shop-by-problem/` page.

**The conclusion is not "dogs do not work."** A three-week-old domain with no
backlinks has no organic search traffic, and would not have any whatever it
sold. The catalogue, the seven guides and the internal link graph have not yet
had the chance to be wrong. Nothing in this data licenses a verdict on the
niche.

What the data *does* license is a verdict on the economics, because those can be
computed without any traffic at all.

## 2. The wall: 29 cents per order

Sampled 36 random catalogue items through
`aliexpress.affiliate.productdetail.get`; 33 returned (the rest fell inside the
rate limit documented in the main README).

| Measure | Value |
| --- | --- |
| Commission rate | **7.0% on all 33** |
| Median item price | $4.13 |
| **Median commission per converted order** | **$0.29** |
| Mean commission per converted order | $0.59 |
| Best single item in the sample | $3.39 |

Across the whole catalogue (259 items carrying a cached price) the median is
$5.66 and the mean $11.88; **116 of 259 items are under $5** and 183 are under
$10.

Three things make this worse than the headline number.

- **The rate is fixed.** 7.0% came back on all 33 pet items *and* on every one of
  the sixteen unrelated niches tested in section 3. There is no category to move
  to that pays a better percentage. Price is the only variable.
- **The payout floor is $16.** At $0.59 mean that is 27 converted orders before
  any money is received at all, and at the median $0.29 it is 55.
- **The cookie is three days**, and the rate drops by roughly 40% for buyers
  AliExpress already knows. Published program terms; not measured here.

What that means in traffic. The conversion and click-through figures below are
**assumptions, not measurements** — we have no conversion data of our own — but
they are deliberately generous:

| To earn | Orders needed | Outbound clicks at 2% | Sessions at 8% outbound CTR |
| --- | --- | --- | --- |
| $100 / month | 170 | 8,500 | 106,000 |
| $500 / month | 847 | 42,000 | 529,000 |

Half a million sessions a month is a large, established publication. That is the
size of site the current price point demands, and it is the reason this document
is about price point rather than about which animal.

## 3. Which niches break the ceiling

Queried `aliexpress.affiliate.product.query` for sixteen keywords, top 30 results
each by order volume, and computed price × commission per item. Same API, same
day, same method throughout.

**Above the line — the item itself is the expensive thing:**

| Niche | Median commission / order | Share of results ≥ $50 | vs. LookDog |
| --- | --- | --- | --- |
| Portable power station / solar | **$14.70** | 67% | 51× |
| Electric scooter (adult) | **$10.57** | — | 36× |
| Laser engraver / cutter | **$6.86** | 60% | 24× |
| Projector, 4K home theatre | **$4.21** | 72% | 15× |

**The middle — mid-priced goods with real volume:**

| Niche | Median | Mean | Share $40–200 | Order volume in sample |
| --- | --- | --- | --- | --- |
| Dash cam / car tech | $2.21 | **$3.58** | 40% | 109,000 |
| Cycling GPS & computers | $1.78 | **$2.76** | 24% | 110,000 |
| Headlamps / EDC lights | $1.85 | $1.80 | 7% | 30,000 |
| Camping gear | $1.49 | $1.90 | 30% | 50,000 |
| Telescopes / astronomy | $1.18 | — | — | 21,000 |
| Mechanical keyboards | $1.11 | $0.91 | 40% | 83,000 |

**Below the line — avoid, including one that looks attractive:**

| Niche | Median | Note |
| --- | --- | --- |
| Fishing reels | $0.90 | |
| RC cars | $0.77 | Mean $2.07 — a few costly items, most cheap |
| Smart home / Zigbee | **$0.50** | 146,000 orders and **0%** of results over $40 |
| **Dog products (current)** | **$0.29** | |

Zigbee is the instructive one: the highest order volume of anything tested and
the second-worst economics on the list. Volume is not the metric.

### The trap in this table

**AliExpress keyword search sorted by volume returns accessories, not
machines**, in almost every category. "Espresso machine" comes back with a
median price of $8.34 — that is descaling tablets and portafilter baskets.
"Electric bike" returns $8.83: lights, grips, brake pads. "Mini PC" returns
$5.33.

The four niches above the line survive precisely because the category's
best-selling item *is* the expensive object. Any niche picked off a keyword's
apparent popularity, without checking the price distribution underneath it,
inherits LookDog's problem exactly.

### The counterweight nobody should skip

High commission per order is bought with conversion rate. Buyer forums and
comparison write-ups consistently report that warranty cover, returns and
shipping time stop people buying expensive electronics from AliExpress, and that
the price advantage stops being decisive somewhere in the low hundreds of
dollars. A $500 power station earns $35 *if it converts*, and there is no
published figure for how much worse it converts than a $6 chew toy.

So the two ends of the table are not directly comparable, and the honest
position is that **we do not know where the crossover sits**. That is an
argument for the middle of the table, where the price advantage is still
obviously real to a buyer and the commission is still 6–12× what we earn now.

## 4. An account-level risk that is not in the main README

`scripts/lookdog-click-log.php` records, in its header comment, that **AliExpress
penalised this account on 31 August 2026** under a scheme targeting fraudulent
click performance, on the same day the daily click count jumped from 4 to 25 to
45 to 52 across 118 different products at all hours — the signature of the
crawler visible in section 1.

This is documented in one source file and nowhere else. It matters to this
decision for one specific reason: **a second site run through the same tracking
ID inherits that account's standing.** If the new site is worth building, it is
worth establishing first whether the penalty is still in force, and worth
considering whether the new property should sit on its own tracking ID so that
one site's crawler problem cannot take down both.

Status not established here. The API answers normally and returns valid
`promotion_link` values, but that is not the same as the account being in good
standing, and nothing in the API response reports standing.

## 5. Recommendation

**Do not choose a niche. Choose a price band, then a niche inside it.**

1. **Build in the $40–200 band, not at $5 and not at $500.** Dash cams and car
   tech is the strongest single candidate tested: $3.58 mean commission per
   order — **six times** the current catalogue — with 109,000 orders of volume
   behind the sample and 40% of results in the band where AliExpress is still
   the obviously cheaper option. Cycling GPS and lights is the close second on
   almost identical numbers, and is the better pick if the subject matter is
   more interesting to write about, which over two years of articles is not a
   soft consideration.

2. **Treat power stations and laser engravers as the high-upside option, not the
   safe one.** 24–51× per order is too large a gap to dismiss, but it is
   unproven against the trust barrier, and both are competitive in English-language
   affiliate search. Worth a test, not worth being the whole bet.

3. **Do not build the new site AliExpress-only.** The flat 7% and the three-day
   cookie are a ceiling, not a starting point. The `/go/{slug}` redirect layer
   already abstracts the destination and is the single most portable thing in
   this repo — build the new site so that swapping a product's destination to
   Amazon (3%, but much higher conversion and credit on the whole cart) or to a
   brand's own programme is an option change, not a rebuild.

4. **Separate the tracking ID** from LookDog's, per section 4.

5. **On timing, once:** LookDog is 23 days old and has not yet had a chance to
   rank for anything. A second site restarts that clock at zero while doubling
   the writing load, and the first site's real answer — does this content model
   earn search traffic — arrives in roughly three to six months whatever we do.
   That is a reason to keep publishing on LookDog while the new site is built,
   not a reason to skip the new site; the economics in section 2 are real and no
   amount of LookDog traffic fixes a 29-cent order.

## What is portable from LookDog

Worth listing, because it is most of the value in this repository and it is
niche-independent:

- The import and idempotency model (`_lookdog_ae_id`), the 3–6 ID batching and
  retry, and the rate-limit signature that looks like delisting and is not.
- Every SureRank finding — the single serialized `surerank_settings_general`
  key, the separate `post_no_index` key, the sitemap batch that must be run as a
  whole set, and `surerank_auto_set_image_title_and_alt`.
- The Astra findings: the ignored `mobile-header-breakpoint`, the hardcoded
  922px rules, the transparent-header menu-link selector.
- The editorial rule that every guide names something its category cannot do.
  This is the only real defence a site with no first-hand testing has, and it
  transfers to any niche unchanged.
- The derived-not-typed rule, the shortcode-over-`post_content` pattern, the
  dated-snapshot honesty on prices, and the `/go/` link layer.

## Method, and what was not checked

Everything in sections 1–3 was read live on 16 September 2026: the click log and
catalogue meta from the WordPress database, the commission and price figures
from the AliExpress Portals API through `lookdog_ae_call()`.

Not established, and needed before committing:

- Whether the 31 August penalty is still in force.
- Actual click-to-order conversion at any price point. We have never had a
  recorded sale, so every conversion figure in section 2 is an assumption.
- Search competition and keyword volume in the candidate niches. The commission
  table says what an order is worth; it says nothing about how hard the traffic
  is to win, and dash cams may well be harder to rank for than dog toys.
- Whether 30 results per keyword is a fair sample of a category's price
  distribution. It is a sample of what sells, which is the relevant thing for a
  catalogue, but it is not the catalogue.
