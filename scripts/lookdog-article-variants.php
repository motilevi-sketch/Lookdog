<?php
/**
 * LookDog - per-guide art direction.
 *
 * One palette and one typeface throughout; what changes is the furniture. Each
 * treatment is chosen for what its guide is about, not assigned arbitrarily.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-article-variants.php
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', static function () {
	if ( ! function_exists( 'lookdog_is_guide' ) || ! lookdog_is_guide() ) {
		return;
	}
	?>
<style id="lookdog-article-variants">
/* LEDGER - feeding. A document of doses and weights, so it reads like a ledger:
   ruled rows, banded tables, numerals given room. */
.ld-art--ledger .entry-title{font-size:clamp(31px,4.6vw,45px);line-height:1.1}
.ld-art--ledger .entry-header{padding-bottom:24px;border-bottom:3px double var(--line2)}
.ld-art--ledger .entry-content h2{font-size:26px;padding-top:22px;border-top:1px solid var(--line2)}
.ld-art--ledger .entry-content tbody tr:nth-child(odd){background:var(--surface)}
.ld-art--ledger .entry-content tbody td{border-bottom:1px solid var(--line)}
.ld-art--ledger .entry-content table{min-width:560px}

/* FIELD - safe play. Hazards and what to do about them; sections carry a bar
   like a warning marker, and the first table column reads as the hazard. */
.ld-art--field .entry-title{font-size:clamp(30px,4.4vw,42px);line-height:1.12}
.ld-art--field .entry-header{border-left:5px solid var(--accent);padding:2px 0 2px 22px}
.ld-art--field .entry-content h2{font-size:25px;border-left:5px solid var(--accent);
padding:1px 0 1px 18px;margin-left:auto}
.ld-art--field .entry-content thead th{background:var(--ink);color:#fff;border-bottom:0}
.ld-art--field .entry-content tbody td:first-child{background:var(--surface)}

/* SEQUENCE - grooming. The guide is an order of operations, so the sections
   are numbered and the numbers are the navigation. */
.ld-art--sequence .entry-content{counter-reset:ldsec}
.ld-art--sequence .entry-title{font-size:clamp(29px,4.2vw,40px);line-height:1.14}
.ld-art--sequence .entry-header{padding-bottom:20px;border-bottom:1px solid var(--line)}
.ld-art--sequence .entry-content h2{font-size:24px;position:relative;padding-left:52px;
min-height:38px;display:flex;align-items:center}
.ld-art--sequence .entry-content h2::before{counter-increment:ldsec;content:counter(ldsec,decimal-leading-zero);
position:absolute;left:0;top:0;width:38px;height:38px;display:flex;align-items:center;
justify-content:center;border:2px solid var(--ink);border-radius:50%;
font-size:14px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums}
.ld-art--sequence .entry-content h3{padding-left:52px}

/* BULLETIN - travel. Consequences stated urgently: a dark header, a large
   headline, and section labels that read like a notice board. */
.ld-art--bulletin .entry-header{background:var(--ink);margin:0 0 40px;padding:38px 30px 32px;
border-radius:4px}
.ld-art--bulletin .entry-title{color:#fff;font-size:clamp(32px,5.2vw,50px);line-height:1.04;
letter-spacing:-.025em}
.ld-art--bulletin .entry-content h2{font-size:15px;text-transform:uppercase;
letter-spacing:.13em;color:var(--accent-dark);margin-top:2.8em;padding-bottom:9px;
border-bottom:2px solid var(--ink)}
.ld-art--bulletin .entry-content h3{font-size:21px}
.ld-art--bulletin .entry-content thead th{border-bottom-width:3px}
.ld-art--bulletin .entry-content tbody td{border-bottom:1px solid var(--line)}

/* CALM - beds. The subject is rest, so the page rests: wider measure, more
   leading, headings that murmur rather than announce. No rules anywhere. */
.ld-art--calm .entry-content{font-size:19px;line-height:1.8}
.ld-art--calm .entry-content>*{max-width:64ch}
.ld-art--calm .entry-title{font-size:clamp(30px,4.4vw,43px);line-height:1.18;font-weight:500}
.ld-art--calm .entry-header{margin-bottom:48px}
.ld-art--calm .entry-content h2{font-size:27px;font-weight:500;margin-top:3em;
color:var(--ink);opacity:.92}
.ld-art--calm .entry-content thead th{border-bottom-color:var(--line2);border-bottom-width:1px;
color:var(--muted)}
.ld-art--calm .entry-content tbody td{border-bottom-color:#F0EEE9}

/* SPEC - trackers. A comparison document: full grid, uppercase labels,
   numerals aligned, nothing decorative. */
.ld-art--spec .entry-title{font-size:clamp(28px,4vw,39px);line-height:1.16}
.ld-art--spec .entry-header{background:var(--surface);padding:26px 24px;border:1px solid var(--line);
border-radius:3px;margin-bottom:36px}
.ld-art--spec .entry-content h2{font-size:14px;text-transform:uppercase;letter-spacing:.12em;
color:var(--muted);margin-top:2.9em;margin-bottom:1em}
.ld-art--spec .entry-content h3{font-size:20px;margin-top:1.7em}
.ld-art--spec .entry-content table{border:1px solid var(--line2)}
.ld-art--spec .entry-content thead th{background:var(--surface2);border:1px solid var(--line2);
border-bottom-width:2px}
.ld-art--spec .entry-content tbody td{border:1px solid var(--line);font-size:14.5px}

/* PRIMER - the puppy guide. Written for someone who has never done this and is
   being sold thirty things they do not need, so the furniture is reassurance:
   an oversized opening paragraph that answers before it sells, section headings
   sitting on a tinted band so the timeline is scannable at a glance, and a
   table ruled only horizontally so it reads as a checklist rather than a spec. */
.ld-art--primer .entry-title{font-size:clamp(30px,4.4vw,43px);line-height:1.12}
.ld-art--primer .entry-header{padding-bottom:22px}
.ld-art--primer .entry-content>p:first-of-type{font-size:20px;line-height:1.62;
color:var(--ink)}
.ld-art--primer .entry-content h2{font-size:21px;margin-top:2.8em;margin-bottom:.9em;
background:var(--surface);border-left:4px solid var(--accent);
padding:14px 18px;border-radius:0 4px 4px 0}
.ld-art--primer .entry-content thead th{background:transparent;color:var(--muted);
font-size:12px;text-transform:uppercase;letter-spacing:.1em;
border-bottom:2px solid var(--line2)}
.ld-art--primer .entry-content tbody td{border-bottom:1px solid var(--line);
padding-top:14px;padding-bottom:14px}

@media (max-width:600px){
.ld-art--primer .entry-content>p:first-of-type{font-size:18px}
.ld-art--primer .entry-content h2{padding:12px 14px;font-size:19px}
.ld-art--primer .entry-content tbody td{padding-top:12px;padding-bottom:12px}
.ld-art--bulletin .entry-header{padding:26px 20px 22px}
.ld-art--sequence .entry-content h2{padding-left:44px}
.ld-art--sequence .entry-content h2::before{width:32px;height:32px;font-size:13px}
.ld-art--sequence .entry-content h3{padding-left:0}
.ld-art--calm .entry-content{font-size:17.5px}
}
</style>
	<?php
}, 22 );
