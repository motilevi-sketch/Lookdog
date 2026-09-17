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
 * whether a post actually sent anyone - and counted with crawlers held apart
 * from people, because without that split they are the same number and it
 * answers nothing. See lookdog_go_count().
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

/**
 * Count the hit. Per-day, trimmed to 120 days so the option cannot grow forever.
 *
 * Crawlers are counted apart from people rather than dropped.
 *
 * This counter had no bot check at all, and the numbers it produced were not
 * merely inflated - they were unreadable. On 15 September between 10:20 and
 * 10:27 it recorded exactly one hit on each of ten different slugs: shed, pull,
 * chew, gulp, hot, bark, lost, car, teeth and fix. A person follows one link. A
 * flat sweep of every chip on the /start page within seven minutes is something
 * walking the page, and counting it as campaign traffic made a link that had
 * sent nobody look like a link that was working.
 *
 * Bots are still recorded, because "eight hits, all automated" is a useful
 * answer and a silently discarded hit is not. `last_human` is kept separately:
 * on a campaign link the question is rarely how many, it is whether a real
 * person has used it since the post went up.
 */
function lookdog_go_count( $slug ) {
	$stats = (array) get_option( 'lookdog_go_stats', array() );
	$day   = gmdate( 'Y-m-d' );

	// The date the split began, so a reader can tell which figures were counted
	// with a bot check and which were not. Everything recorded before this is a
	// raw hit count and cannot be repaired: there is no record of what sent it.
	if ( ! get_option( 'lookdog_go_split_since' ) ) {
		update_option( 'lookdog_go_split_since', $day, false );
	}

	$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
	$bot = function_exists( 'lookdog_click_is_bot' )
		? lookdog_click_is_bot( $ua )
		: ( '' === trim( $ua ) || (bool) preg_match( '~bot|crawl|spider|curl|wget|python~i', $ua ) );

	$row = isset( $stats[ $slug ] ) ? $stats[ $slug ] : array( 'total' => 0, 'days' => array() );
	$row['total']        = (int) $row['total'] + 1;
	$row['days'][ $day ] = ( isset( $row['days'][ $day ] ) ? (int) $row['days'][ $day ] : 0 ) + 1;
	$row['last']         = time();

	if ( $bot ) {
		$row['bots']             = ( isset( $row['bots'] ) ? (int) $row['bots'] : 0 ) + 1;
		$row['bot_days'][ $day ] = ( isset( $row['bot_days'][ $day ] ) ? (int) $row['bot_days'][ $day ] : 0 ) + 1;
	} else {
		$row['last_human'] = time();
	}

	foreach ( array( 'days', 'bot_days' ) as $map ) {
		if ( ! empty( $row[ $map ] ) && count( $row[ $map ] ) > 120 ) {
			ksort( $row[ $map ] );
			$row[ $map ] = array_slice( $row[ $map ], -120, null, true );
		}
	}

	$stats[ $slug ] = $row;
	update_option( 'lookdog_go_stats', $stats, false );
}

/**
 * One campaign link's figures, with the crawlers taken out.
 *
 * `humans` is only meaningful for hits recorded after `split_since`. Before
 * that date nothing checked the user agent, so a raw total is all there is -
 * and on this site most of it was a crawler sweeping the /start page.
 *
 * @param string $slug Campaign slug.
 * @return array{total:int,bots:int,humans:int,last_human:int,split_since:string,split_total:int}
 */
function lookdog_go_row( $slug ) {
	$stats = (array) get_option( 'lookdog_go_stats', array() );
	$row   = isset( $stats[ $slug ] ) ? $stats[ $slug ] : array();
	$since = (string) get_option( 'lookdog_go_split_since', '' );
	$total = isset( $row['total'] ) ? (int) $row['total'] : 0;
	$bots  = isset( $row['bots'] ) ? (int) $row['bots'] : 0;

	// Hits counted since the split started, which is the only window where
	// "humans" means anything.
	$split_total = 0;
	if ( '' !== $since && ! empty( $row['days'] ) ) {
		foreach ( $row['days'] as $day => $n ) {
			if ( $day >= $since ) {
				$split_total += (int) $n;
			}
		}
	}

	return array(
		'total'       => $total,
		'bots'        => $bots,
		'humans'      => max( 0, $split_total - $bots ),
		'last_human'  => isset( $row['last_human'] ) ? (int) $row['last_human'] : 0,
		'split_since' => $since,
		'split_total' => $split_total,
	);
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
