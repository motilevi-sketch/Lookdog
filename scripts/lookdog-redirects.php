<?php
/**
 * LookDog - permanent redirects for retired URLs.
 *
 * Retiring a page is not the same as deleting it. Anything that has been
 * published for a while has links pointing at it - from search results, from
 * somebody's bookmarks, from a post on another site - and unpublishing it turns
 * every one of those into a 404 and throws away whatever standing the URL had.
 *
 * A 301 keeps both: the reader lands somewhere useful, and search engines pass
 * the old URL's history to the new one. WordPress does not do this on its own,
 * and installing a redirect plugin to hold three rules would be heavier than
 * the rules themselves.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-redirects.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retired path => where it should go now.
 *
 * Keys are paths relative to the site root, no leading or trailing slash, and
 * are matched exactly. Values are either a post ID or an absolute URL.
 *
 * @return array<string,int|string>
 */
function lookdog_redirect_map() {
	return apply_filters( 'lookdog_redirect_map', array(
		// The About Us page said what the site does; the personal piece says
		// who is doing it and why, and carries everything from the old page
		// worth keeping. Two "about" destinations competed with each other.
		'about-us' => 5083,

		// Five products were in the catalogue twice. AliExpress numbers the
		// same item in two ID spaces that differ by exactly 2^51, the search
		// API answers in one and the site's own links in the other, and the
		// import de-duplicated on the ID string - so the same mat came in
		// twice under two numbers, at two prices. The duplicate is retired
		// and its URL points at the copy that was kept.
		'product/water-fill-gel-cooling-cushion'          => 3665,
		'product/ice-silk-cooling-bed-oval-rim'           => 3679,
		'product/real-time-gps-dog-collar-movement-alerts' => 4065,
		'product/two-in-one-tick-removal-tool'            => 4788,
		'product/lead-mounted-waste-bag-dispenser'        => 4244,
	) );
}

add_action(
	'template_redirect',
	static function () {
		$map = lookdog_redirect_map();
		if ( ! $map ) {
			return;
		}

		$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		$path = trim( $path, '/' );
		if ( '' === $path || ! isset( $map[ $path ] ) ) {
			return;
		}

		$target = $map[ $path ];
		$url    = is_numeric( $target ) ? get_permalink( (int) $target ) : (string) $target;

		// A redirect to a target that has itself gone is worse than the 404 it
		// was hiding, because it looks deliberate.
		if ( ! $url ) {
			return;
		}

		wp_safe_redirect( $url, 301 );
		exit;
	},
	0
);
