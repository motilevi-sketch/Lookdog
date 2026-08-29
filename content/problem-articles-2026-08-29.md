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

## Batch 2

| Article | Problem page | Treatment |
|---|---|---|
| My Dog Sheds Everywhere: What Actually Reduces It | sheds-everywhere | spec |
| How Hot Is Too Hot to Walk a Dog? | gets-too-hot | bulletin |
| Why Does My Dog Bark So Much? A Straight Answer | barks-too-much | field |

The line these hold:

- **Shedding** — opens by saying you cannot stop it and anything promising
  otherwise is selling something. The value is a tool-to-coat table: a
  deshedding blade does nothing on a poodle and cuts the topcoat on a spaniel,
  and clipping a double coat to reduce shedding permanently damages it.
- **Overheating** — the danger is heat plus humidity, not the forecast. Carries
  the seven-second pavement test, notes that the largest UK study found
  exertion rather than parked cars to be the usual trigger, and states that
  cooling gear makes a hot day comfortable without making a hot walk safe. The
  first-aid section reflects current guidance that cool water is correct and
  the old tepid-water advice was wrong.
- **Barking** — the hardest one to write honestly, because the site sells bark
  deterrents. It says they interrupt a habit and teach nothing, that dogs
  habituate within a fortnight, and that on a frightened dog they add a second
  unpleasant thing to a situation already causing fear — barking stops while
  the anxiety rises, which looks like success and is not. The free fix that
  ends most window barking is film on the glass.

## Batch 3 — the set is complete

| Article | Problem page | Treatment |
|---|---|---|
| My Dog Runs Off and Will Not Come Back | runs-off | spec |
| Walking a Dog in the Dark: What Drivers Can Actually See | walking-in-the-dark | ledger |
| My Dog Hates the Car: Sickness, Fear and Safe Restraint | hates-the-car | sequence |
| My Dog Has Bad Breath: What Actually Fixes It | bad-breath | primer |

- **Running off** separates two problems that get muddled — getting a dog to
  come back, and finding one already gone. The commercially useful part is a
  Bluetooth-versus-GPS table: a tag borrows passing phones, so in woodland it
  reports where the dog was when it last passed a human. Buying one for a dog
  that bolts in fields is the expensive mistake in this category.
- **Dark walking** is built on distances, which is why it took the ledger
  treatment. A car on dipped beams lights ~40m and needs ~73m to stop from
  60mph, so the target is being seen past 100m. Hi-vis yellow is named as a
  daytime material that performs no better than a white coat after dark.
- **Car** splits sickness from fear and gives each its own method. It states
  the thing most listings omit: a seatbelt tether is a restraint, not crash
  protection, and must clip to a harness rather than a collar.
- **Teeth** is the widest gap between what is sold and what works, so it opens
  by conceding it: brushing is the only thing shown to reliably control plaque,
  and everything else on the page — including products we sell — is a
  supplement or a distant second. A breath spray on established disease is
  called out as worse than nothing, because it removes the signal that would
  have sent the owner to a vet.

## Result

All ten problem pages now have an article behind them. Seven of the seven
design treatments are in use across the ten, so no two adjacent pieces read
the same.

The ratio that prompted this has moved from **237 products : 7 articles** to
**237 : 17**. Sitemap at 290 URLs.

## One defect fixed on the way

Nine internal links in the final batch were written without a trailing slash
and were resolving through a 301. They now point at the canonical URL. All
forty internal links across the ten articles verified 200.

## Two plugin bugs found while doing this

Both in SureRank's sitemap batch classes, both the same shape: a constructor
whose second argument defaults to `'any'`.

- `Sync_Posts()` with no arguments writes a `post-type-any` chunk and refreshes
  nothing. It has to be called per post type, per 20-item offset.
- `Sync_Taxonomies()` behaves identically, which is why a newly created category
  never reached the sitemap until it was called as
  `new Sync_Taxonomies($offset, 'category', 20)`.

Neither errors. Both look like a successful run.
