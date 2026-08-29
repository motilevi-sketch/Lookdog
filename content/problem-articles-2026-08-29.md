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

## Still to write

Done: pulls-on-the-lead, chews-everything, eats-too-fast, sheds-everywhere,
gets-too-hot, barks-too-much. Remaining: runs-off, walking-in-the-dark,
hates-the-car, bad-breath.

## Two plugin bugs found while doing this

Both in SureRank's sitemap batch classes, both the same shape: a constructor
whose second argument defaults to `'any'`.

- `Sync_Posts()` with no arguments writes a `post-type-any` chunk and refreshes
  nothing. It has to be called per post type, per 20-item offset.
- `Sync_Taxonomies()` behaves identically, which is why a newly created category
  never reached the sitemap until it was called as
  `new Sync_Taxonomies($offset, 'category', 20)`.

Neither errors. Both look like a successful run.
