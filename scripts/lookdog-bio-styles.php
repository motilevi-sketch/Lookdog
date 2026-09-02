<?php
/**
 * LookDog - styles for the link-in-bio page.
 *
 * Printed only when [lookdog_bio_links] is on the page being rendered.
 * Tokens follow the lookdog-navy-ember design system: navy #14213D carries the
 * one heavy band, ember #F97316 is reserved for the single action, and the
 * bodyOnInk neutral appears on the navy header and nowhere else.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-bio-styles.php
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', static function () {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post || ! has_shortcode( $post->post_content, 'lookdog_bio_links' ) ) {
		return;
	}
	?>
<style id="lookdog-bio">
.ld-bio{--ink:#14213D;--body:#3A3F4B;--muted:#5A5F6B;--line:#E6E6E1;--surface:#F8F8F6;
--accent:#F97316;--accent-dark:#EA670B;--on-ink:#C9D0DC;
max-width:520px;margin:0 auto;padding:0 0 40px;font-family:Poppins,sans-serif;color:var(--body)}
.ld-bio *{box-sizing:border-box}

.ld-bio__head{background:var(--ink);padding:34px 26px 30px;text-align:center}
.ld-bio__mark{margin:0;font-size:26px;font-weight:600;line-height:1.1;color:#fff}
.ld-bio__strap{margin:10px 0 0;font-size:14px;line-height:1.55;color:var(--on-ink)}

.ld-bio__feature{padding:26px 20px 0}
.ld-bio__eyebrow{margin:0 0 10px;font-size:11px;font-weight:600;letter-spacing:.09em;
text-transform:uppercase;color:var(--muted)}
.ld-bio__featurecard{display:block;background:#fff;border:1px solid var(--line);border-radius:10px;
overflow:hidden;text-decoration:none;box-shadow:0 1px 3px rgba(20,33,61,.06);
transition:transform .15s ease,box-shadow .15s ease}
.ld-bio__featurecard:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(20,33,61,.09)}
.ld-bio__shot{display:block;background:var(--surface)}
.ld-bio__shot img{display:block;width:100%;height:230px;object-fit:cover}
.ld-bio__featurebody{display:block;padding:18px 18px 20px}
.ld-bio__featurename{display:block;font-size:19px;font-weight:600;line-height:1.25;color:var(--ink)}
.ld-bio__featurenote{display:block;margin-top:8px;font-size:14px;line-height:1.55;color:var(--body)}
.ld-bio__btn{display:block;margin-top:16px;background:var(--accent);color:#fff;border-radius:4px;
padding:13px 18px;text-align:center;font-size:15px;font-weight:600;transition:background .15s ease}
.ld-bio__featurecard:hover .ld-bio__btn{background:var(--accent-dark)}

.ld-bio__list{margin:30px 0 0;padding:0 20px}
.ld-bio__row{display:flex;align-items:center;gap:14px;padding:15px 0;
border-top:1px solid var(--line);text-decoration:none;color:inherit}
.ld-bio__row:last-child{border-bottom:1px solid var(--line)}
.ld-bio__rowimg{flex:0 0 56px;width:56px;height:56px;border-radius:4px;object-fit:cover;
background:var(--surface)}
.ld-bio__rowtext{flex:1 1 auto;min-width:0}
.ld-bio__rowname{display:block;font-size:16px;font-weight:600;color:var(--ink);line-height:1.3}
.ld-bio__rownote{display:block;margin-top:3px;font-size:13px;line-height:1.45;color:var(--muted)}
.ld-bio__count{flex:0 0 auto;font-size:13px;font-weight:600;color:var(--muted)}

/* Problems, as chips. A vertical list of ten rows would push the categories
   off the screen; chips fit the whole set in the height of three rows, which
   matters on the one page every social visitor lands on. */
.ld-bio__problems{margin:30px 0 0;padding:26px 20px 0;border-top:1px solid var(--line)}
.ld-bio__chips{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 18px}
.ld-bio__chip{display:inline-flex;align-items:center;gap:7px;
padding:9px 13px;border:1px solid var(--line2);border-radius:30px;
color:var(--ink);font-size:14px;font-weight:600;text-decoration:none;
transition:background .16s ease,border-color .16s ease}
.ld-bio__chip:hover,.ld-bio__chip:focus{background:var(--surface);border-color:var(--ink)}
.ld-bio__chip:focus-visible{outline:3px solid var(--accent);outline-offset:2px}
.ld-bio__chipn{color:var(--muted);font-size:12px;font-weight:600;
font-variant-numeric:tabular-nums}
.ld-bio__row:hover .ld-bio__rowname{color:var(--accent-dark)}

.ld-bio__foot{padding:30px 20px 0;text-align:center}
.ld-bio__ghost{display:block;border:1px solid var(--ink);color:var(--ink);border-radius:4px;
padding:12px 18px;font-size:15px;font-weight:600;text-decoration:none;
transition:background .15s ease,color .15s ease}
.ld-bio__ghost:hover{background:var(--ink);color:#fff}
.ld-bio__disclosure{margin:20px 0 0;font-size:12px;line-height:1.6;color:var(--muted)}

/* Bell, at the top. A social visitor arriving from a video has met the dog
   already; the logo alone throws that recognition away. */
.ld-bio__face{display:block;width:84px;height:84px;margin:0 auto 14px;border-radius:50%;
object-fit:cover;border:3px solid rgba(255,255,255,.9)}
.ld-bio__free{margin:12px 0 0;font-size:13.5px;line-height:1.55;color:#fff;
border-top:1px solid rgba(255,255,255,.18);padding-top:12px}

/* The three numbers. Read live, so they are a claim that can be checked
   rather than an adjective. */
.ld-bio__facts{display:grid;grid-template-columns:repeat(3,1fr);gap:0;margin:0;padding:0;
list-style:none;background:var(--surface);border-bottom:1px solid var(--line)}
.ld-bio__facts li{padding:16px 10px;text-align:center;border-right:1px solid var(--line)}
.ld-bio__facts li:last-child{border-right:0}
.ld-bio__facts b{display:block;font-size:21px;font-weight:600;color:var(--ink);
line-height:1.1;font-variant-numeric:tabular-nums}
.ld-bio__facts span{display:block;margin-top:5px;font-size:11.5px;line-height:1.35;color:var(--muted)}

/* The guides. The half of this site that is not for sale, and the half that
   was missing from the page every social visitor lands on. */
.ld-bio__reading{margin:30px 0 0;padding:26px 20px 0;border-top:1px solid var(--line)}
.ld-bio__read{display:flex;align-items:baseline;justify-content:space-between;gap:14px;
padding:13px 0;border-bottom:1px solid var(--line);text-decoration:none}
.ld-bio__readname{font-size:15px;font-weight:600;line-height:1.35;color:var(--ink)}
.ld-bio__read:hover .ld-bio__readname{color:var(--accent-dark)}
.ld-bio__readmeta{flex:0 0 auto;font-size:12px;color:var(--muted);white-space:nowrap;
font-variant-numeric:tabular-nums}
.ld-bio__reading .ld-bio__ghost{margin-top:18px}

/* Who writes this. */
.ld-bio__who{margin:30px 0 0;padding:26px 20px 0;border-top:1px solid var(--line)}
.ld-bio__whocard{display:flex;align-items:center;gap:14px;text-decoration:none;
background:var(--surface);border:1px solid var(--line);border-radius:10px;padding:14px}
.ld-bio__whocard:hover{border-color:var(--ink)}
.ld-bio__whoimg{flex:0 0 62px;width:62px;height:62px;border-radius:50%;object-fit:cover}
.ld-bio__whotext{flex:1 1 auto;min-width:0}
.ld-bio__whoname{display:block;font-size:15px;font-weight:600;color:var(--ink);line-height:1.3}
.ld-bio__whonote{display:block;margin-top:4px;font-size:13px;line-height:1.45;color:var(--muted)}

.ld-bio__social{display:flex;justify-content:center;gap:22px;margin:18px 0 0}
.ld-bio__social a{font-size:13.5px;font-weight:600;color:var(--ink);text-decoration:none;
border-bottom:2px solid var(--accent);padding-bottom:2px}
.ld-bio__social a:hover{color:var(--accent-dark)}

@media (max-width:430px){
.ld-bio__head{padding:28px 20px 24px}
.ld-bio__shot img{height:190px}
.ld-bio__facts b{font-size:19px}
.ld-bio__facts li{padding:14px 7px}
.ld-bio__facts span{font-size:11px}
}
@media (prefers-reduced-motion:reduce){
.ld-bio__featurecard{transition:none}
.ld-bio__featurecard:hover{transform:none}
}
</style>
	<?php
}, 20 );
