<?php
/**
 * LookDog - social follow block.
 *
 * Appended to single posts, where a reader who has finished an article is most
 * likely to act. Colours and type come from the Astra global palette so it
 * matches the site rather than introducing a second look.
 *
 * Posts only, by design. Product pages already push the reader to AliExpress and
 * a second competing call to action there costs affiliate clicks.
 *
 * Networks are read from lookdog_social_profiles_map(), so adding an account in
 * one place adds a button here. Replaces the earlier Instagram-only version,
 * which had the network hardcoded in its title, its glyph and its single button.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-follow-cta.php
 */

defined( 'ABSPATH' ) || exit;

/** Inline glyphs, so no icon font or third-party request is needed. */
function lookdog_social_glyph( $network ) {
	$open = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">';

	switch ( $network ) {
		case 'instagram':
			return $open
				. '<g fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">'
				. '<rect x="2.6" y="2.6" width="18.8" height="18.8" rx="5.2"/>'
				. '<circle cx="12" cy="12" r="4.1"/></g>'
				. '<circle cx="17.4" cy="6.6" r="1.15" fill="currentColor"/></svg>';

		case 'tiktok':
			return $open
				. '<path fill="currentColor" d="M16.6 2h-3.1v13.2a2.7 2.7 0 1 1-2.2-2.65v-3.2a5.9 5.9 0 1 0 5.3 5.87V9.1a8.7 8.7 0 0 0 4.4 1.35V7.3A5.6 5.6 0 0 1 16.6 2z"/></svg>';
	}

	return '';
}

function lookdog_social_label( $network ) {
	$labels = array(
		'instagram' => 'Instagram',
		'tiktok'    => 'TikTok',
		'facebook'  => 'Facebook',
		'twitter'   => 'X',
		'linkedin'  => 'LinkedIn',
	);
	return $labels[ $network ] ?? ucfirst( $network );
}

function lookdog_follow_cta_html() {
	$map = function_exists( 'lookdog_social_profiles_map' ) ? lookdog_social_profiles_map() : array();
	if ( empty( $map ) ) {
		return '';
	}

	$buttons = '';
	foreach ( $map as $network => $url ) {
		$glyph = lookdog_social_glyph( $network );
		if ( '' === $glyph ) {
			continue;
		}
		$buttons .= '<a class="lookdog-follow__cta" href="' . esc_url( $url ) . '"'
			. ' target="_blank" rel="me noopener noreferrer">'
			. $glyph
			. '<span>' . esc_html( lookdog_social_label( $network ) ) . '</span></a>';
	}
	if ( '' === $buttons ) {
		return '';
	}

	return '<aside class="lookdog-follow">'
		. '<div class="lookdog-follow__body">'
		. '<p class="lookdog-follow__title">Follow LookDog</p>'
		. '<p class="lookdog-follow__copy">New product picks, quick buying tips, and a great many dogs.</p>'
		. '</div>'
		. '<div class="lookdog-follow__actions">' . $buttons . '</div>'
		. '</aside>';
}

add_filter( 'the_content', static function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return $content . lookdog_follow_cta_html();
}, 25 );

add_action( 'wp_head', static function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	?>
<style id="lookdog-follow-css">
.lookdog-follow{display:flex;align-items:center;gap:22px;flex-wrap:wrap;
background:#14213D;color:#fff;border-radius:12px;padding:26px 28px;margin:46px 0 8px;
font-family:Poppins,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
.lookdog-follow__body{flex:1 1 260px;min-width:0}
.lookdog-follow__title{margin:0 0 4px;font-size:1.08rem;font-weight:600;line-height:1.3;color:#fff}
.lookdog-follow__copy{margin:0;font-size:.93rem;line-height:1.5;color:#C9D0DC}
.lookdog-follow__actions{display:flex;gap:10px;flex-wrap:wrap}
.lookdog-follow__cta{display:inline-flex;align-items:center;gap:9px;
background:#F97316;color:#fff !important;font-weight:600;font-size:.95rem;line-height:1;
padding:13px 20px;border-radius:8px;text-decoration:none !important;white-space:nowrap;
transition:transform .15s ease,background-color .15s ease}
.lookdog-follow__cta:hover,.lookdog-follow__cta:focus{background:#EA670B;color:#fff !important;
transform:translateY(-1px)}
.lookdog-follow__cta:focus-visible{outline:3px solid rgba(255,255,255,.7);outline-offset:3px}
@media (prefers-reduced-motion:reduce){
.lookdog-follow__cta{transition:none}
.lookdog-follow__cta:hover,.lookdog-follow__cta:focus{transform:none}}
@media (max-width:600px){
.lookdog-follow{padding:22px;gap:18px}
.lookdog-follow__actions{width:100%}
.lookdog-follow__cta{flex:1 1 0;justify-content:center}}
</style>
	<?php
}, 20 );
