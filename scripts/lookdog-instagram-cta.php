<?php
/**
 * LookDog - Instagram follow block.
 *
 * Appends a follow call-to-action to the end of single blog posts, where a
 * reader who has finished an article is most likely to act. Colours and type
 * are taken from the Astra global palette so it matches the rest of the site
 * rather than introducing a second look:
 *   #14213D navy (theme + headings), #F97316 orange (accent), Poppins.
 *
 * Posts only, by design. Product pages already push the reader to AliExpress
 * and a second competing call to action there costs affiliate clicks.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-instagram-cta.php
 * Reads its URL from lookdog_social_profiles() in lookdog-social-schema.php.
 */

/**
 * Render the follow block.
 *
 * @return string
 */
function lookdog_instagram_cta_html() {
	$profiles = function_exists( 'lookdog_social_profiles' ) ? lookdog_social_profiles() : array();
	$url      = $profiles[0] ?? 'https://www.instagram.com/lookdog435/';
	$handle   = '@lookdog435';

	$glyph = '<svg class="lookdog-ig__glyph" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. '<rect x="2" y="2" width="20" height="20" rx="5.5"/>'
		. '<circle cx="12" cy="12" r="4.2"/>'
		. '<circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none"/>'
		. '</svg>';

	ob_start();
	?>
<aside class="lookdog-ig">
	<div class="lookdog-ig__mark"><?php echo $glyph; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<div class="lookdog-ig__body">
		<p class="lookdog-ig__title">Follow LookDog on Instagram</p>
		<p class="lookdog-ig__copy">New product picks, quick buying tips, and a great many dogs.</p>
	</div>
	<a class="lookdog-ig__cta" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="me noopener noreferrer">
		<?php echo $glyph; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php echo esc_html( $handle ); ?></span>
	</a>
</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * Styles. Scoped to .lookdog-ig so nothing leaks into Astra.
 *
 * @return void
 */
function lookdog_instagram_cta_styles() {
	?>
<style id="lookdog-ig-css">
.lookdog-ig{
	--ig-navy:#14213D;
	--ig-accent:#F97316;
	display:flex;
	align-items:center;
	gap:20px;
	flex-wrap:wrap;
	background:var(--ig-navy);
	color:#fff;
	border-radius:14px;
	padding:26px 28px;
	margin:44px 0 8px;
	font-family:'Poppins',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
.lookdog-ig__mark{
	display:flex;align-items:center;justify-content:center;
	width:46px;height:46px;flex:0 0 46px;
	border-radius:12px;
	background:rgba(255,255,255,.10);
	color:#fff;
}
.lookdog-ig__body{flex:1 1 260px;min-width:0;}
.lookdog-ig__title{
	margin:0 0 4px;
	font-size:1.06rem;
	font-weight:600;
	line-height:1.3;
	color:#fff;
}
.lookdog-ig__copy{
	margin:0;
	font-size:.93rem;
	line-height:1.5;
	color:rgba(255,255,255,.76);
}
.lookdog-ig__cta{
	display:inline-flex;align-items:center;gap:9px;
	background:var(--ig-accent);
	color:#fff !important;
	font-weight:600;
	font-size:.95rem;
	line-height:1;
	padding:13px 20px;
	border-radius:9px;
	text-decoration:none !important;
	box-shadow:none;
	transition:transform .15s ease, background-color .15s ease;
	white-space:nowrap;
}
.lookdog-ig__cta:hover,.lookdog-ig__cta:focus{
	background:#EA670B;
	color:#fff !important;
	transform:translateY(-1px);
}
.lookdog-ig__cta:focus-visible{outline:3px solid rgba(255,255,255,.7);outline-offset:3px;}
@media (prefers-reduced-motion: reduce){
	.lookdog-ig__cta{transition:none;}
	.lookdog-ig__cta:hover,.lookdog-ig__cta:focus{transform:none;}
}
@media (max-width:600px){
	.lookdog-ig{padding:22px;gap:16px;}
	.lookdog-ig__cta{width:100%;justify-content:center;}
}
</style>
	<?php
}

add_filter(
	'the_content',
	static function( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return $content . lookdog_instagram_cta_html();
	},
	25
);

add_action(
	'wp_head',
	static function() {
		if ( is_singular( 'post' ) ) {
			lookdog_instagram_cta_styles();
		}
	},
	20
);
