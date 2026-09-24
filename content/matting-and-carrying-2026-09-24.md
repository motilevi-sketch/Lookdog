# Two new guides, and a tagging pass — 24 September 2026

## Why these two

Of 262 products, 137 had no specific article: only the generic category guide.
Grouping those by subject, two stood out.

- **Matting.** The most-clicked product on the site in the click log
  (4837, double-sided detangling brush, 7 human clicks) carried no problem tag.
  It is also the subject the owner knows first-hand: Bell is a Shih Tzu.
- **Carrying a small dog.** Ten carriers, slings, trolleys and a stroller, the
  largest uncovered group by clicks (9), and not mentioned in any guide.

The click log is 162 human clicks over 20 days. That is a direction, not proof.

## Published

| Post | Title | Category | Shape | Photo |
|---|---|---|---|---|
| 5296 | My Dog's Coat Keeps Matting: Where It Starts and What Stops It | Common Problems | Problem catalogue: six places mats start, each with its own cause | 5297, Bell from above on the grooming table, coat parted |
| 5298 | Carrying a Small Dog: Sling, Backpack, Stroller or Trolley | Buying Guides | Decision-first, one table | 5299, Bell in a building lobby |

The last two articles were diagnostic (bath) and chronological (clipping), so
neither shape repeats. Both featured images are 4:3 crops of the owner's
photographs, checked by eye, with captions that describe only what is visible.

Every factual claim about a product traces to that product's own listing copy:
weight on one shoulder (4009), carriers that collapse around the dog (3347),
"extra large" not meaning extra large (5171), thinning blades leaving tracks on
a fine coat (4837), dematting blades cutting on the pull stroke (3897). The
carrier at 88% feedback (3361) is tagged but not linked from the body.

Health cautions are woven in rather than bolted on: grass seeds between the toes
and skin found under a clipped pelt are vet questions (5296); a dog that has
started asking to be carried may be sore rather than reluctant (5298).

## Wiring

- New problem tags `coat-mats` (4 products) and `carrying-a-small-dog`
  (10 products), each with a row in `lookdog_problem_reading_map()`.
- Clusters: 5296 joins `coat` in second place, 5298 joins `car` in second place,
  so both appear in the "Read next" block of the older guides as well as having
  their own.
- Product photo strips: 4355, 3897, 4837 and 4002, 4016, 4418.

## Tagging fixes

| Tag | Added | Why |
|---|---|---|
| pulls-on-the-lead | 4907 head halter | The pulling guide discusses head halters, with their caveats |
| walking-in-the-dark | 4844 LED lead | The dark-walks guide recommends a second light source |
| clipping-at-home | 3883, 5230, 5223, 5237, 3309 | Clippers and shears; 5237 is already linked from that article |
| overgrown-nails | 5216, 3869 | Nail grinders |
| bad-breath | 4823 oral spray | The guide rates sprays as weak; the product page now links to the article that says so |

Three tags created on 14 September had their slug as their display name
("clipping-at-home"), which is what their archive pages showed as a heading.
Renamed and given descriptions: Clipping at home, Overgrown nails, Won't hold
still for a bath.

**Deliberately not tagged:** the bath sprayer, basin and brushes. The bath guide
says plainly that no product fixes a frightened dog, and filing a sprayer
"for dogs who panic" under it would sell against the article.

## Drift found and fixed

The live `lookdog-related-reading.php` carried four comparison rows
(5098, 5099, 5100, 5101) that were never committed. The repo copy now matches the
server byte for byte.

One consequence to know about: row 5100 points at a **draft** from 2 September,
"Why the Same Brush Does Not Work on Every Dog's Coat". It lists 3890, 3897 and
4837. While it is a draft it is skipped. If it is ever published, a comparison
beats a problem tag, and those three products will send readers there instead of
to the matting guide.
