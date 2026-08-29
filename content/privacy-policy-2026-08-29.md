# Privacy Policy — rewritten 29 August 2026

Live at https://lookdog.club/privacy-policy/ (post 3279). This file is the
record of what the page says and why it was changed.

## What was wrong with the previous version

It was not boilerplate — it was a real, sensibly structured policy. But it had
drifted out of step with what the site now actually does:

1. **"Google Analytics (if enabled)"** — a hedge that stopped being true the
   moment GA4 was switched on. A policy that says "if" about something that is
   definitely happening is worse than one that omits it.
2. **No mention of consent at all.** The banner, Consent Mode, the denied-by-
   default position and the Cookie settings link were all missing — which is
   the single most important thing the policy now has to describe, and the
   thing the banner itself links to the policy to explain.
3. **"Cookies and Your Choices" pointed at browser settings.** That was the
   only advice available before there was a consent control. Leaving it as the
   sole route implies no on-site choice exists.
4. **No cookie list.** Naming what is actually set is expected practice, and
   the consent choice here is stored in localStorage rather than a cookie,
   which is a distinction worth being explicit about.
5. **No legal bases.** UK/EU GDPR Article 13 requires the lawful basis for
   each purpose to be stated.
6. **No international transfer statement**, despite sending data to Google and
   handing visitors to AliExpress.
7. **Processors unnamed.** "Hosting provider", "Email/newsletter provider" —
   these are Hostinger, and the newsletter is Hostinger Reach.
8. **Contact form storage understated.** SureForms writes submissions to
   `wp_srfm_entries` in the site's own database, not just to an email. The old
   text implied email only.
9. **Nothing about the new outbound click counting** added the same week.

## What the new version adds

- **The Short Version** — four lines at the top, because a policy nobody reads
  protects nobody.
- **Analytics and Your Consent** — denied by default worldwide, what Accept and
  Reject each do, the 12-month memory, and the withdrawal route.
- **Cookies and Local Storage** — a table naming `ld_consent_v1`, `_ga`,
  `_ga_<id>` and the WordPress session cookies, with when each is set and how
  long it lasts.
- **How We Count Link Clicks** — describes the `/out/{id}` and `/go/` counters
  honestly and states plainly that they hold totals only, with no identifier
  connecting a click to a person. This is a genuine strength and is written as
  one.
- **Legal basis table** — consent for analytics and newsletter, legitimate
  interests for enquiries and security, none required for aggregate counts.
- **Who Processes Data For Us** — Google, Hostinger, AliExpress, and the site's
  own database, each with what they handle.
- **International Transfers** — Google's reliance on the Data Privacy
  Frameworks and Standard Contractual Clauses.
- **Concrete retention periods** in place of "as long as needed".

## Deliberately not done

- **No company address or registration number.** The controller is identified
  by email only, which is thin for GDPR. Supplying a real address is the
  owner's decision, not something to invent.
- **No claim of compliance.** The closing note that this is not legal advice
  was in the original and is kept.

## Open item flagged to the owner

`woo-cart-abandonment-recovery` is active. It exists to capture email addresses
at checkout and chase people who leave without buying. This site has no
checkout and has never taken an order, so its table (`wp_cartflows_ca_cart_
abandonment`) is empty and it collects nothing — but it is an active
data-capture plugin with no purpose here. SureCart and WooCommerce Payments are
in the same position. Deactivating all three removes the question entirely.
