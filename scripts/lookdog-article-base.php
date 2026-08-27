<?php
/**
 * LookDog - shared article typography.
 *
 * The half every guide gets: measure, scale, rhythm, table and list treatment.
 * Variant styling lives in lookdog-article-variants.php.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-article-base.php
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', static function () {
	if ( ! function_exists( 'lookdog_is_guide' ) || ! lookdog_is_guide() ) {
		return;
	}
	?>
<style id="lookdog-article-base">
.ld-art{--ink:#14213D;--body:#3A3F4B;--muted:#5A5F6B;--line:#E6E6E1;--line2:#D5D5CE;
--surface:#F8F8F6;--surface2:#EFEFEC;--accent:#F97316;--accent-dark:#EA670B;--on-ink:#C9D0DC}

/* the meta strip is a comment link and an author byline; neither earns space */
.ld-art .entry-meta{display:none}

.ld-art .entry-header{margin-bottom:38px}
.ld-art .entry-title{color:var(--ink);text-wrap:balance;letter-spacing:-.015em}

.ld-art .entry-content{color:var(--body);font-size:18px;line-height:1.68}
.ld-art .entry-content>*{max-width:68ch;margin-left:auto;margin-right:auto}
.ld-art .entry-content p{margin:0 0 1.15em}
.ld-art .entry-content strong{color:var(--ink);font-weight:600}

.ld-art .entry-content h2{color:var(--ink);font-weight:600;letter-spacing:-.01em;
text-wrap:balance;margin:2.4em auto .7em}
.ld-art .entry-content h3{color:var(--ink);font-size:19px;font-weight:600;
margin:2em auto .5em}

.ld-art .entry-content a{color:var(--accent-dark);text-decoration:underline;
text-decoration-thickness:1.5px;text-underline-offset:2.5px;
text-decoration-color:rgba(249,115,22,.45);transition:text-decoration-color .15s ease}
.ld-art .entry-content a:hover{text-decoration-color:var(--accent)}

.ld-art .entry-content ul,.ld-art .entry-content ol{margin:0 auto 1.4em;padding-left:1.25em}
.ld-art .entry-content li{margin-bottom:.55em;padding-left:.2em}
.ld-art .entry-content li::marker{color:var(--accent)}

/* tables scroll in their own box so the page never moves sideways */
.ld-art .entry-content figure.wp-block-table{max-width:none;width:100%;margin:2.2em 0;
overflow-x:auto;-webkit-overflow-scrolling:touch}
.ld-art .entry-content table{width:100%;min-width:520px;border-collapse:collapse;
font-size:15px;line-height:1.5;font-variant-numeric:tabular-nums}
.ld-art .entry-content thead th{color:var(--ink);font-weight:600;text-align:left;
font-size:13px;letter-spacing:.05em;text-transform:uppercase;padding:11px 14px;
border-bottom:2px solid var(--ink);white-space:nowrap}
.ld-art .entry-content tbody td{padding:12px 14px;border-bottom:1px solid var(--line);
vertical-align:top;color:var(--body)}
.ld-art .entry-content tbody tr:last-child td{border-bottom:0}
.ld-art .entry-content tbody td:first-child{color:var(--ink);font-weight:600}

@media (max-width:600px){
.ld-art .entry-content{font-size:17px}
.ld-art .entry-content h3{font-size:18px}
}
</style>
	<?php
}, 21 );
