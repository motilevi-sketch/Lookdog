# Problem articles — batch 1 of 10, 29 August 2026

## Why these, and why now

The site had 237 product pages and 7 articles: 34 commercial pages for every
useful one. Google's helpful content system judges quality site-wide, and
"almost entirely affiliate product pages" is the profile it suppresses. Adding
more products makes that worse. Adding articles is the only lever that moves it.

The existing 7 guides are category-level ("Dog Grooming at Home"). Search
traffic arrives on specific questions, not categories. Ten problem pages were
already built with almost nothing feeding them.

**The plan: one article behind each of the ten problem pages.** It fixes the
ratio, targets real queries, and uses structure that already exists.

## Published in this batch

| Article | Problem page | Treatment |
|---|---|---|
| Why Does My Dog Pull on the Lead? What Actually Stops It | pulls-on-the-lead | sequence |
| How Do I Stop My Dog Chewing Everything? | chews-everything | field |
| My Dog Eats Too Fast: Why It Matters and What Helps | eats-too-fast | bulletin |

Filed under a new **Common Problems** category (89), kept separate from the
category buying guides so the two do not blur.

## The editorial line these hold

Each article says plainly where a product is not the answer. That is the site's
one real differentiator and the reason the guides can rank at all:

- **Pulling** — a trained habit rewarded thousands of times. A front-clip
  harness makes the walk survivable and teaches nothing. Choke and prong
  collars are named and rejected on both welfare and effectiveness grounds.
- **Chewing** — two different problems behind one complaint. Teething needs
  frozen textures and time. An adult wrecking skirting boards is bored, and
  damage at doors when alone is separation distress, which no toy touches.
- **Eating too fast** — the one case where a cheap product genuinely reduces a
  medical risk, stated with its limit: a slow feeder reduces one contributing
  factor to bloat and does not prevent it. Raised bowls are corrected — the
  evidence now points the other way. Prophylactic gastropexy is named as the
  thing that actually moves the odds, which is a conversation with a vet and
  not a purchase here.

## Still to write

pulls-on-the-lead, chews-everything and eats-too-fast are done. Remaining:
sheds-everywhere, gets-too-hot, barks-too-much, runs-off,
walking-in-the-dark, hates-the-car, bad-breath.

## Two plugin bugs found while doing this

Both in SureRank's sitemap batch classes, both the same shape: a constructor
whose second argument defaults to `'any'`.

- `Sync_Posts()` with no arguments writes a `post-type-any` chunk and refreshes
  nothing. It has to be called per post type, per 20-item offset.
- `Sync_Taxonomies()` behaves identically, which is why a newly created category
  never reached the sitemap until it was called as
  `new Sync_Taxonomies($offset, 'category', 20)`.

Neither errors. Both look like a successful run.
