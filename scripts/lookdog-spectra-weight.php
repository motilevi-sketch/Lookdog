<?php
/**
 * LookDog - keep the Spectra utility stylesheet off pages that do not use it.
 *
 * Updating Spectra Blocks from 0.0.8 to 1.0.6 on 5 September 2026 added a
 * measured 394KB of inline CSS to every page on the site - the
 * `spectra-gs-utility-classes` sheet, a site-wide surface of utility classes
 * printed whether or not anything on the page refers to them. Total page weight
 * went from about 250KB to about 650KB, on every URL, including the contact
 * page and the privacy policy.
 *
 * That matters here more than it would elsewhere. Search is the only channel
 * this site has that is growing, page weight is a ranking input, and the sheet
 * is render-blocking inline CSS rather than a cacheable file.
 *
 * WHY SCOPE IT RATHER THAN ROLL BACK OR REMOVE. Rolling back to 0.0.8 would
 * leave a knowingly outdated plugin in place, which is the opposite of what
 * this update round is for. Removing Spectra is very probably the right answer -
 * its own analytics report zero posts using its blocks, and the only two posts
 * containing one are a private page that redirects and a template part a
 * classic theme never renders - but that is the owner's decision, not a
 * side effect of a stylesheet fix.
 *
 * So the sheet is dropped where nothing needs it and kept where something does.
 * The test reads the post's own content, so a page built with Spectra tomorrow
 * keeps its styles with no further work.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-spectra-weight.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Does the thing being viewed actually contain a Spectra block?
 *
 * Errs towards keeping the stylesheet: anything that is not a single post or
 * page whose content can be read - a search page, a 404, an archive rendering
 * many posts - keeps it, because the cost of a missing style is a broken
 * layout and the cost of an extra one is only weight.
 *
 * @return bool
 */
function lookdog_page_uses_spectra() {
	if ( is_admin() || is_embed() ) {
		return true;
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			return false !== strpos( (string) $post->post_content, 'wp:uagb/' );
		}
		return true;
	}

	// The shop, category archives, the blog index and the front page are all
	// built from theme templates and this site's own code, none of which uses
	// a Spectra block.
	if ( is_front_page() || is_home() || is_archive() || is_post_type_archive() ) {
		return false;
	}

	return true;
}

/**
 * Drop the site-wide utility sheet where it is not needed.
 *
 * Registered on `wp_enqueue_scripts` at a very late priority: Spectra enqueues
 * on `enqueue_block_assets` at 99, which has already run by then, so the handle
 * exists and dequeuing it also removes the inline CSS attached to it.
 *
 * @return void
 */
function lookdog_spectra_trim() {
	if ( lookdog_page_uses_spectra() ) {
		return;
	}
	wp_dequeue_style( 'spectra-gs-utility-classes' );
	wp_dequeue_style( 'spectra-gs-jit' );
	wp_dequeue_style( 'spectra-gs-gen-sitewide' );
}
add_action( 'wp_enqueue_scripts', 'lookdog_spectra_trim', 9999 );
add_action( 'wp_print_styles', 'lookdog_spectra_trim', 1 );
