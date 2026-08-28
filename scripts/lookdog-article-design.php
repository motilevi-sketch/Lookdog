<?php
/**
 * LookDog - editorial article system.
 *
 * Guides rendered on the stock Astra post template were indistinguishable from
 * each other and from every other Astra blog. This gives them a real reading
 * layout, and gives each guide its own art direction inside one shared system.
 *
 * The variation is deliberately NOT colour. The saved design (lookdog-navy-ember)
 * allows one accent, and six accents would read as six websites. Each guide
 * instead differs in the shape of its furniture: how the title lands, how a
 * section announces itself, how tables are ruled, and which ground the header
 * sits on. Same palette, same face, seven distinct rhythms.
 *
 * Style per post lives in `lookdog_article_style` meta, so it survives without
 * code. The map below only seeds it.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-article-design.php
 */

defined( 'ABSPATH' ) || exit;

/** post id => style key. Seeds meta; meta wins once set. */
function lookdog_article_styles() {
	return apply_filters( 'lookdog_article_styles', array(
		3777 => 'ledger',   // feeding: doses, weights, calories - a numbers document
		3344 => 'field',    // safe play: hazards and what to do about them
		4497 => 'sequence', // grooming: an order of operations
		4498 => 'bulletin', // travel: physics and consequences, stated urgently
		4499 => 'calm',     // beds: rest, so the page should be restful
		4500 => 'spec',     // trackers: comparison and specification
		4524 => 'primer',   // puppy: a first-timer being sold thirty things they do not need
	) );
}

function lookdog_article_style( $post_id ) {
	$style = (string) get_post_meta( $post_id, 'lookdog_article_style', true );
	if ( '' !== $style ) {
		return $style;
	}
	$map = lookdog_article_styles();
	return isset( $map[ $post_id ] ) ? $map[ $post_id ] : 'ledger';
}

/**
 * Every single post on this site is a guide, and they are not all filed under
 * Buying Guides - the feeding guide sits in Feeding & Nutrition and the play
 * guide in Stories & Reviews. Gating on the category silently skipped both.
 */
function lookdog_is_guide() {
	return is_singular( 'post' );
}

add_filter( 'body_class', static function ( $classes ) {
	if ( ! lookdog_is_guide() ) {
		return $classes;
	}
	$classes[] = 'ld-art';
	$classes[] = 'ld-art--' . lookdog_article_style( get_queried_object_id() );
	return $classes;
} );
