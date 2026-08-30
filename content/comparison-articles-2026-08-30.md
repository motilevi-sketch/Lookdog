# Four comparison articles — 30 August 2026

The site had 18 articles and none of them answered a buying question. Everything
was either "my dog pulls" (problem-led) or "how to choose a bed" (category-led).
Nothing put products side by side, which is the shape of the search somebody
makes with a card in their hand.

| Article | Words | Products compared |
|---|---|---|
| [AirTag, GPS or a Name Tag: Which Dog Tracker You Need](https://lookdog.club/dog-trackers-compared/) | 1,075 | 7 |
| [Slow Feeders Compared: Bowls, Lick Mats, Snuffle Mats and Balls](https://lookdog.club/slow-feeders-compared/) | 1,012 | 11 |
| [Cooling Mats Compared: Gel, Water, Ice Silk and Vests](https://lookdog.club/cooling-mats-compared/) | 914 | 8 |
| [Dog Harnesses Compared: Front-Clip, Back-Clip and Fit](https://lookdog.club/dog-harnesses-compared/) | 853 | 6 |

All four use the `spec` article variant — deliberately the same one. The other
guides each have their own art direction, but a comparison is a *family*, and a
reader should recognise one on sight.

## The three things each article refuses to do

**Sell the premise.** Every one opens by narrowing what the category can do:
no cooling mat refrigerates anything, no harness stops pulling, a Bluetooth tag
has no idea where it is, and a slow feeder full of too much food is still too
much food.

**Pretend near-identical products are different.** The slow feeder piece says in
as many words that three of our own bowls are the same object and to buy
whichever is cheapest. We could have written three enthusiastic paragraphs
instead. That is the sentence the whole site is for.

**Protect a listing from its own price.** The tracker article states the test —
a real GPS tracker needs a satellite receiver, a mobile radio and a SIM, so
anything under about $15 with no subscription mentioned is a Bluetooth tag
wearing the wrong name — and then applies it to our own $8.62 "GPS tracker" by
name, telling the reader to ask the seller and to buy the Bluetooth tag instead
if the answer is vague.

## Wiring

`lookdog_compare_reading_map()` in `lookdog-related-reading.php` inverts the
usual direction: it lists, per article, the products it compares. A product page
now prefers its comparison over the problem article over the category guide,
because somebody reading about one slow feeder has already chosen the category
and needs to know the bowls next to it are identical.

Each article also opens with a photo strip built from three of the products it
covers, through the existing `lookdog_article_photo_map()`.

## Verified

All 41 outbound links across the four articles return 200. All four render with
the `spec` variant, their tables and their photo strips. Sitemap rebuilt: 22
posts. The homepage band now lists 21 articles totalling 27,909 words, up from
17 and 24,055 this morning.
