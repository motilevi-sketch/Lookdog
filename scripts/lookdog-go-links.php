<?php
/**
 * LookDog - short campaign links.
 *
 * Gives social posts a URL short enough to read off a phone screen and type by
 * hand, which is the only way a link in an Instagram image can work at all.
 *
 *   https://lookdog.club/go/tug  ->  the product page, tagged as Instagram traffic
 *
 * Targets live in the `lookdog_go_links` option so a new campaign needs no code.
 * Hits are counted per day in `lookdog_go_stats`, which is the only way to know
 * whether a post actually sent anyone.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-go-links.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reserved slug for a bare /go/ with nothing after it.
 *
 * Without a rule for it, WordPress fell through to its own 404 guessing and
 * sent /go/ to whichever product it thought the word resembled - on the day
 * this was written, a tracker collar. A person typing the short link off a
 * phone screen and missing the last word landed on a random product page with
 * no idea why.
 */
const LOOKDOG_GO_INDEX = '__index';

function lookdog_go_targets() {
	return (array) get_option( 'lookdog_go_links', array() );
}

/**
 * Resolve a slug to a destination URL, or '' when the slug is unknown.
 * A target is either [ 'post' => 3553 ] or [ 'url' => 'https://...' ].
 */
function lookdog_go_resolve( $slug ) {
	$targets = lookdog_go_targets();
	if ( empty( $targets[ $slug ] ) ) {
		return '';
	}
	$t = $targets[ $slug ];

	if ( ! empty( $t['post'] ) ) {
		$post_id = (int) $t['post'];
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return '';
		}
		$url = get_permalink( $post_id );
	} else {
		$url = isset( $t['url'] ) ? $t['url'] : '';
	}
	if ( ! $url ) {
		return '';
	}

	// Tag the traffic so it is separable from organic search later.
	$utm = array(
		'utm_source'   => isset( $t['source'] ) ? $t['source'] : 'instagram',
		'utm_medium'   => isset( $t['medium'] ) ? $t['medium'] : 'social',
		'utm_campaign' => $slug,
	);

	return add_query_arg( $utm, $url );
}

/**
 * The short-link slug pointing at a given post, or '' when none does.
 *
 * Lets a page link to /go/tug without hardcoding "tug" next to a product id
 * that a filter can change underneath it.
 */
function lookdog_go_slug_for_post( $post_id ) {
	$post_id = (int) $post_id;
	foreach ( lookdog_go_targets() as $slug => $t ) {
		if ( ! empty( $t['post'] ) && (int) $t['post'] === $post_id ) {
			return (string) $slug;
		}
	}
	return '';
}

/** The best URL for a post: its short link when one exists, else the permalink. */
function lookdog_go_url_for_post( $post_id ) {
	$slug = lookdog_go_slug_for_post( $post_id );
	return $slug ? home_url( '/go/' . $slug ) : (string) get_permalink( $post_id );
}

/** Count the hit. Per-day, trimmed to 120 days so the option cannot grow forever. */
function lookdog_go_count( $slug ) {
	$stats = (array) get_option( 'lookdog_go_stats', array() );
	$day   = gmdate( 'Y-m-d' );

	$row = isset( $stats[ $slug ] ) ? $stats[ $slug ] : array( 'total' => 0, 'days' => array() );
	$row['total']         = (int) $row['total'] + 1;
	$row['days'][ $day ]  = ( isset( $row['days'][ $day ] ) ? (int) $row['days'][ $day ] : 0 ) + 1;
	$row['last']          = time();

	if ( count( $row['days'] ) > 120 ) {
		ksort( $row['days'] );
		$row['days'] = array_slice( $row['days'], -120, null, true );
	}

	$stats[ $slug ] = $row;
	update_option( 'lookdog_go_stats', $stats, false );
}

add_action( 'init', static function () {
	add_rewrite_rule( '^go/([A-Za-z0-9_-]+)/?$', 'index.php?lookdog_go=$matches[1]', 'top' );
	add_rewrite_rule( '^go/?$', 'index.php?lookdog_go=' . LOOKDOG_GO_INDEX, 'top' );
} );

add_filter( 'query_vars', static function ( $vars ) {
	$vars[] = 'lookdog_go';
	return $vars;
} );

add_action( 'template_redirect', static function () {
	$slug = get_query_var( 'lookdog_go' );
	if ( ! $slug ) {
		return;
	}

	// Bare /go/ is a landing page, not a campaign. It goes where the link in
	// bio goes, so the two can never disagree.
	if ( LOOKDOG_GO_INDEX === $slug ) {
		$start = get_page_by_path( 'start' );
		wp_safe_redirect( $start ? (string) get_permalink( $start ) : home_url( '/' ), 302 );
		exit;
	}

	$url = lookdog_go_resolve( $slug );

	// An unknown or unpublished target sends people to the homepage rather than
	// a 404. A dead link in a printed image cannot be corrected after posting.
	if ( ! $url ) {
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}

	lookdog_go_count( $slug );

	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	wp_redirect( $url, 302 );
	exit;
}, 1 );
