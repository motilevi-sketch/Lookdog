<?php
/**
 * LookDog - a sale banner that takes itself down.
 *
 * AliExpress runs a promotion most months, and the useful half of a promotion
 * is the part with a deadline on it. The dangerous half is the same thing: a
 * banner announcing a sale that ended last week is worse than no banner, and
 * nobody ever remembers to remove one by hand.
 *
 * So the sale is a dated record, and every part of the site asks it whether it
 * is still running. Outside the window nothing renders and there is nothing to
 * clean up.
 *
 * ON THE CACHE. Pages are cached by LiteSpeed, so "nothing renders after the
 * end time" is only true for pages generated after the end time. A one-off cron
 * at the closing minute purges the cache, which is what actually makes the
 * banner disappear for a visitor holding a cached page.
 *
 * ON THE CODES. The September newsletter listed seven discount codes and then,
 * in grey, the countries they do not work in: the United States, Britain,
 * Canada, Australia, Israel and most of the EU - which is this site's whole
 * audience. Codes are therefore off by default. Add them here only when the
 * block AliExpress publishes for a country actually covers the reader, and put
 * the restriction in the note so nobody is sent to a checkout to be refused.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-sale.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * The sale currently on, or null.
 *
 * Times are UTC because the seller publishes in Pacific time and a promotion
 * that ends "23:59 PT on the 7th" ends at 06:59:59 UTC on the 8th. Working in
 * one zone and converting once, here, is the only way that stays right.
 *
 * @return ?array<string,mixed>
 */
function lookdog_sale() {
	$sale = apply_filters(
		'lookdog_sale',
		array(
			'id'      => 'fall-fest-2026',
			'label'   => 'AliExpress Fall Fest',
			'line'    => 'Up to 60% off across the site until Sunday.',
			'start'   => '2026-09-01 07:00:00', // 1 Sept, 00:00 PT
			'end'     => '2026-09-08 06:59:59', // 7 Sept, 23:59 PT
			'cta'     => 'See what we would buy',
			'url'     => '',    // defaults to the Best Sellers archive
			'codes'   => array(), // see the note at the top of this file
			'note'    => 'Discount codes are issued per country and most of September&rsquo;s do not apply in the US, UK, Canada or the EU. Whatever code you qualify for appears at the AliExpress checkout.',
		)
	);

	if ( empty( $sale['start'] ) || empty( $sale['end'] ) ) {
		return null;
	}

	$now = time();
	if ( $now < strtotime( $sale['start'] . ' UTC' ) || $now > strtotime( $sale['end'] . ' UTC' ) ) {
		return null;
	}

	if ( empty( $sale['url'] ) ) {
		$term        = get_term_by( 'slug', 'best-sellers', 'product_cat' );
		$sale['url'] = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : home_url( '/store/' );
	}
	return $sale;
}

/** Whole days left, for a line that survives being cached for an hour. */
function lookdog_sale_days_left( $sale ) {
	$left = strtotime( $sale['end'] . ' UTC' ) - time();
	return max( 0, (int) floor( $left / DAY_IN_SECONDS ) );
}

/**
 * The strip across the top of every page.
 *
 * Not dismissible, and deliberately one line: a sale is worth announcing once,
 * not worth a box that covers the article somebody came to read.
 */
add_action(
	'wp_body_open',
	static function () {
		$sale = lookdog_sale();
		if ( ! $sale || is_admin() ) {
			return;
		}
		$days = lookdog_sale_days_left( $sale );
		?>
<a class="ld-sale" href="<?php echo esc_url( (string) $sale['url'] ); ?>">
	<span class="ld-sale__tag"><?php echo esc_html( $sale['label'] ); ?></span>
	<span class="ld-sale__line"><?php echo esc_html( $sale['line'] ); ?></span>
	<?php if ( $days > 0 ) : ?>
		<span class="ld-sale__left">
			<?php
			printf(
				/* translators: %d: whole days remaining. */
				esc_html( _n( '%d day left', '%d days left', $days, 'lookdog' ) ),
				(int) $days
			);
			?>
		</span>
	<?php else : ?>
		<span class="ld-sale__left"><?php esc_html_e( 'Ends today', 'lookdog' ); ?></span>
	<?php endif; ?>
	<span class="ld-sale__go"><?php echo esc_html( $sale['cta'] ); ?> &rarr;</span>
</a>
<style id="lookdog-sale-css">
.ld-sale{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px 14px;
	background:#14213D;color:#E7EAF0;text-decoration:none;padding:11px 18px;
	font-family:Poppins,system-ui,sans-serif;font-size:14px;line-height:1.4;text-align:center}
.ld-sale:hover,.ld-sale:focus{background:#1B2B4E;color:#fff}
.ld-sale:focus-visible{outline:3px solid #F97316;outline-offset:-3px}
.ld-sale__tag{background:#F97316;color:#fff;font-size:11px;font-weight:700;letter-spacing:.09em;
	text-transform:uppercase;border-radius:20px;padding:4px 11px;white-space:nowrap}
.ld-sale__line{font-weight:500}
.ld-sale__left{color:#FBBF24;font-weight:600;font-variant-numeric:tabular-nums;white-space:nowrap}
.ld-sale__go{color:#FDBA74;font-weight:600;white-space:nowrap}
@media (max-width:640px){
	.ld-sale{font-size:13px;padding:10px 14px;gap:6px 10px}
	.ld-sale__go{width:100%}
}
</style>
		<?php
	}
);

/**
 * A fuller note on product pages, where the reader is about to click out.
 *
 * The honest part matters more than the offer: this site publishes the price it
 * last read from the seller, and during a sale that figure is behind. Saying so
 * costs nothing and is the reason anybody trusts the numbers the rest of the
 * month.
 */
add_action(
	'woocommerce_before_add_to_cart_form',
	static function () {
		$sale = lookdog_sale();
		if ( ! $sale ) {
			return;
		}
		?>
<div class="ld-salebox">
	<p class="ld-salebox__h"><?php echo esc_html( $sale['label'] ); ?> &mdash; <?php echo esc_html( $sale['line'] ); ?></p>
	<p class="ld-salebox__p">
		<?php esc_html_e( 'The price shown above is the one we last read from the seller. During the sale it is usually higher than what you will actually pay, so check the figure on AliExpress rather than ours.', 'lookdog' ); ?>
	</p>
	<?php if ( ! empty( $sale['codes'] ) ) : ?>
		<ul class="ld-salebox__codes">
			<?php foreach ( $sale['codes'] as $c ) : ?>
				<li><code><?php echo esc_html( $c['code'] ); ?></code> <?php echo esc_html( $c['saves'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( ! empty( $sale['note'] ) ) : ?>
		<p class="ld-salebox__n"><?php echo wp_kses( $sale['note'], array() ); ?></p>
	<?php endif; ?>
</div>
<style id="lookdog-salebox-css">
.ld-salebox{background:#FDF3E7;border:1px solid #F0C99B;border-left:4px solid #EA670B;
	border-radius:8px;padding:15px 17px;margin:0 0 20px;max-width:60ch;
	font-family:Poppins,system-ui,sans-serif}
.ld-salebox__h{margin:0 0 7px;font-size:15px;font-weight:600;color:#14213D;line-height:1.35}
.ld-salebox__p{margin:0;font-size:13.5px;line-height:1.6;color:#3A3F4B}
.ld-salebox__codes{margin:10px 0 0;padding-left:1.1em;font-size:13.5px;color:#3A3F4B}
.ld-salebox__codes code{background:#fff;border:1px solid #F0C99B;border-radius:4px;padding:1px 6px;
	font-weight:600;color:#14213D}
.ld-salebox__n{margin:10px 0 0;font-size:12.5px;line-height:1.55;color:#5A5F6B}
</style>
		<?php
	},
	4
);

/**
 * Take the cached copies down at the closing minute.
 *
 * Without this the banner keeps appearing on every page LiteSpeed cached while
 * the sale was on, which for a quiet site can be days. The event is scheduled
 * once per sale and carries the sale's id, so a new promotion cannot be blocked
 * by the previous one's leftover event.
 */
add_action(
	'init',
	static function () {
		$sale = lookdog_sale();
		if ( ! $sale ) {
			return;
		}
		$args = array( (string) $sale['id'] );
		if ( ! wp_next_scheduled( 'lookdog_sale_ended', $args ) ) {
			wp_schedule_single_event( strtotime( $sale['end'] . ' UTC' ) + 60, 'lookdog_sale_ended', $args );
		}
	}
);

add_action(
	'lookdog_sale_ended',
	static function () {
		do_action( 'litespeed_purge_all' );
	}
);
