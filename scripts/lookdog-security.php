<?php
/**
 * LookDog - hardening.
 *
 * An audit of the live site on 5 September 2026 found the application itself
 * sound - no exposed configuration, no directory listings, no leaked account
 * address, consent handled properly - but the HTTP layer wide open: not one of
 * the standard browser-side protections was being sent, the WordPress version
 * was readable from readme.html, and the author list was queryable by anyone.
 *
 * None of that is a break-in on its own. Together they are the reconnaissance
 * an automated attack does first, and they cost nothing to close.
 *
 * WHAT IS DELIBERATELY NOT HERE. Strict-Transport-Security is sent without
 * `includeSubDomains` and without `preload`. The site has a Hostinger preview
 * subdomain, and asserting HTTPS for every subdomain would break anything there
 * that is not; `preload` is close to irreversible once submitted, which is not
 * a decision a hardening file should take on the owner's behalf.
 *
 * Content-Security-Policy is also absent. A meaningful policy has to enumerate
 * every script this site loads - Google Analytics, LiteSpeed, WooCommerce,
 * Spectra, SureForms - and a wrong one silently breaks the buy button. It needs
 * to be built and tested deliberately, not guessed at here.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-security.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turn off the built-in theme and plugin file editors.
 *
 * With one administrator account, the editor's only real function is to turn a
 * stolen password into arbitrary PHP execution. Everything on this site is
 * deployed as files, so nothing legitimate needs it.
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Standard browser-side protections.
 *
 * Sent on the front end and in wp-admin alike. Values are conservative on
 * purpose: nothing here can change how a page renders.
 *
 * @return void
 */
function lookdog_security_headers() {
	if ( headers_sent() ) {
		return;
	}

	// PHP announces its exact version by default, which tells an attacker
	// precisely which vulnerabilities to try.
	header_remove( 'X-Powered-By' );

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000', true );
	}
	header( 'X-Content-Type-Options: nosniff', true );
	header( 'X-Frame-Options: SAMEORIGIN', true );
	header( 'Referrer-Policy: strict-origin-when-cross-origin', true );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()', true );
}
add_action( 'send_headers', 'lookdog_security_headers', 1 );
add_action( 'admin_init', 'lookdog_security_headers', 1 );
add_action( 'login_init', 'lookdog_security_headers', 1 );

/**
 * Stop anonymous visitors listing the site's accounts.
 *
 * /wp-json/wp/v2/users answers unauthenticated requests with every author on
 * the site. Here that is one administrator, which hands a password-guessing
 * script the only half of the credentials it cannot otherwise obtain. Logged-in
 * requests are untouched, so the editor and WooCommerce keep working.
 *
 * @param array $endpoints REST endpoints.
 * @return array
 */
function lookdog_security_hide_users( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	return $endpoints;
}
add_filter( 'rest_endpoints', 'lookdog_security_hide_users' );

/**
 * Close the same door on the other routes that open it.
 *
 * ?author=1 redirects to the author archive, whose URL contains the login name
 * unless a nickname is set; the oEmbed endpoint reports the author too. The
 * author archive itself is already disabled elsewhere - this covers the two
 * routes that reach the same information without going through it.
 */
add_action(
	'template_redirect',
	static function () {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}
		if ( isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	0
);
add_filter( 'oembed_response_data', static function ( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
} );

/**
 * Disable XML-RPC.
 *
 * It exists for the old desktop clients and the Jetpack-era mobile apps, none
 * of which touch this site. What it is used for in practice is password
 * guessing - system.multicall lets one request try hundreds of passwords - and
 * pingback requests that turn the site into a traffic amplifier.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * And refuse the endpoint outright.
 *
 * The filter above already turns every authenticated method away with a 405 -
 * verified against wp.getUsersBlogs, which is the one brute-force scripts call.
 * What it does not stop is system.listMethods, which still answers and confirms
 * the endpoint is live. Nothing here uses XML-RPC at all, so the file is closed
 * rather than left half open.
 */
add_action(
	'init',
	static function () {
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) : '';
		if ( 'xmlrpc.php' !== basename( $script ) ) {
			return;
		}
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo "XML-RPC is disabled on this site.\n";
		exit;
	},
	0
);
add_filter( 'xmlrpc_methods', static function ( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	return $methods;
} );
add_filter( 'wp_headers', static function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

/**
 * Say nothing useful when a login fails.
 *
 * WordPress distinguishes "unknown username" from "wrong password", which
 * confirms an account exists and turns guessing a login into a solved problem.
 *
 * @return string
 */
function lookdog_security_login_error() {
	return __( 'That combination is not correct.', 'lookdog' );
}
add_filter( 'login_errors', 'lookdog_security_login_error' );

/**
 * Drop the generator meta tag and version strings.
 *
 * The exact WordPress version tells an attacker which published vulnerabilities
 * apply before they try anything.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
