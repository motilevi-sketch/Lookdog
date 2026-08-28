<?php
/**
 * LookDog - search engine ownership verification tags.
 *
 * Google Search Console's HTML tag method looks for this meta on the URL the
 * property was registered against, which for a URL-prefix property is the
 * homepage. Printed on every page anyway: it costs one line, and it survives
 * Google re-checking ownership later against a different URL.
 *
 * These tokens are not secrets. They appear in the page source by design and
 * prove nothing on their own - they only match a property already tied to a
 * Google account. They live in an option so a token can be rotated without a
 * code change, and so Bing can be added the same way.
 *
 *   update_option( 'lookdog_site_verification', [ 'google' => '...', 'bing' => '...' ] );
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-site-verification.php
 */

defined( 'ABSPATH' ) || exit;

function lookdog_verification_tokens() {
	return (array) get_option( 'lookdog_site_verification', array() );
}

add_action( 'wp_head', static function () {
	$names = array(
		'google' => 'google-site-verification',
		'bing'   => 'msvalidate.01',
		'yandex' => 'yandex-verification',
	);

	foreach ( lookdog_verification_tokens() as $engine => $token ) {
		$token = trim( (string) $token );
		if ( '' === $token || empty( $names[ $engine ] ) ) {
			continue;
		}
		printf(
			'<meta name="%s" content="%s" />' . "\n",
			esc_attr( $names[ $engine ] ),
			esc_attr( $token )
		);
	}
}, 1 );
