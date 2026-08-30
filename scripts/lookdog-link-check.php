<?php
/**
 * LookDog - daily availability check for every affiliate product.
 *
 * WHAT THIS ACTUALLY CHECKS, AND WHY NOT HTTP
 * The obvious implementation - request each affiliate URL and look for a 404 -
 * does not work here. An AliExpress affiliate link answers 200 and redirects
 * happily even when the item behind it is gone; you land on a search page or a
 * "no longer available" page that is still, technically, a live URL. So the
 * supplier API is the only honest source: if productdetail.get no longer
 * returns a product, that product is gone.
 *
 * WHY LINKS ARE NOT REFRESHED
 * AliExpress mints a fresh promotion_link on every call - same length, different
 * string - so comparing the stored link with a new one always reports a change
 * that is not one. Rewriting 237 links nightly would churn the database to no
 * effect. The stored links keep working, so they are left alone. The only thing
 * worth writing back is whether the product still exists.
 *
 * THE THREE-STRIKE RULE
 * A single silent response is not proof of anything: the endpoint returns an
 * empty set under load with no error at all. A product is only marked
 * unavailable after being absent from THREE separate successful responses. A
 * chunk that failed entirely is not counted against any product in it.
 *
 * WHAT IT WILL NOT DO
 * It does not swap a dead product for a different one. Choosing a replacement
 * means judging that a snuffle mat is a fair substitute for a slow feeder, and
 * a wrong guess sends a buyer to something they did not want with our
 * recommendation attached to it. Dead products stop being sold and are listed
 * for a person to replace. When somebody has made that judgement, the new
 * product's ID goes in `_lookdog_replaced_by` on the old one and the withdrawn
 * page offers it by name.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-link-check.php
 * Requires:    wp-content/mu-plugins/lookdog-aliexpress.php (API client)
 */

defined( 'ABSPATH' ) || exit;

const LOOKDOG_LINK_CHECK_HOOK = 'lookdog_link_check';

/** Consecutive absences before a product is treated as gone. */
const LOOKDOG_MISS_LIMIT = 3;

/**
 * Sweep the catalogue for products the supplier no longer returns.
 *
 * Products are taken oldest-checked first, so a run that stops early resumes
 * where it left off tomorrow and every product comes round in turn.
 *
 * @param int $limit Maximum products to check in one run. 0 for all.
 * @return array<string,mixed> Report, also stored in lookdog_link_check_report.
 */
function lookdog_link_check( $limit = 150 ) {
	if ( ! function_exists( 'lookdog_ae_call' ) ) {
		return array( 'ok' => false, 'error' => 'API client unavailable' );
	}
	$tracking = defined( 'ALIEXPRESS_TRACKING_ID' ) ? ALIEXPRESS_TRACKING_ID : 'default';

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	// Sorted in PHP rather than by the query. Ordering on a meta_key in
	// WP_Query silently drops every post that does not have that key yet, which
	// on the first run is all of them - the sweep reported nothing to check and
	// looked like a success. A product never checked must sort FIRST, not
	// vanish, so a missing date is treated as the oldest possible one.
	$checked = array();
	foreach ( $ids as $pid ) {
		$checked[ $pid ] = (string) get_post_meta( $pid, '_lookdog_checked', true );
	}
	asort( $checked );

	$by_ae = array();
	foreach ( array_keys( $checked ) as $pid ) {
		if ( $limit > 0 && count( $by_ae ) >= $limit ) {
			break;
		}
		$ae = trim( (string) get_post_meta( $pid, '_lookdog_ae_id', true ) );
		if ( '' !== $ae ) {
			$by_ae[ $ae ] = $pid;
		}
	}

	$today  = current_time( 'Y-m-d' );
	$report = array(
		'ok'            => true,
		'ran'           => current_time( 'mysql' ),
		'checked'       => 0,
		'alive'         => 0,
		'missed'        => 0,
		'skipped'       => 0,
		'newly_gone'    => array(),
		'recovered'     => array(),
		'stopped_early' => false,
	);

	$empty_streak = 0;

	foreach ( array_chunk( array_keys( $by_ae ), 5 ) as $chunk ) {
		$products = array();
		for ( $try = 0; $try < 3 && ! $products; $try++ ) {
			$raw      = lookdog_ae_call(
				'aliexpress.affiliate.productdetail.get',
				array(
					'product_ids'     => implode( ',', $chunk ),
					'target_currency' => 'USD',
					'target_language' => 'EN',
					'tracking_id'     => $tracking,
				)
			);
			$products = $raw['aliexpress_affiliate_productdetail_get_response']['resp_result']['result']['products']['product'] ?? array();
			if ( ! $products ) {
				usleep( 400000 );
			}
		}

		// Nothing came back at all. That is the endpoint going quiet under load,
		// not five products vanishing at once, so nobody gets a strike.
		if ( ! $products ) {
			$report['skipped'] += count( $chunk );
			$empty_streak++;
			if ( $empty_streak >= 3 ) {
				$report['stopped_early'] = true;
				break;
			}
			continue;
		}
		$empty_streak = 0;

		$answered = array();
		foreach ( $products as $p ) {
			$answered[ (string) ( $p['product_id'] ?? '' ) ] = true;
		}

		foreach ( $chunk as $ae ) {
			$pid = $by_ae[ $ae ];
			$report['checked']++;
			update_post_meta( $pid, '_lookdog_checked', $today );

			if ( isset( $answered[ $ae ] ) ) {
				$report['alive']++;
				update_post_meta( $pid, '_lookdog_seen', $today );
				delete_post_meta( $pid, '_lookdog_miss' );
				if ( 'yes' === get_post_meta( $pid, '_lookdog_unavailable', true ) ) {
					delete_post_meta( $pid, '_lookdog_unavailable' );
					$report['recovered'][] = array( 'id' => $pid, 'title' => get_the_title( $pid ) );
				}
				continue;
			}

			// The chunk answered, but not for this product.
			$report['missed']++;
			$miss = (int) get_post_meta( $pid, '_lookdog_miss', true ) + 1;
			update_post_meta( $pid, '_lookdog_miss', $miss );

			if ( $miss >= LOOKDOG_MISS_LIMIT && 'yes' !== get_post_meta( $pid, '_lookdog_unavailable', true ) ) {
				update_post_meta( $pid, '_lookdog_unavailable', 'yes' );
				update_post_meta( $pid, '_lookdog_unavailable_since', $today );
				$report['newly_gone'][] = array( 'id' => $pid, 'title' => get_the_title( $pid ) );
			}
		}

		usleep( 250000 );
	}

	update_option( 'lookdog_link_check_report', $report, false );
	delete_transient( 'lookdog_unavailable_ids' );
	return $report;
}

/**
 * IDs of products the supplier has stopped returning. Cached for an hour.
 *
 * @return int[]
 */
function lookdog_unavailable_ids() {
	$ids = get_transient( 'lookdog_unavailable_ids' );
	if ( is_array( $ids ) ) {
		return $ids;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_lookdog_unavailable', 'value' => 'yes' ),
			),
		)
	);
	$ids = array_map( 'intval', (array) $ids );
	set_transient( 'lookdog_unavailable_ids', $ids, HOUR_IN_SECONDS );
	return $ids;
}

/** Whether one product is currently unbuyable. */
function lookdog_is_unavailable( $post_id ) {
	return in_array( (int) $post_id, lookdog_unavailable_ids(), true );
}

/**
 * The product we now list in place of one that is gone, if there is one.
 *
 * Set by hand, in `_lookdog_replaced_by`, when somebody has looked at both
 * items and judged the new one a fair answer to the same question. Nothing
 * writes it automatically - see the note at the top of this file about why a
 * swap is not a thing a nightly job should decide on its own.
 *
 * @param int $post_id The withdrawn product.
 * @return ?array{url:string,title:string}
 */
function lookdog_replacement_for( $post_id ) {
	$new = (int) get_post_meta( $post_id, '_lookdog_replaced_by', true );
	if ( ! $new || 'publish' !== get_post_status( $new ) || lookdog_is_unavailable( $new ) ) {
		return null;
	}
	return array(
		'url'   => (string) get_permalink( $new ),
		'title' => get_the_title( $new ),
	);
}

/**
 * Stop sending people to something that is not there.
 *
 * The button is removed rather than left pointing at a link that now lands on
 * an AliExpress search page - which looks like a working link and is worse than
 * an honest dead end, because the reader blames us for the wrong product.
 *
 * Where a replacement has been chosen, it is offered here as one named link
 * rather than a row of suggestions: the reader came for a specific thing, and
 * the useful answer is the closest equivalent we would actually stand behind.
 */
add_action(
	// Not woocommerce_before_add_to_cart_form. That hook lives INSIDE the
	// add-to-cart template, which the block below removes on exactly the
	// products this notice is for - so the notice rendered on nothing at all.
	// The summary hook fires either way, immediately above where the button
	// would have been.
	'woocommerce_single_product_summary',
	static function () {
		if ( ! lookdog_is_unavailable( get_the_ID() ) ) {
			return;
		}
		$since = (string) get_post_meta( get_the_ID(), '_lookdog_unavailable_since', true );
		?>
<p class="ld-gone">
	<strong><?php esc_html_e( 'This one is no longer listed.', 'lookdog' ); ?></strong>
	<?php
	if ( $since ) {
		printf(
			/* translators: %s: date the product stopped being available. */
			esc_html__( 'The seller withdrew it, or it sold out permanently. We noticed on %s and stopped linking to it rather than send you somewhere it is not.', 'lookdog' ),
			esc_html( date_i18n( 'j F Y', strtotime( $since ) ) )
		);
	} else {
		esc_html_e( 'The seller withdrew it, and we have stopped linking to it rather than send you somewhere it is not.', 'lookdog' );
	}
	?>
	<?php $swap = lookdog_replacement_for( get_the_ID() ); ?>
	<?php if ( $swap ) : ?>
		<span class="ld-gone__swap">
			<?php esc_html_e( 'We now list', 'lookdog' ); ?>
			<a href="<?php echo esc_url( $swap['url'] ); ?>"><?php echo esc_html( $swap['title'] ); ?></a>
			<?php esc_html_e( 'in its place, chosen as the closest thing we would still recommend.', 'lookdog' ); ?>
		</span>
	<?php else : ?>
		<a href="<?php echo esc_url( (string) get_permalink( (int) get_option( 'woocommerce_shop_page_id' ) ) ); ?>"><?php esc_html_e( 'Browse what is still available', 'lookdog' ); ?></a>
	<?php endif; ?>
</p>
<style>
.ld-gone{background:#FDF3E7;border:1px solid #F0C99B;border-left:4px solid #B45309;
	border-radius:6px;padding:16px 18px;margin:0 0 22px;font-size:14.5px;line-height:1.6;color:#3A3F4B;max-width:60ch;}
.ld-gone strong{display:block;margin-bottom:4px;color:#14213D;font-size:15.5px;}
.ld-gone__swap{display:block;margin-top:10px;}
.ld-gone a{color:#14213D;text-decoration:underline;}
.ld-gone a:hover{color:#F97316;}
</style>
		<?php
	},
	29
);

/** Remove the buy button itself on a product that cannot be bought. */
add_action(
	'wp',
	static function () {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		if ( lookdog_is_unavailable( get_queried_object_id() ) ) {
			remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
			remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
		}
	}
);

/**
 * Keep withdrawn products out of anything that actively recommends a product.
 *
 * They stay in category archives, where the page still answers the question a
 * reader arrived with and now says plainly that this one is gone. What they do
 * not do is get promoted on the homepage as something to go and buy.
 *
 * @param array $args WP_Query arguments.
 * @return array
 */
function lookdog_exclude_unavailable( $args ) {
	$gone = lookdog_unavailable_ids();
	if ( ! $gone ) {
		return $args;
	}
	$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $gone );
	return $args;
}
add_filter( 'lookdog_rail_query_args', 'lookdog_exclude_unavailable' );
add_filter( 'lookdog_ordered_widget_args', 'lookdog_exclude_unavailable' );

/* ---------------------------------------------------------------- schedule */

add_action( LOOKDOG_LINK_CHECK_HOOK, static function () { lookdog_link_check( 150 ); } );

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( LOOKDOG_LINK_CHECK_HOOK ) ) {
			// 04:30, half an hour after the price watch, so the two sweeps do
			// not compete for the same rate-limited endpoint at once.
			wp_schedule_event( strtotime( 'tomorrow 04:30' ), 'daily', LOOKDOG_LINK_CHECK_HOOK );
		}
	}
);
