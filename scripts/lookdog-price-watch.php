<?php
/**
 * LookDog - price watch, and the homepage band that reports it.
 *
 * [lookdog_price_drops]
 *
 * WHY THIS IS NOT A COUPON BOX. The brief was a window of live AliExpress
 * coupons. There is no such thing to read: the Portals API has no coupon path
 * at all - `aliexpress.affiliate.coupon.get`, `.coupon.query`,
 * `.promotion.get` and `.promo.coupon.get` all return InvalidApiPath - and a
 * product record carries no code, no expiry and no voucher of any kind. The
 * only promo-shaped things available are `featuredpromo.get`, which lists 139
 * named product feeds with no codes or dates, and a per-product `discount`
 * percentage computed against `target_original_price`.
 *
 * That percentage is the trap. 142 of our 159 priced products carry an
 * "original" price above the sale price permanently, which makes it marketing
 * rather than a saving - the reason the product pages already refuse to print
 * it. A band shouting "53% off" would be the least honest thing on the site.
 *
 * So this reports the one discount we can actually stand behind: a price that
 * has fallen *since we ourselves last checked it*. We hold the old figure and
 * the date we recorded it, and the new figure and today's date. Both are ours,
 * both are dated, and a reader can check either against the listing.
 *
 * The band hides itself when nothing has dropped. An empty deals section is
 * worth more than a full one that invents deals.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-price-watch.php
 */

defined( 'ABSPATH' ) || exit;

const LOOKDOG_PRICE_WATCH_HOOK = 'lookdog_price_watch';

/**
 * Refresh prices from the supplier and record any that fell.
 *
 * Chunked at five IDs a call with a retry, because `productdetail.get` returns
 * an empty result set for large batches with no error at all. After roughly 160
 * IDs in a session the endpoint starts returning empty for everything,
 * including IDs it answered minutes earlier - so a run that goes quiet part way
 * through has hit the rate limit, not found 60 delisted products. Anything not
 * answered for keeps the figures it already had.
 *
 * @param int   $limit Stop after this many products. 0 for all.
 * @param int[] $only  Restrict to these post IDs. Empty for the whole catalogue.
 * @return array<string,mixed> A report, also stored for the record.
 */
function lookdog_refresh_prices( $limit = 0, $only = array() ) {
	if ( ! function_exists( 'lookdog_ae_call' ) || ! defined( 'ALIEXPRESS_TRACKING_ID' ) ) {
		return array( 'ok' => false, 'error' => 'API client unavailable' );
	}

	$posts = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$by_ae = array();
	foreach ( $posts as $pid ) {
		$ae = trim( (string) get_post_meta( $pid, '_lookdog_ae_id', true ) );
		if ( '' !== $ae ) {
			$by_ae[ $ae ] = $pid;
		}
	}
	if ( $only ) {
		$only  = array_map( 'intval', (array) $only );
		$by_ae = array_filter(
			$by_ae,
			static function ( $pid ) use ( $only ) {
				return in_array( (int) $pid, $only, true );
			}
		);
	}
	if ( $limit > 0 ) {
		$by_ae = array_slice( $by_ae, 0, $limit, true );
	}

	$today   = current_time( 'Y-m-d' );
	$report  = array(
		'ok'        => true,
		'ran'       => current_time( 'mysql' ),
		'requested' => count( $by_ae ),
		'answered'  => 0,
		'unanswered'=> 0,
		'fell'      => 0,
		'rose'      => 0,
		'unchanged' => 0,
		'drops'     => array(),
	);

	foreach ( array_chunk( array_keys( $by_ae ), 5 ) as $chunk ) {
		$products = array();

		for ( $try = 0; $try < 3 && ! $products; $try++ ) {
			$raw      = lookdog_ae_call(
				'aliexpress.affiliate.productdetail.get',
				array(
					'product_ids'     => implode( ',', $chunk ),
					'target_currency' => 'USD',
					'target_language' => 'EN',
					'tracking_id'     => ALIEXPRESS_TRACKING_ID,
				)
			);
			$products = $raw['aliexpress_affiliate_productdetail_get_response']['resp_result']['result']['products']['product'] ?? array();
			if ( ! $products ) {
				usleep( 400000 );
			}
		}

		if ( ! $products ) {
			$report['unanswered'] += count( $chunk );
			continue;
		}

		foreach ( $products as $p ) {
			$ae = (string) ( $p['product_id'] ?? '' );
			if ( ! isset( $by_ae[ $ae ] ) ) {
				continue;
			}
			$pid = $by_ae[ $ae ];
			$report['answered']++;

			$new = isset( $p['target_sale_price'] ) ? (float) $p['target_sale_price'] : 0.0;
			if ( $new <= 0 ) {
				continue;
			}

			$old      = (float) get_post_meta( $pid, '_lookdog_price', true );
			$old_date = (string) get_post_meta( $pid, '_lookdog_facts_date', true );

			if ( $old > 0 && $new < $old ) {
				// Keep the *original* observation, not the last one, so a price
				// that slides down over several days reports the whole fall
				// rather than yesterday's sliver.
				if ( ! get_post_meta( $pid, '_lookdog_price_prev', true ) ) {
					update_post_meta( $pid, '_lookdog_price_prev', number_format( $old, 2, '.', '' ) );
					update_post_meta( $pid, '_lookdog_price_prev_date', $old_date ?: $today );
				}
				$report['fell']++;
				$report['drops'][] = array(
					'id'    => $pid,
					'title' => get_the_title( $pid ),
					'from'  => (string) get_post_meta( $pid, '_lookdog_price_prev', true ),
					'to'    => number_format( $new, 2, '.', '' ),
				);
			} elseif ( $old > 0 && $new > $old ) {
				// The drop is over. Nothing to celebrate and nothing to show.
				delete_post_meta( $pid, '_lookdog_price_prev' );
				delete_post_meta( $pid, '_lookdog_price_prev_date' );
				$report['rose']++;
			} else {
				$report['unchanged']++;
			}

			update_post_meta( $pid, '_lookdog_price', number_format( $new, 2, '.', '' ) );
			update_post_meta( $pid, '_lookdog_facts_date', $today );
			// The date alone cannot say whether a figure is an hour old or
			// twenty-three, and on a supplier whose prices move intraday that
			// is the difference between a useful number and a misleading one.
			update_post_meta( $pid, '_lookdog_price_time', time() );
			update_post_meta( $pid, '_lookdog_currency', (string) ( $p['target_sale_price_currency'] ?? 'USD' ) );

			if ( isset( $p['target_original_price'] ) ) {
				update_post_meta( $pid, '_lookdog_price_was', number_format( (float) $p['target_original_price'], 2, '.', '' ) );
			}
			if ( isset( $p['evaluate_rate'] ) ) {
				update_post_meta( $pid, '_lookdog_rate', (string) $p['evaluate_rate'] );
			}
			if ( isset( $p['lastest_volume'] ) ) {
				update_post_meta( $pid, '_lookdog_orders', (int) $p['lastest_volume'] );
			}
		}

		usleep( 250000 );
	}

	update_option( 'lookdog_price_watch_report', $report, false );
	delete_transient( 'lookdog_rating_floor' );
	delete_transient( 'lookdog_price_drops' );

	return $report;
}

/**
 * Products whose price has fallen since we recorded it, newest fall first.
 *
 * @return array<int,array<string,mixed>>
 */
function lookdog_price_drops( $limit = 6 ) {
	$cached = get_transient( 'lookdog_price_drops' );
	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_lookdog_price_prev',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$out = array();
	foreach ( $ids as $id ) {
		$now  = (float) get_post_meta( $id, '_lookdog_price', true );
		$then = (float) get_post_meta( $id, '_lookdog_price_prev', true );
		if ( $now <= 0 || $then <= 0 || $now >= $then ) {
			continue;
		}
		$out[] = array(
			'id'        => $id,
			'now'       => $now,
			'then'      => $then,
			'saved'     => $then - $now,
			'pct'       => (int) round( ( ( $then - $now ) / $then ) * 100 ),
			'currency'  => (string) get_post_meta( $id, '_lookdog_currency', true ) ?: 'USD',
			'then_date' => (string) get_post_meta( $id, '_lookdog_price_prev_date', true ),
			'now_date'  => (string) get_post_meta( $id, '_lookdog_facts_date', true ),
		);
	}

	// Biggest real saving first, in money rather than percentage: 40% off a
	// three-dollar toy is not the news that 25% off a sixty-dollar bed is.
	usort( $out, static fn( $a, $b ) => $b['saved'] <=> $a['saved'] );

	set_transient( 'lookdog_price_drops', $out, 6 * HOUR_IN_SECONDS );

	return array_slice( $out, 0, $limit );
}

function lookdog_price_drops_shortcode( $atts = array() ) {
	$atts  = shortcode_atts(
		array(
			'heading' => 'Cheaper than when we last looked',
			'limit'   => '6',
		),
		$atts,
		'lookdog_price_drops'
	);
	$drops = lookdog_price_drops( absint( $atts['limit'] ) );
	if ( ! $drops ) {
		return '';
	}

	$fmt = static function ( $d ) {
		$ts = strtotime( $d );
		return $ts ? date_i18n( 'j M', $ts ) : '';
	};

	ob_start();
	?>
<section class="ld-band">
	<div class="ld-wrap">
		<div class="ld-drop__head">
			<h2 class="ld-h2"><?php echo esc_html( $atts['heading'] ); ?></h2>
			<p class="ld-drop__lede">The seller sets these prices and moves them without warning. These are the ones that have gone <em>down</em> since we last recorded them &mdash; our own figures, both dated, so you can check either against the listing.</p>
		</div>

		<ul class="ld-drops">
			<?php foreach ( $drops as $d ) : ?>
				<li class="ld-drop">
					<a class="ld-drop__link" href="<?php echo esc_url( (string) get_permalink( $d['id'] ) ); ?>">
						<span class="ld-drop__media"><?php echo get_the_post_thumbnail( $d['id'], 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) ); // phpcs:ignore ?></span>
						<span class="ld-drop__name"><?php echo esc_html( get_the_title( $d['id'] ) ); ?></span>
						<span class="ld-drop__prices">
							<s class="ld-drop__then"><?php echo esc_html( $d['currency'] . ' ' . number_format( $d['then'], 2 ) ); ?></s>
							<b class="ld-drop__now"><?php echo esc_html( $d['currency'] . ' ' . number_format( $d['now'], 2 ) ); ?></b>
							<span class="ld-drop__when"><?php echo esc_html( $fmt( $d['then_date'] ) ); ?> &rarr; <?php echo esc_html( $fmt( $d['now_date'] ) ); ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_price_drops', 'lookdog_price_drops_shortcode' );

/**
 * The products worth re-checking between the nightly sweeps.
 *
 * Measured over 20 products, roughly one in seven moved within seventeen hours
 * of the 04:00 run, two of them by more than 20%. Checking everything six times
 * a day would spend the API allowance on 237 products to keep perhaps thirty of
 * them accurate, so this refreshes only the ones visitors actually reach:
 * whatever has been clicked, topped up with the best sellers.
 *
 * @param int $limit Maximum products to return.
 * @return int[]
 */
function lookdog_hot_product_ids( $limit = 40 ) {
	$clicked = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'meta_key'       => '_lookdog_clicks', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		)
	);

	if ( count( $clicked ) >= $limit ) {
		return array_map( 'intval', $clicked );
	}

	$sellers = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'meta_key'       => '_lookdog_orders', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'post__not_in'   => $clicked,
		)
	);

	return array_map( 'intval', array_slice( array_merge( $clicked, $sellers ), 0, $limit ) );
}

/**
 * Refresh the hot list. Reports separately so a failure here is visible rather
 * than overwriting the record of the nightly sweep.
 *
 * @return void
 */
function lookdog_refresh_hot_prices() {
	$report = lookdog_refresh_prices( 0, lookdog_hot_product_ids( 40 ) );
	update_option( 'lookdog_price_hot_report', $report, false );
}

/* ---------------------------------------------------------------- schedule */

add_action( LOOKDOG_PRICE_WATCH_HOOK, 'lookdog_refresh_prices' );
add_action( 'lookdog_price_watch_hot', 'lookdog_refresh_hot_prices' );

add_filter(
	'cron_schedules', // phpcs:ignore WordPress.WP.CronInterval
	static function ( $s ) {
		$s['lookdog_six_hours'] = array( 'interval' => 6 * HOUR_IN_SECONDS, 'display' => 'Every six hours' );
		return $s;
	}
);

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( LOOKDOG_PRICE_WATCH_HOOK ) ) {
			// 04:00 site time: after the day has rolled over, before anyone reads it.
			wp_schedule_event( strtotime( 'tomorrow 04:00' ), 'daily', LOOKDOG_PRICE_WATCH_HOOK );
		}
		if ( ! wp_next_scheduled( 'lookdog_price_watch_hot' ) ) {
			// Offset from the nightly sweep so the two never compete for the
			// same rate-limited endpoint.
			wp_schedule_event( strtotime( 'tomorrow 07:00' ), 'lookdog_six_hours', 'lookdog_price_watch_hot' );
		}
	}
);

/* -------------------------------------------------------------------- style */

add_action(
	'wp_head',
	static function () {
		global $post;
		if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, 'lookdog_price_drops' ) ) {
			return;
		}
		?>
<style id="lookdog-drops-css">
.ld-drop__head{max-width:64ch;margin-bottom:34px}
.ld-drop__lede{margin:14px 0 0;color:#3A3F4B;font-size:15.5px;line-height:1.6}
/* A price board: two columns of tight rows, not a third grid of cards. */
.ld-drops{list-style:none;margin:0;padding:0;display:grid;
grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:0 48px;
border-top:1px solid #D5D5CE}
.ld-drop{border-bottom:1px solid #E6E6E1}
.ld-drop__link{display:grid;grid-template-columns:52px minmax(0,1fr) auto;gap:16px;
align-items:center;padding:14px 4px;text-decoration:none;transition:background .18s ease}
.ld-drop__link:hover,.ld-drop__link:focus{background:#F8F8F6}
.ld-drop__link:focus-visible{outline:3px solid #F97316;outline-offset:-3px}
.ld-drop__media{display:block;width:52px;height:52px;border-radius:4px;overflow:hidden;background:#EFEFEC}
.ld-drop__media img{width:100%;height:100%;object-fit:cover;display:block}
.ld-drop__name{color:#14213D;font-size:14.5px;font-weight:600;line-height:1.4}
.ld-drop__prices{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.ld-drop__then{display:inline-block;color:#5A5F6B;font-size:13px;margin-right:7px}
.ld-drop__now{color:#EA670B;font-size:16px;font-weight:600}
.ld-drop__when{display:block;margin-top:2px;color:#5A5F6B;font-size:11px;letter-spacing:.04em}
@media (max-width:640px){
	.ld-drops{grid-template-columns:1fr;gap:0}
	.ld-drop__link{grid-template-columns:44px minmax(0,1fr) auto;gap:12px}
	.ld-drop__media{width:44px;height:44px}
	.ld-drop__name{font-size:13.5px}
}
</style>
		<?php
	},
	20
);
