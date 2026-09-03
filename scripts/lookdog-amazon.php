<?php
/**
 * LookDog - Amazon links, without the things Amazon does not allow.
 *
 * The owner has a dormant Amazon Associates account for amazon.de. This is the
 * plumbing for using it. It renders nothing at all until a tracking tag is
 * stored, so it can sit here safely while the account status is confirmed.
 *
 * THREE RULES BUILT IN RATHER THAN WRITTEN DOWN SOMEWHERE
 *
 * 1. No price. Amazon's prices move and a stale price on a publisher's page
 *    breaks the operating agreement. There is no price field here, so one
 *    cannot be added by accident.
 * 2. No product image. Images may only come through the Product Advertising
 *    API or an approved link tool - copying one from a listing is a breach.
 *    There is no image field either. Text links only, until API access exists.
 * 3. The disclosure travels with the link. Amazon requires its own wording in
 *    addition to the site's usual notice, so it is printed by the same function
 *    that prints the link and cannot be separated from it.
 *
 * THE STORE IS A SETTING, NOT A CONSTANT. The account is German today. If the
 * audience turns out to be American and a .com account follows, one option
 * value changes and every link on the site follows.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-amazon.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings. Empty tag means the whole feature is off.
 *
 * @return array{tag:string,store:string,label:string,ships:string}
 */
function lookdog_amazon_config() {
	$c = (array) get_option( 'lookdog_amazon', array() );
	return array(
		'tag'   => isset( $c['tag'] ) ? trim( (string) $c['tag'] ) : '',
		'store' => ! empty( $c['store'] ) ? (string) $c['store'] : 'amazon.de',
		'label' => ! empty( $c['label'] ) ? (string) $c['label'] : 'Amazon.de',
		// One honest line about what the reader gets by going there instead.
		'ships' => isset( $c['ships'] ) ? (string) $c['ships'] : 'Usually dearer, and usually there this week.',
	);
}

/** Is the feature configured at all? */
function lookdog_amazon_on() {
	$c = lookdog_amazon_config();
	return '' !== $c['tag'];
}

/**
 * product id => ASIN.
 *
 * Deliberately a map rather than a field on the product: an Amazon equivalent
 * is a judgement about two different objects being the same thing, and that
 * judgement belongs somewhere a person reviews, not in a bulk import.
 *
 * @return array<int,string>
 */
function lookdog_amazon_map() {
	return apply_filters( 'lookdog_amazon_map', array() );
}

/**
 * A tagged link to one item.
 *
 * @param string $asin Amazon's ten-character item id.
 * @return string URL, or '' when the feature is off or the ASIN is malformed.
 */
function lookdog_amazon_url( $asin ) {
	$asin = strtoupper( trim( (string) $asin ) );
	if ( ! lookdog_amazon_on() || ! preg_match( '~^[A-Z0-9]{10}$~', $asin ) ) {
		return '';
	}
	$c = lookdog_amazon_config();
	return 'https://www.' . $c['store'] . '/dp/' . $asin . '/?tag=' . rawurlencode( $c['tag'] );
}

/**
 * The link block. Used on product pages and, through the shortcode, in articles.
 *
 * @param string $asin  Item id.
 * @param string $intro One line of context, optional.
 * @return string
 */
function lookdog_amazon_block( $asin, $intro = '' ) {
	$url = lookdog_amazon_url( $asin );
	if ( '' === $url ) {
		return '';
	}
	$c = lookdog_amazon_config();

	$out  = '<div class="ld-az">';
	$out .= '<p class="ld-az__line">' . esc_html( '' !== $intro ? $intro : $c['ships'] ) . '</p>';
	$out .= '<p class="ld-az__go"><a href="' . esc_url( $url ) . '" rel="sponsored nofollow noopener" target="_blank">'
		. sprintf( /* translators: %s: store name. */ esc_html__( 'See it on %s', 'lookdog' ), esc_html( $c['label'] ) )
		. ' &rarr;</a></p>';
	// Amazon's own required wording, alongside the site's usual notice. Confirm
	// the current sentence in the operating agreement for the store in use -
	// the German programme states it in German.
	$out .= '<p class="ld-az__disc">' . esc_html__( 'As an Amazon Associate we earn from qualifying purchases.', 'lookdog' ) . '</p>';
	$out .= '</div>';
	return $out;
}

/** [lookdog_amazon asin="B01ABCDEFG" intro="Same bed, here on Thursday."] */
add_shortcode(
	'lookdog_amazon',
	static function ( $atts ) {
		$a = shortcode_atts( array( 'asin' => '', 'intro' => '' ), $atts, 'lookdog_amazon' );
		return lookdog_amazon_block( $a['asin'], $a['intro'] );
	}
);

/**
 * On a product page, under the AliExpress button.
 *
 * Second, not first: the reader came for the thing this site actually
 * researched. The Amazon line is the alternative for somebody who would rather
 * pay more and have it this week, and saying that plainly is worth more than
 * pretending the two are the same offer.
 */
add_action(
	'woocommerce_after_add_to_cart_form',
	static function () {
		if ( ! lookdog_amazon_on() ) {
			return;
		}
		$map = lookdog_amazon_map();
		$id  = get_the_ID();
		if ( empty( $map[ $id ] ) ) {
			return;
		}
		echo lookdog_amazon_block( $map[ $id ] ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
);

add_action(
	'wp_head',
	static function () {
		if ( ! lookdog_amazon_on() ) {
			return;
		}
		?>
<style id="lookdog-amazon-css">
.ld-az{margin:14px 0 0;padding:13px 15px;background:#F8F8F6;border:1px solid #E6E6E1;
	border-radius:8px;max-width:60ch;font-family:Poppins,system-ui,sans-serif}
.ld-az__line{margin:0;font-size:14px;line-height:1.55;color:#3A3F4B}
.ld-az__go{margin:8px 0 0}
.ld-az__go a{color:#14213D;font-size:14.5px;font-weight:600;text-decoration:none;
	border-bottom:2px solid #F97316;padding-bottom:2px}
.ld-az__go a:hover,.ld-az__go a:focus{color:#EA670B}
.ld-az__disc{margin:9px 0 0;font-size:11.5px;line-height:1.5;color:#5A5F6B}
</style>
		<?php
	},
	21
);
