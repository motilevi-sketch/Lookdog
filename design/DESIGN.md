---
name: "LookDog Navy & Ember"
description: "A calm navy-and-white commercial palette with a single ember-orange accent, built for scannable product guidance rather than lifestyle mood."
colors:
  bg: "#FFFFFF"
  surface: "#F8F8F6"
  surfaceAlt: "#EFEFEC"
  ink: "#14213D"
  body: "#3A3F4B"
  muted: "#5A5F6B"
  line: "#E6E6E1"
  accent: "#F97316"
  accentDark: "#EA670B"
  bodyOnInk: "#C9D0DC"
  mutedOnInk: "#AEB6C6"
typography:
  heading:
    fontFamily: "Poppins, sans-serif"
    fontWeight: "600"
  body:
    fontFamily: "Poppins, sans-serif"
    fontWeight: "400"
spacing:
  xs: "8px"
  sm: "14px"
  md: "26px"
  lg: "48px"
  xl: "80px"
rounded:
  sm: "4px"
  md: "10px"
  pill: "30px"
dials:
  variance: 0.45
  density: 0.35
  motion: 0.15
---

Mirror of the design system stored in WordPress (Novamira `save-design`, slug
`lookdog-navy-ember`). WordPress holds the live copy; this is the version-controlled
reference. Re-save there if you change it here.

The palette and type were **captured from the site's existing Astra globals**, not
invented: this documents what LookDog already looked like so new sections match
rather than clash.

## Colors

`#14213D` navy carries every heading. `#F97316` ember is the only accent and is
reserved for actions. Neutrals are warm-biased off-whites, which keeps them
sympathetic to the orange without drifting into cream.

`bodyOnInk` and `mutedOnInk` exist only for text on the navy band. They are the
dark-ground counterparts of `body` and `muted`, tinted toward navy rather than
plain grey. Never use them on a light ground.

## Layout

Bands stacked full width, inner container capped at 1200px. **Layout families must
vary down the page** and no family repeats. The homepage currently runs: cover
hero, horizontal product rail, card grid, asymmetric navy split, plain text columns.

At `variance` 0.45 the page is composed by default with asymmetry used once, on
purpose, rather than throughout.

## Do's and Don'ts

- Do use real product photography from the Media Library; the catalogue has it.
- Do keep orange for actions only.
- Do alternate `#FFFFFF` and `#F8F8F6` grounds to mark bands.
- Do let one number or one image anchor a section.
- Don't use emoji as section markers or as a substitute for iconography.
- Don't build rows of identical feature cards; vary the layout family instead.
- Don't introduce a second accent, a gradient, or any purple.
- Don't add motion beyond a small hover lift and a colour transition.
- Don't use serif anywhere; Poppins carries the whole site.
- Don't use `bodyOnInk` / `mutedOnInk` on a light ground.

## Where a card is allowed

A card implies a click target. Product and category cards are links, so they get
the card treatment. The "How things get on this site" columns are static text and
use a hairline top rule instead, because a card there would be false elevation.
