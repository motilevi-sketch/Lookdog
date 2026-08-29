<?php
/**
 * LookDog - candidate harvester for bulk imports.
 *
 * The first import went through aliexpress.affiliate.productdetail.get, one
 * product ID at a time. That endpoint has a hard daily ceiling: somewhere past
 * 160 IDs it stops erroring and starts returning empty results for everything,
 * including IDs that worked minutes earlier, which is indistinguishable from a
 * product being delisted.
 *
 * aliexpress.affiliate.product.query avoids the problem entirely. One call
 * returns up to 50 products already carrying every field the importer needs -
 * promotion_link, the image list, price, feedback score and order volume - so a
 * 70-product import costs a few dozen search calls instead of 70 detail calls.
 *
 * ON THE QUALITY FLOOR. The affiliate API publishes no five-star rating; the
 * only score it exposes is `evaluate_rate`, a positive-feedback percentage.
 * A 4.2-of-5 bar is therefore applied as 84% (4.2 / 5), and paired with a
 * minimum order volume, because 100% positive on nine orders is noise while
 * 88% on four thousand is a real signal.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-harvest.php
 * Requires:    wp-content/mu-plugins/lookdog-aliexpress.php (API client)
 */

defined( 'ABSPATH' ) || exit;

/** AliExpress second-level category for Pet Products. Without it a search for
 * "dog bed" returns dachshund slippers and bean-bag filler. */
const LOOKDOG_AE_PET_CAT = '100006664';

/**
 * Normalised title, for spotting the same listing resold under a new ID.
 *
 * @param string $title Raw product title.
 * @return string
 */
function lookdog_harvest_key( $title ) {
	$t = strtolower( wp_strip_all_tags( (string) $title ) );
	$t = preg_replace( '~[^a-z0-9 ]+~', ' ', $t );
	$t = preg_replace( '~\b(new|hot|free shipping|for|the|and|with|pet|pets|dog|dogs|cat|cats|puppy)\b~', ' ', $t );
	$t = trim( preg_replace( '~\s+~', ' ', $t ) );
	$words = array_slice( explode( ' ', $t ), 0, 6 );
	sort( $words );
	return implode( '-', $words );
}

/**
 * Every AliExpress product ID already in the catalogue.
 *
 * @return array<string,true>
 */
function lookdog_harvest_existing_ids() {
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_lookdog_ae_id'" );
	return array_fill_keys( array_map( 'strval', $ids ), true );
}

/**
 * Normalised titles already in the catalogue, so a near-duplicate listing under
 * a different ID does not slip through the ID check.
 *
 * @return array<string,true>
 */
function lookdog_harvest_existing_titles() {
	global $wpdb;
	$titles = $wpdb->get_col(
		"SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status IN ('publish','draft','pending')"
	);
	$keys = array();
	foreach ( $titles as $t ) {
		$keys[ lookdog_harvest_key( $t ) ] = true;
	}
	return $keys;
}

/**
 * Run one keyword search and merge whatever clears the bar into the bucket.
 *
 * @param string $bucket   Category slug the candidates are being gathered for.
 * @param string $keyword  Search phrase.
 * @param array  $opts     min_rate, min_volume, pages, sort.
 * @return array Counts, never the records themselves - they are far too large
 *               to hand back through a tool call.
 */
function lookdog_harvest( $bucket, $keyword, $opts = array() ) {
	$opts = array_merge(
		array(
			'min_rate'   => 84.0,
			'min_volume' => 200,
			'pages'      => 1,
			'sort'       => 'LAST_VOLUME_DESC',
			'page_size'  => 50,
		),
		$opts
	);

	$store    = get_option( 'lookdog_cand_' . $bucket, array() );
	$have_ids = lookdog_harvest_existing_ids();
	$have_key = lookdog_harvest_existing_titles();
	foreach ( $store as $id => $rec ) {
		$have_ids[ (string) $id ] = true;
		if ( ! empty( $rec['key'] ) ) {
			$have_key[ $rec['key'] ] = true;
		}
	}

	$stats = array(
		'seen'      => 0,
		'kept'      => 0,
		'low_rate'  => 0,
		'low_vol'   => 0,
		'duplicate' => 0,
		'errors'    => array(),
	);

	for ( $page = 1; $page <= (int) $opts['pages']; $page++ ) {
		$resp = lookdog_ae_call(
			'aliexpress.affiliate.product.query',
			array(
				'keywords'        => $keyword,
				'category_ids'    => LOOKDOG_AE_PET_CAT,
				'page_size'       => (string) $opts['page_size'],
				'page_no'         => (string) $page,
				'sort'            => $opts['sort'],
				'target_currency' => 'USD',
				'target_language' => 'EN',
				'ship_to_country' => 'US',
				'tracking_id'     => 'default',
			)
		);

		$result = $resp['aliexpress_affiliate_product_query_response']['resp_result']['result'] ?? null;
		if ( ! $result || empty( $result['products']['product'] ) ) {
			$stats['errors'][] = 'page ' . $page . ': no result';
			break;
		}

		foreach ( $result['products']['product'] as $p ) {
			++$stats['seen'];
			$id = (string) ( $p['product_id'] ?? '' );
			if ( '' === $id || empty( $p['promotion_link'] ) ) {
				continue;
			}

			$rate = (float) rtrim( (string) ( $p['evaluate_rate'] ?? '0' ), '%' );
			$vol  = (int) ( $p['lastest_volume'] ?? 0 );

			if ( $rate < (float) $opts['min_rate'] ) {
				++$stats['low_rate'];
				continue;
			}
			if ( $vol < (int) $opts['min_volume'] ) {
				++$stats['low_vol'];
				continue;
			}

			$key = lookdog_harvest_key( $p['product_title'] ?? '' );
			if ( isset( $have_ids[ $id ] ) || isset( $have_key[ $key ] ) ) {
				++$stats['duplicate'];
				continue;
			}

			$imgs = array();
			if ( ! empty( $p['product_small_image_urls']['string'] ) ) {
				$imgs = array_slice( (array) $p['product_small_image_urls']['string'], 0, 6 );
			} elseif ( ! empty( $p['product_main_image_url'] ) ) {
				$imgs = array( $p['product_main_image_url'] );
			}

			$store[ $id ] = array(
				'id'      => $id,
				'title'   => (string) ( $p['product_title'] ?? '' ),
				'key'     => $key,
				'rate'    => $rate,
				'volume'  => $vol,
				'price'   => (string) ( $p['target_sale_price'] ?? '' ),
				'promo'   => (string) $p['promotion_link'],
				'main'    => (string) ( $p['product_main_image_url'] ?? '' ),
				'imgs'    => $imgs,
				'kw'      => $keyword,
			);
			$have_ids[ $id ] = true;
			$have_key[ $key ] = true;
			++$stats['kept'];
		}
	}

	update_option( 'lookdog_cand_' . $bucket, $store, false );
	$stats['bucket_total'] = count( $store );
	return $stats;
}

/**
 * Compact listing of a bucket, for choosing what to import. Deliberately drops
 * the affiliate link and image URLs, which are what make the records huge.
 *
 * @param string $bucket Category slug.
 * @param int    $limit  Rows to return.
 * @return array
 */
function lookdog_harvest_list( $bucket, $limit = 60 ) {
	$store = get_option( 'lookdog_cand_' . $bucket, array() );
	uasort(
		$store,
		static function ( $a, $b ) {
			return $b['volume'] <=> $a['volume'];
		}
	);
	$rows = array();
	foreach ( array_slice( $store, 0, $limit, true ) as $id => $r ) {
		$rows[] = array( $id, $r['title'], $r['rate'], $r['volume'], $r['price'] );
	}
	return $rows;
}

/**
 * Feed one harvested candidate into the existing importer.
 *
 * lookdog_toy_create() reads its source data from the `lookdog_toy_details`
 * option, so the record is copied across under the shape that function expects
 * rather than duplicating the creation logic.
 *
 * @param string $bucket Category slug the candidate was harvested into.
 * @param array  $copy   Written copy for the product; see lookdog_toy_create().
 * @return array
 */
function lookdog_harvest_import( $bucket, $copy ) {
	$store = get_option( 'lookdog_cand_' . $bucket, array() );
	$id    = (string) $copy['ae_id'];
	if ( empty( $store[ $id ] ) ) {
		return array( 'ae_id' => $id, 'status' => 'error', 'msg' => 'not in bucket ' . $bucket );
	}

	$details        = get_option( 'lookdog_toy_details', array() );
	$details[ $id ] = array(
		'promo' => $store[ $id ]['promo'],
		'imgs'  => $store[ $id ]['imgs'],
		'main'  => $store[ $id ]['main'],
	);
	update_option( 'lookdog_toy_details', $details, false );

	$res = lookdog_toy_create( $copy );

	// The facts strip reads these; without them a new product renders no strip
	// at all while every older one shows price, score and orders.
	if ( 'created' === $res['status'] ) {
		update_post_meta( $res['post_id'], '_lookdog_price', $store[ $id ]['price'] );
		update_post_meta( $res['post_id'], '_lookdog_currency', 'USD' );
		update_post_meta( $res['post_id'], '_lookdog_rate', number_format( $store[ $id ]['rate'], 1 ) . '%' );
		update_post_meta( $res['post_id'], '_lookdog_orders', $store[ $id ]['volume'] );
		update_post_meta( $res['post_id'], '_lookdog_facts_date', gmdate( 'Y-m-d' ) );
		delete_transient( 'lookdog_rating_floor' );
		delete_transient( 'lookdog_product_count' );
	}
	return $res;
}
