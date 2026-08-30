<?php
/**
 * LookDog - put the article at the top of the problem page it belongs to.
 *
 * The ten problem archives were built before the articles behind them existed,
 * so the traffic ran one way: each article linked out to its shop page and
 * nothing linked back. That is the wrong way round for both readers and search.
 *
 * A visitor landing on "chewing everything" from a search wants to know what to
 * do before they want a list of toys, and an article that nothing on the site
 * links to starts at a disadvantage no matter how good it is.
 *
 * Rendered above the search box and the product grid, and only where an article
 * actually exists, so a new problem tag without one shows nothing rather than a
 * broken promise.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-problem-lede.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * The article behind the problem tag being viewed, if there is one.
 *
 * Reuses the map in lookdog-related-reading.php rather than keeping a second
 * copy, so a new article is added in exactly one place.
 *
 * @return ?array{url:string,title:string,blurb:string}
 */
function lookdog_problem_lede_article() {
	if ( ! is_tax( 'product_tag' ) || ! function_exists( 'lookdog_problem_reading_map' ) ) {
		return null;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	$map = lookdog_problem_reading_map();
	if ( empty( $map[ $term->slug ] ) ) {
		return null;
	}
	list( $id, $blurb ) = $map[ $term->slug ];
	if ( 'publish' !== get_post_status( $id ) ) {
		return null;
	}
	return array(
		'url'   => (string) get_permalink( $id ),
		'title' => get_the_title( $id ),
		'blurb' => $blurb,
	);
}

add_action(
	'woocommerce_archive_description',
	static function () {
		$a = lookdog_problem_lede_article();
		if ( ! $a ) {
			return;
		}
		?>
<aside class="ld-lede">
	<p class="ld-lede__label"><?php esc_html_e( 'Read this first', 'lookdog' ); ?></p>
	<p class="ld-lede__title"><a href="<?php echo esc_url( $a['url'] ); ?>"><?php echo esc_html( $a['title'] ); ?></a></p>
	<p class="ld-lede__blurb"><?php echo esc_html( $a['blurb'] ); ?></p>
	<p class="ld-lede__more"><a href="<?php echo esc_url( $a['url'] ); ?>"><?php esc_html_e( 'Read the guide', 'lookdog' ); ?> &rarr;</a></p>
</aside>
<style id="lookdog-lede-css">
.ld-lede{margin:0 0 26px;padding:20px 22px;background:#14213D;border-radius:10px;color:#D6D9E0;max-width:760px}
.ld-lede__label{margin:0 0 6px;font-size:11.5px;font-weight:700;letter-spacing:.12em;
	text-transform:uppercase;color:#F97316}
.ld-lede__title{margin:0 0 8px;font-size:20px;line-height:1.25;font-weight:600}
.ld-lede__title a{color:#FFFFFF;text-decoration:none}
.ld-lede__title a:hover,.ld-lede__title a:focus{text-decoration:underline}
.ld-lede__blurb{margin:0;font-size:14.5px;line-height:1.6;max-width:62ch}
.ld-lede__more{margin:12px 0 0}
.ld-lede__more a{color:#F97316;font-size:14px;font-weight:600;text-decoration:none}
.ld-lede__more a:hover,.ld-lede__more a:focus{text-decoration:underline}
@media (max-width:600px){
	.ld-lede{padding:17px 18px}
	.ld-lede__title{font-size:18px}
}
</style>
		<?php
	},
	15
);
