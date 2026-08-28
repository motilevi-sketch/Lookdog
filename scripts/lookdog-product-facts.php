<?php
/**
 * LookDog - supplier facts strip on product pages.
 *
 * A product page that shows only a name and a button asks the reader to click
 * through to find out anything. This puts the three things a buyer decides on -
 * price, feedback score, how many people have actually bought it - above the
 * button, sourced from the AliExpress affiliate API and stamped with the date
 * they were checked.
 *
 * Three deliberate decisions about honesty, because this is the part of the site
 * where it is easiest to mislead:
 *
 * 1. NO STAR RATING. The API exposes `evaluate_rate`, a positive-feedback
 *    percentage, not a five-star score. Rendering 92.9% as four and a half
 *    stars invents a number the supplier never published. It is shown as what
 *    it is, and labelled as the seller's score rather than ours.
 * 2. NO CROSSED-OUT "WAS" PRICE. 142 of 159 products carry one, which makes it
 *    permanent marketing rather than a saving. Repeating it would be lending it
 *    our credibility.
 * 3. A DATE, NOT A PROMISE. Prices move. Saying "checked on 28 August" is true
 *    tomorrow; saying "$3.67" alone is not.
 *
 * Products the API returns nothing for get no strip at all rather than a blank
 * or a guess.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-product-facts.php
 */

defined( 'ABSPATH' ) || exit;

function lookdog_product_facts( $post_id ) {
	$price = trim( (string) get_post_meta( $post_id, '_lookdog_price', true ) );
	$rate  = trim( (string) get_post_meta( $post_id, '_lookdog_rate', true ) );
	$ord   = (int) get_post_meta( $post_id, '_lookdog_orders', true );
	$date  = trim( (string) get_post_meta( $post_id, '_lookdog_facts_date', true ) );

	if ( '' === $price && '' === $rate && ! $ord ) {
		return null;
	}

	return array(
		'price'    => $price,
		'currency' => (string) get_post_meta( $post_id, '_lookdog_currency', true ) ?: 'USD',
		'rate'     => $rate,
		'orders'   => $ord,
		'date'     => $date,
	);
}

/**
 * Hooked immediately before the add-to-cart form rather than on
 * woocommerce_single_product_summary. Astra replaces that summary with a single
 * callback at priority 10 that prints the title, description and button in one
 * pass, so anything hooked after it lands below the button instead of above it.
 */
add_action( 'woocommerce_before_add_to_cart_form', static function () {
	$f = lookdog_product_facts( get_the_ID() );
	if ( ! $f ) {
		return;
	}

	$when = '';
	if ( $f['date'] ) {
		$ts   = strtotime( $f['date'] );
		$when = $ts ? date_i18n( 'j F Y', $ts ) : '';
	}
	?>
<div class="ld-facts">
	<?php if ( '' !== $f['price'] ) : ?>
		<div class="ld-facts__item ld-facts__item--price">
			<span class="ld-facts__value"><?php echo esc_html( $f['currency'] . ' ' . $f['price'] ); ?></span>
			<span class="ld-facts__label">seller&rsquo;s price<?php echo $when ? esc_html( ', ' . $when ) : ''; ?></span>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $f['rate'] ) : ?>
		<div class="ld-facts__item">
			<span class="ld-facts__value"><?php echo esc_html( $f['rate'] ); ?></span>
			<span class="ld-facts__label">positive feedback on AliExpress</span>
		</div>
	<?php endif; ?>

	<?php if ( $f['orders'] > 0 ) : ?>
		<div class="ld-facts__item">
			<span class="ld-facts__value"><?php echo esc_html( number_format_i18n( $f['orders'] ) ); ?></span>
			<span class="ld-facts__label">recent orders</span>
		</div>
	<?php endif; ?>

	<p class="ld-facts__note">Figures come from the AliExpress listing<?php echo $when ? esc_html( ', checked ' . $when ) : ''; ?>. The seller sets the price and can change it at any time, so check it before you buy. The feedback score is the seller&rsquo;s, not a rating by us &mdash; we have not tested this product.</p>
</div>
	<?php
} );

add_action( 'wp_head', static function () {
	if ( ! is_singular( 'product' ) || ! lookdog_product_facts( get_queried_object_id() ) ) {
		return;
	}
	?>
<style id="lookdog-facts">
.ld-facts{display:flex;flex-wrap:wrap;gap:14px 30px;align-items:flex-end;
margin:0 0 22px;padding:18px 20px;background:#F8F8F6;border:1px solid #E6E6E1;
border-radius:10px;font-family:Poppins,sans-serif}
.ld-facts__item{display:flex;flex-direction:column;gap:2px}
.ld-facts__value{font-size:24px;font-weight:600;line-height:1.1;color:#14213D;
font-variant-numeric:tabular-nums}
.ld-facts__item--price .ld-facts__value{color:#EA670B}
.ld-facts__label{font-size:12px;line-height:1.35;color:#5A5F6B;letter-spacing:.02em}
.ld-facts__note{flex:1 1 100%;margin:4px 0 0;padding-top:12px;
border-top:1px solid #E6E6E1;font-size:12.5px;line-height:1.55;color:#5A5F6B}
@media (max-width:520px){
.ld-facts{gap:12px 22px;padding:16px}
.ld-facts__value{font-size:21px}
}
</style>
	<?php
}, 20 );
