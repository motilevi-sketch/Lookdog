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
.ld-bio__row:hover .ld-bio__rowname{color:var(--accent-dark)}

.ld-bio__foot{padding:30px 20px 0;text-align:center}
.ld-bio__ghost{display:block;border:1px solid var(--ink);color:var(--ink);border-radius:4px;
padding:12px 18px;font-size:15px;font-weight:600;text-decoration:none;
transition:background .15s ease,color .15s ease}
.ld-bio__ghost:hover{background:var(--ink);color:#fff}
.ld-bio__disclosure{margin:20px 0 0;font-size:12px;line-height:1.6;color:var(--muted)}

@media (max-width:430px){
.ld-bio__head{padding:28px 20px 24px}
.ld-bio__shot img{height:190px}
}
</style>
	<?php
}, 20 );
