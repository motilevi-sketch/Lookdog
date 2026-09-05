# Clipping Your Dog at Home — 5 September 2026

Post 5252, `/clipping-your-dog-at-home/`, 1,577 words, Buying Guides (75).

Written to close the gap the eight-product batch exposed: the grooming guide
covers coats, brushing, matting, bathing, drying, nails, ears and shedding, and
says nothing at all about clipping or scissoring. The clippers, shears and
grooming arm had no editorial home until this existed.

## Shape: chronological

None of the site's twenty-five articles used it. The existing set runs heavily
to "X or Y: what actually…" decision pieces and problem catalogues, and the
house rules forbid reusing the last article's shape.

A clip is a sequence, and the argument the article makes is that almost every
bad home haircut is a step done out of order rather than a bad tool. So the
article runs in that order: the week before, the morning of, where the dog
stands, choosing the length, the first pass, blade heat, the face and feet,
blending, afterwards.

## Where it sends readers away from the shop

Four times, which is the point of the piece:

- Double coats should not be clipped at all — brushing, not cutting.
- A matted coat is a groomer's job, often a sedated shave-down, and that is a
  decision for them and a vet.
- Faces and feet: "if you are not confident with scissors near a moving dog,
  this is the part to hand over", with the split arrangement named explicitly —
  groomer does faces, you do the body.
- Clipper burn: redness or a dog licking one spot afterwards is a vet call, not
  something to watch for a week.

## Product links: three, not four

The draft carried four. At 1,594 words that is double the one-per-800-words
guidance, so the weakest was cut.

The grooming hammock lost its place. Its real use is nail trimming, and this
article explicitly defers nails to another day — a link there would have been
placed for the sake of the product rather than the reader.

The three that stayed each sit where the sentence has just made the case:

| Product | Placement |
|---------|-----------|
| Grooming arm with table clamp | "Where the Dog Stands", after the paragraph on a dog turning round mid-clip |
| Type-C clipper | "The First Pass", after the explanation that a stalling blade pulls before it cuts |
| Curved and thinning shears | "The Face, the Feet and the Sanitary Area", after the case for cutting along a face rather than towards it |

Plus the two structural links the house rules require: one out-link to the
grooming guide at the bathing-and-brushing bridge, one to the Grooming archive
at the close.

## Verified live

- HTTP 200, one H1, eleven H2s, no PHP notices, no unrendered shortcodes
- Title tag: `Clipping Your Dog at Home | LookDog` (35 characters)
- Block comments balanced 40/40, anchors 5/5, no loose HTML chunks
- All three product links resolve as products; guide and category links present
- Pull quote — "Almost every bad home haircut is a step done out of order, not
  a bad tool." — verified verbatim in the body and rendering on the page
- On the blog index, and in the rebuilt sitemap (26 URLs)

## Separate finding, recorded here because it is the more important one

The Site URL registered in the AliExpress Portals account reads
`www.lookgog.club` — a typo for `lookdog.club`, g in place of d.

`lookgog.club` has no DNS record at all. The domain does not exist.

That is almost certainly the whole cause of the "Site information violation"
penalty: AliExpress checked the registered address, found nothing there, and
flagged the account. The appeal was approved on 3 September, but an approved
appeal does not correct the field — if the URL is left as it is, the next check
finds the same dead domain and the penalty returns.

Owner action, not a code change: Portals → Account Settings → Site URL →
`https://lookdog.club`.
