<?php
/**
 * LookDog - the "N new" badge on the Blog menu item.
 *
 * There is an unmirrored mu-plugin on the server,
 * `wp-content/mu-plugins/lookdog-blog-badge.php`, that appends a count of posts
 * published in the last 30 days to the Blog link. The idea is good. The
 * implementation has three faults, and mu-plugins are outside the sandbox this
 * repo can write to, so this file corrects the output at a later priority
 * instead. **Delete the mu-plugin by hand and this file becomes the only
 * source.** Until then it runs first and this runs second.
 *
 * What was wrong with it:
 *
 * 1. It matched menu items on the literal title "Blog". Renaming the menu item
 *    silently killed the badge, and any other menu containing an item called
 *    Blog got one too - including the footer.
 * 2. `posts_per_page => 20` capped the count, so a busy month would have read
 *    "20 new" no matter how many went up.
 * 3. It ran on every nav render, including the footer menus, doing a post query
 *    each time.
 *
 * Matching is on the URL now, the count is uncapped and cached for an hour, and
 * only the site navigation is badged.
 *
 * A NOTE ON STALENESS, because this site cares about it: the page cache holds
 * HTML for up to seven days, so the number can lag by that much. That is
 * tolerable for a 30-day window and would not be for a shorter one. Do not
 * reuse this pattern for anything measured in hours.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-blog-badge.php
 */

defined( 'ABSPATH' ) || exit;

/** Posts published in the last 30 days. Cached for an hour. */
function lookdog_recent_post_count() {
	$count = get_transient( 'lookdog_recent_posts' );
	if ( false !== $count ) {
		return (int) $count;
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'date_query'             => array(
				array( 'after' => '30 days ago' ),
			),
		)
	);

	$count = (int) $q->found_posts;
	set_transient( 'lookdog_recent_posts', $count, HOUR_IN_SECONDS );

	return $count;
}

add_filter(
	'wp_nav_menu_objects',
	static function ( $items, $args ) {
		// Site navigation only. The footer widgets render the same links through
		// nav_menu widgets, which pass no theme_location; badging those as well
		// puts the same number on the page twice, which is noise.
		$location = isset( $args->theme_location ) ? (string) $args->theme_location : '';
		if ( ! in_array( $location, array( 'primary', 'mobile_menu' ), true ) ) {
			return lookdog_strip_blog_badge( $items );
		}

		$items = lookdog_strip_blog_badge( $items );
		$count = lookdog_recent_post_count();
		if ( ! $count ) {
			return $items;
		}

		$blog = trailingslashit( home_url( '/blog/' ) );
		foreach ( $items as $item ) {
			if ( trailingslashit( (string) $item->url ) !== $blog ) {
				continue;
			}
			$item->title .= ' <span class="ld-badge">' . esc_html( number_format_i18n( $count ) ) . ' new</span>';
		}

		return $items;
	},
	20,
	2
);

/** Remove whatever the mu-plugin appended, so only one badge can ever render. */
function lookdog_strip_blog_badge( $items ) {
	foreach ( $items as $item ) {
		// `#` cannot be the delimiter here: the pattern contains a hex colour.
		if ( false !== strpos( (string) $item->title, '<span style="display:inline-block;background:#F97316' ) ) {
			$item->title = trim( preg_replace( '~\s*<span style="display:inline-block;background:\#F97316.*?</span>~s', '', $item->title ) );
		}
	}
	return $items;
}

add_action(
	'wp_head',
	static function () {
		?>
<style id="lookdog-badge-css">
.ld-badge{display:inline-block;margin-left:6px;padding:2px 7px;border-radius:30px;
background:#F97316;color:#FFFFFF;font-size:10px;font-weight:700;line-height:1.5;
letter-spacing:.02em;vertical-align:middle}
</style>
		<?php
	},
	21
);

/** A new post changes the count; do not wait an hour to say so. */
add_action(
	'transition_post_status',
	static function ( $new, $old, $post ) {
		if ( 'post' === $post->post_type && ( 'publish' === $new || 'publish' === $old ) ) {
			delete_transient( 'lookdog_recent_posts' );
		}
	},
	10,
	3
);
