<?php
/**
 * LookDog - stop inviting Google to crawl empty comment feeds.
 *
 * WHY THIS EXISTS, with the numbers that produced it.
 *
 * Search Console's "Crawled - currently not indexed" list was mostly not pages
 * at all. It was feeds: /clipping-your-dog-at-home/feed/,
 * /product/treat-dispensing-puzzle-ball/feed/, /product/carrot-cotton-rope-
 * chew-toy/feed/ and more of the same. WordPress publishes a comment feed for
 * every single post and product, and this site had 289 of them answering 200 -
 * against a grand total of zero comments.
 *
 * Worse, it was advertising them. Every page carried
 * <link rel="alternate" type="application/rss+xml" ... Comments Feed> in its
 * head, which is an explicit invitation. Google accepted it, crawled them, and
 * threw all of them away.
 *
 * At the same time, 58 real product pages sat in "Discovered - currently not
 * indexed" with Last crawled: N/A. Never fetched, not once.
 *
 * That is the same budget. A crawler spending requests on 289 empty feeds is a
 * crawler not spending them on the catalogue. This closes the invitation and
 * sends anything that still asks back to the page it came from.
 *
 * WHAT THIS DELIBERATELY DOES NOT TOUCH.
 *
 * The main site feed at /feed/ stays. It is one URL, it carries the actual
 * writing, and feed readers and aggregators are a legitimate way to be found.
 * Only comment feeds are redirected.
 *
 * Paginated shop archives (/store/page/3/ and friends) also show up in that
 * report, and are left alone on purpose. They are thin, but they are a route a
 * crawler uses to walk the catalogue, and closing a discovery path while
 * complaining that products are not discovered would be working against the
 * point.
 *
 * This is housekeeping, not promotion. It frees crawl budget. It does not make
 * any existing page rank better.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-feed-cleanup.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stop printing feed links that point at a comment section, and keep the one
 * that points at the writing.
 *
 * feed_links_extra() emits the per-post comment feed - the 289 - plus category,
 * tag and author feeds. That one goes entirely.
 *
 * feed_links() emits two links, and core gives no way to ask for one of them:
 * the main content feed, which is worth advertising, and the site-wide comments
 * feed, which is not. Leaving it registered meant every page in the site
 * advertised a URL that the redirect below immediately sends away again. So it
 * is replaced with a single hand-printed link to the content feed.
 */
add_action(
	'after_setup_theme',
	static function () {
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'feed_links', 2 );
	}
);

add_action(
	'wp_head',
	static function () {
		printf(
			'<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />' . "\n",
			esc_attr( get_bloginfo( 'name' ) . ' &raquo; Feed' ),
			esc_url( get_feed_link() )
		);
	},
	2
);

/**
 * Send comment feeds back to the thing they were a feed of.
 *
 * A 301 rather than a 404: these URLs have been crawled and are known, and a
 * redirect tells a crawler where the value actually is instead of leaving it a
 * dead end to retry. Covers both the per-item feeds and the site-wide
 * /comments/feed/, all of which describe a comment section that does not exist.
 *
 * is_feed() alone would catch the main content feed too, which is why the test
 * is is_comment_feed().
 *
 * @return void
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! is_feed() || ! is_comment_feed() ) {
			return;
		}

		$target = is_singular() ? get_permalink() : home_url( '/' );
		wp_safe_redirect( $target ? $target : home_url( '/' ), 301 );
		exit;
	},
	1
);
