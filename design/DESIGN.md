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
  lineStrong: "#D5D5CE"
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
components:
  buttons: "Solid #F97316 with white text at 4px radius for inline actions; white pill at 30px radius on dark photographic grounds only"
  cards: "White on #E6E6E1 hairline at 10px radius, shadow 0 1px 3px rgba(20,33,61,.06); used only where a card is a real click target"
  sections: "Alternate #FFFFFF and #F8F8F6 grounds to separate bands without borders; #14213D for the one band that needs weight"
dials:
  variance: 0.45
  density: 0.35
  motion: 0.15
---

# LookDog Navy & Ember

## Overview

LookDog is an affiliate guide site for dog owners. The job of the design is to
make product guidance scannable and trustworthy, not to sell a lifestyle mood.
The palette was captured from the site's existing Astra global colours and its
Poppins type, so this direction documents what LookDog already is rather than
replacing it.

The register is plain and practical. Copy on this site tells people what a
product does badly as well as what it does well, and the visual language should
match: clear hierarchy, real photography, no atmosphere for its own sake.

## Colors

`#14213D` deep navy carries every heading and is the brand's anchor. `#F97316`
ember orange is the single accent, reserved for calls to action and nothing else.
Neutrals are warm-biased off-whites (`#F8F8F6`, `#EFEFEC`) rather than cool greys,
which keeps them sympathetic to the orange without drifting into cream.

Two weights of hairline. `#E6E6E1` is the default divider and card border.
`#D5D5CE` is the heavier step, for the places a rule has to carry structure
rather than just separate: a table grid, a double rule under a masthead. Both
hold R=G>B, the same warm cast as the off-whites, so neither reads as grey.

Two further neutrals exist only for text sitting on the navy ground:
`#C9D0DC` for body copy and `#AEB6C6` for captions. They are the dark-ground
counterparts of `body` and `muted`, tinted toward the navy rather than plain grey
so the band reads as one temperature. They are never used on a light ground.

Section separation comes from alternating white and `#F8F8F6` grounds, not from
borders or dividers. One band per page may take the full navy ground when it
needs real weight.

## Typography

Poppins throughout, which the site already loads. Geometric sans, friendly
without being childish, and it holds up at both display and small sizes.
Headings sit at 600 with tight leading near 1.1. Body runs at 400 with relaxed
leading and a measure around 65 characters.

Uppercase labels take modest letter-spacing. Body does not.

## Layout

Bands stacked full width, each with an inner container capped at 1200px. Layout
families must vary down the page: a photographic hero, a horizontally scrolling
product rail, a responsive card grid, an asymmetric editorial split, plain text
columns. No family repeats.

At `variance` 0.45 the page is mostly composed and orderly, with asymmetry used
deliberately in one or two places rather than throughout. Generous whitespace at
`density` 0.35.

## Articles

Guides are the one place where per-page art direction is allowed, because six
guides that look identical read as generated. The variation is carried by
furniture, never by palette: how the title lands, how a section announces itself,
how tables are ruled, which ground the header sits on. Six named treatments -
ledger, field, sequence, bulletin, calm, spec - each chosen for what its guide is
about. A guide may set the header on the navy ground; that is its one heavy band.

Do not answer "make the articles look different" by giving each one its own
accent colour. One accent, six rhythms.

## Elevation & Depth

Elevation is nearly flat. Cards carry a hairline border and a whisper of shadow
tinted to the navy ground, never a black drop shadow and never a glow. Depth
comes from ground colour changes between bands, not from stacking.

## Shapes

One radius scale, held: 4px on inline buttons, 10px on cards, 30px pill only for
buttons sitting on photographic grounds where a pill reads better against an
image. No other radii.

## Components

Product and category cards share one anatomy: image panel on top at a fixed
height, body below with a heading, one line of copy, and a single action. Rails
scroll horizontally on narrow viewports rather than squashing.

Where a group of items is not clickable, use plain columns separated by a
hairline top rule instead of cards. A card implies a click target, and using one
for static text is false elevation.

## Do's and Don'ts

- Do use real product photography from the Media Library; the catalogue has it.
- Do keep orange for actions only. A heading, a badge and a rule must not all be
  orange on the same screen.
- Do alternate white and `#F8F8F6` grounds to mark bands.
- Do let one number or one image be large enough to anchor a section.
- Don't use emoji as section markers or as a substitute for iconography.
- Don't build rows of identical feature cards; vary the layout family instead.
- Don't introduce a second accent, a gradient, or any purple.
- Don't add motion beyond a small hover lift and a colour transition.
- Don't use serif anywhere; Poppins carries the whole site.
- Don't use `bodyOnInk` or `mutedOnInk` on a light ground; they are dark-band only.
