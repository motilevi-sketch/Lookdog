<?php
/**
 * LookDog - "further reading" link from a product to the guide that covers it.
 *
 * Product pages had no editorial outbound links at all: WooCommerce's related
 * products block sends people sideways to more products, and the header nav is
 * the same on every page. This adds the one link that answers the question a
 * reader actually has on a product page, which is whether they need the thing.
 *
 * Deliberately partial. A category only appears here when a guide genuinely
 * covers it; the rest get nothing rather than a link to something loosely
 * related, which is worth less than no link and reads as filler.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-related-reading.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * product_cat term id => [ post id, the sentence that earns the click ].
 * Add a row here when a new guide lands.
 */
function lookdog_reading_map() {
	return apply_filters( 'lookdog_reading_map', array(
		70 => array( 3777, 'How much to feed, what is never safe, and why the same food is riskier for a small dog.' ),
		68 => array( 3344, 'Sizing a toy safely, playing tug without the risks, and when a toy has to go in the bin.' ),
	) );
}

function lookdog_reading_for_product( $post_id ) {
	$map   = lookdog_reading_map();
	$terms = wp_get_object_terms( $post_id, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term_id ) {
		if ( empty( $map[ $term_id ] ) ) {
			continue;
		}
		list( $guide_id, $blurb ) = $map[ $term_id ];
		if ( 'publish' !== get_post_status( $guide_id ) ) {
			continue;
		}
		return array(
			'url'   => get_permalink( $guide_id ),
			'title' => get_the_title( $guide_id ),
			'blurb' => $blurb,
		);
	}
	return null;
}

add_filter( 'the_content', static function ( $content ) {
	if ( ! is_singular( 'product' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$guide = lookdog_reading_for_product( get_the_ID() );
	if ( ! $guide ) {
		return $content;
	}

	$block  = '<aside class="ld-reading">';
	$block .= '<p class="ld-reading__label">Before you buy</p>';
	$block .= '<p class="ld-reading__title"><a href="' . esc_url( $guide['url'] ) . '">' . esc_html( $guide['title'] ) . '</a></p>';
	$block .= '<p class="ld-reading__blurb">' . esc_html( $guide['blurb'] ) . '</p>';
	$block .= '</aside>';

	return $content . $block;
}, 25 );

add_action( 'wp_head', static function () {
	if ( ! is_singular( 'product' ) || ! lookdog_reading_for_product( get_queried_object_id() ) ) {
		return;
	}
	?>
<style id="lookdog-reading">
.ld-reading{margin:34px 0 0;padding:20px 0 0;border-top:1px solid #E6E6E1;font-family:Poppins,sans-serif}
.ld-reading__label{margin:0;font-size:11px;font-weight:600;letter-spacing:.09em;
text-transform:uppercase;color:#5A5F6B}
.ld-reading__title{margin:8px 0 0;font-size:18px;font-weight:600;line-height:1.3}
.ld-reading__title a{color:#14213D;text-decoration:none;transition:color .15s ease}
.ld-reading__title a:hover{color:#EA670B}
.ld-reading__blurb{margin:6px 0 0;font-size:14px;line-height:1.55;color:#3A3F4B}
</style>
	<?php
}, 20 );
