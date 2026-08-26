# Blog article: What Small Dogs and Big Dogs Can Eat

**Published:** 2026-08-26
**Post ID:** 3777
**URL:** https://lookdog.club/what-small-and-big-dogs-can-eat/
**Category:** Feeding & Nutrition (term 77, created for this post)
**Format:** Core Gutenberg blocks — 97 blocks, 7 tables, ~4,150 words
**SEO meta:** `surerank_settings_page_title` / `surerank_settings_page_description`

The article body lives in WordPress (post 3777) and is covered by post revisions.
This file records the structure and, more importantly, the basis for the numbers,
so any figure can be re-checked or defended if a reader challenges it.

## Structure

Written to serve two audiences in one piece. Beginners are told to stop after the
portion tables; the later sections are aimed at long-time owners.

1. The one idea that explains most of the difference (dose per kg)
2. Foods that are never safe — table
3. Why the same food is more dangerous for a small dog — table, 4kg vs 35kg
4. Safe everyday foods and portions — table, small vs large
5. The ten per cent rule
6. Feeding a small dog: pros and cons
7. Feeding a big dog: pros and cons
8. Small versus large, side by side — table
9. How much food per day — calorie table
10. Puppies: where size matters most (+ 2 tables)
11. For experienced owners: the parts that are still debated
12. Treats and chews, sized properly — table
13. Reading the label properly
14. If your dog eats something it should not
15. The short version

## Basis for the numbers

**Calorie table.** Resting energy requirement `RER = 70 × (kg ^ 0.75)`, then
maintenance `MER = RER × factor`. The published ranges use factors of 1.4 to 1.8,
which spans a quiet neutered adult through to an active dog. Recalculate rather
than copying if the table is ever revised.

**Chocolate thresholds.** Based on theobromine content of roughly 2 mg/g for milk
chocolate, 6 mg/g for dark and 15 mg/g for baking chocolate, against a threshold of
about 20 mg/kg where mild signs typically begin. Presented explicitly as "where
signs begin", never as a safe amount.

**Xylitol.** Hypoglycemia at approximately 0.1 g/kg; liver injury at higher doses.
A single piece of gum can contain 0.3–1.0 g, which is why one piece can matter for
a small dog.

**Onion** ~15–30 g/kg. **Macadamia** ~2 g/kg. **Grapes/raisins** deliberately given
no threshold — toxicity is idiosyncratic and no safe dose is established.

**Ten per cent rule figures.** 10% of MER at the 1.6 factor: ~37 kcal at 5 kg,
~144 kcal at 30 kg.

## Positions taken on contested topics

These were written to reflect the state of the evidence rather than the popular
opinion, and should not be "simplified" later without checking:

- **Raised feeding bowls.** A large Purdue observational study associated raised
  bowls with *increased* bloat risk in large and giant breeds. The article says so
  plainly and points readers to their vet — including a note that this applies to
  the elevated feeder sold on this site. Do not quietly drop this; selling the
  product is not a reason to withhold the finding.
- **Grain-free and DCM.** Presented as unresolved: no causal mechanism established,
  FDA stopped routine updates in 2022, research ongoing. Not stated as proven harm.
- **Raw feeding.** Benefits described as largely anecdotal; documented risks
  (bacterial shedding, household transmission, imbalance) stated without moralising.
- **Senior protein.** The older advice to restrict protein in healthy seniors is
  described as not having held up; restriction belongs to diagnosed conditions.
- **Food allergy testing.** Blood, saliva and hair tests described as unreliable;
  elimination diet given as the actual diagnostic route.

## Known issue, not caused by this article

SureRank per-post SEO titles and descriptions are **not reaching the front end**
anywhere on this site. Every post and product renders the theme-default
`Title - LookDog` and an auto-generated description instead of its stored
`surerank_settings_page_title` / `surerank_settings_page_description`.

The meta keys look correct (the plugin reads `surerank_settings_*` meta directly
elsewhere, e.g. `surerank_settings_post_no_index` in `inc/sitemap/sitemap.php`), and
the frontend class loads, so the cause is further in. This predates this article and
affects all 67 products plus both posts. Worth diagnosing properly.
