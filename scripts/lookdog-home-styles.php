<?php
/**
 * LookDog - homepage section styles.
 *
 * Every value here comes from the active "LookDog Navy & Ember" design tokens.
 * Printed once, only on pages that use one of the homepage shortcodes.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-home-styles.php
 */

function lookdog_home_styles() {
	global $post;
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$content = (string) $post->post_content;
	$uses = has_shortcode( $content, 'lookdog_hero' )
		|| has_shortcode( $content, 'lookdog_product_rail' )
		|| has_shortcode( $content, 'lookdog_featured_guide' )
		|| has_shortcode( $content, 'lookdog_method' );
	if ( ! $uses ) {
		return;
	}
	?>
<style id="lookdog-home-css">
.ld-band{padding:80px 40px;}
.ld-band--tint{background:#F8F8F6;}
.ld-band--ink{background:#14213D;}
.ld-wrap{max-width:1200px;margin:0 auto;}
.ld-h2{color:#14213D;font-size:34px;line-height:1.12;margin:0;font-weight:600;}
.ld-h2--lead{margin-bottom:48px;}
.ld-textlink{color:#14213D;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid #F97316;padding-bottom:2px;white-space:nowrap;}
.ld-textlink:hover,.ld-textlink:focus{color:#EA670B;}

/* hero */
.ld-hero{position:relative;display:flex;align-items:center;min-height:580px;padding:80px 40px;overflow:hidden;}
.ld-hero__bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
/* Two scrims, not one. The horizontal gradient carries the headline; the
   vertical one exists for the header, because the top of this photograph is a
   bright room - mean luminance about 190 - and the top RIGHT corner, where the
   menu button sits, was the lightest point of the whole band. A white icon
   there was landing on roughly 3:1. With the top strip darkened it clears 5:1,
   and the logo top left gains as well. */
.ld-hero__scrim{position:absolute;inset:0;background:linear-gradient(180deg,rgba(20,33,61,.6) 0%,rgba(20,33,61,.28) 96px,rgba(20,33,61,0) 200px),linear-gradient(90deg,rgba(20,33,61,.88) 0%,rgba(20,33,61,.74) 46%,rgba(20,33,61,.42) 100%);}
.ld-hero__inner{position:relative;width:100%;}
.ld-hero__title{max-width:17ch;margin:0 0 20px;color:#FFFFFF;font-size:52px;line-height:1.08;font-weight:600;letter-spacing:-.01em;overflow-wrap:break-word;}
.ld-hero__copy{max-width:56ch;margin:0 0 32px;color:#C9D0DC;font-size:17px;line-height:1.6;overflow-wrap:break-word;}
.ld-hero__actions{display:flex;flex-wrap:wrap;gap:16px;}
.ld-pill{display:inline-block;background:#FFFFFF;color:#14213D;padding:14px 30px;border:2px solid #FFFFFF;border-radius:30px;text-decoration:none;font-weight:600;font-size:14px;transition:background .18s ease,color .18s ease;}
.ld-pill:hover,.ld-pill:focus{background:#F8F8F6;color:#14213D;}
.ld-pill--ghost{background:transparent;color:#FFFFFF;}
.ld-pill--ghost:hover,.ld-pill--ghost:focus{background:#FFFFFF;color:#14213D;}
.ld-pill:focus-visible{outline:3px solid #F97316;outline-offset:3px;}
.ld-hero__trust{max-width:64ch;margin:30px 0 0;color:#AEB6C6;font-size:13.5px;line-height:1.7;}

/* category band head */
.ld-cats__head{max-width:62ch;margin-bottom:44px}
.ld-cats__lede{margin:16px 0 0;color:#3A3F4B;font-size:16px;line-height:1.65}

/* rail */
.ld-rail__head{display:flex;align-items:baseline;justify-content:space-between;gap:26px;margin-bottom:32px;flex-wrap:wrap;}
.ld-rail{display:flex;gap:26px;list-style:none;margin:0;padding:0 4px 14px;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;}
.ld-rail__item{flex:0 0 232px;scroll-snap-align:start;}
.ld-pcard{display:flex;flex-direction:column;height:100%;background:#FFFFFF;border:1px solid #E6E6E1;border-radius:10px;overflow:hidden;text-decoration:none;box-shadow:0 1px 3px rgba(20,33,61,.06);transition:transform .18s ease,box-shadow .18s ease;}
.ld-pcard:hover,.ld-pcard:focus-within{transform:translateY(-2px);box-shadow:0 6px 18px rgba(20,33,61,.10);}
.ld-pcard__media{display:block;height:190px;background:#EFEFEC;overflow:hidden;}
.ld-pcard__media img{width:100%;height:100%;object-fit:cover;display:block;}
.ld-pcard__name{display:block;padding:16px 16px 0;color:#14213D;font-size:14px;font-weight:600;line-height:1.4;flex:1;}
.ld-pcard__cta{display:block;padding:12px 16px 16px;color:#EA670B;font-size:13px;font-weight:600;}
.ld-rail::-webkit-scrollbar{height:8px;}
.ld-rail::-webkit-scrollbar-thumb{background:#E6E6E1;border-radius:30px;}

/* featured guide */
.ld-guide{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:64px;align-items:center;}
.ld-guide__quote{margin:0;border-left:3px solid #F97316;padding-left:26px;}
.ld-guide__quote blockquote{margin:0;color:#FFFFFF;font-size:29px;line-height:1.22;font-weight:600;}
.ld-guide__quote figcaption{margin-top:18px;color:#AEB6C6;font-size:13px;letter-spacing:.06em;text-transform:uppercase;}
.ld-guide__kicker{margin:0 0 12px;color:#F97316;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;}
.ld-guide__title{margin:0 0 16px;color:#FFFFFF;font-size:28px;line-height:1.2;font-weight:600;}
.ld-guide__copy{margin:0 0 28px;color:#C9D0DC;font-size:15px;line-height:1.65;max-width:62ch;}
.ld-btn{display:inline-block;background:#F97316;color:#FFFFFF;padding:13px 26px;border-radius:4px;text-decoration:none;font-weight:600;font-size:14px;transition:background .18s ease;}
.ld-btn:hover,.ld-btn:focus{background:#EA670B;color:#FFFFFF;}
.ld-btn:focus-visible,.ld-textlink:focus-visible,.ld-pcard:focus-visible{outline:3px solid #F97316;outline-offset:3px;}

/* method */
.ld-method{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:48px;}
.ld-method__col{border-top:2px solid #14213D;padding-top:22px;}
.ld-method__stat{margin:0 0 14px;color:#14213D;font-size:40px;line-height:1;font-weight:600;letter-spacing:-.02em;}
.ld-method__stat span{display:block;margin-top:8px;color:#5A5F6B;font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;}
.ld-method__copy{margin:0;color:#3A3F4B;font-size:15px;line-height:1.65;}

@media (max-width:900px){
	.ld-guide{grid-template-columns:1fr;gap:40px;}
	.ld-guide__quote blockquote{font-size:24px;}
}
@media (max-width:900px){
	.ld-hero__title{font-size:40px;}
	/* Keep the top strip when the horizontal gradient flattens out, or the
	   header loses its contrast again on tablets and phones. */
	.ld-hero__scrim{background:linear-gradient(180deg,rgba(20,33,61,.55) 0%,rgba(20,33,61,0) 170px),linear-gradient(90deg,rgba(20,33,61,.90) 0%,rgba(20,33,61,.80) 100%);}
}
@media (max-width:640px){
	.ld-band{padding:56px 22px;}
	.ld-hero{min-height:0;padding:64px 22px;}
	.ld-hero__title{font-size:32px;max-width:none;}
	.ld-hero__copy{font-size:16px;}
	.ld-pill{padding:12px 24px;}
	.ld-h2{font-size:27px;}
	.ld-method{gap:34px;}
}
@media (prefers-reduced-motion: reduce){
	.ld-pcard{transition:none;}
	.ld-pcard:hover,.ld-pcard:focus-within{transform:none;}
}
</style>
	<?php
}
add_action( 'wp_head', 'lookdog_home_styles', 20 );
