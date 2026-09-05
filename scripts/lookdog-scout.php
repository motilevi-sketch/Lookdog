<?php
/**
 * LookDog - product scout.
 *
 * A standing search that runs itself. Once a week it sweeps AliExpress for
 * products in a price band the catalogue is missing, holds whatever clears a
 * quality floor, and leaves the list in wp-admin for a yes/no.
 *
 * WHY IT EXISTS. Grooming is the category the catalogue earns least from, and
 * the reason is arithmetic rather than taste: of its 39 products the median
 * price is about $4.50, and an affiliate commission on a $4 comb is small
 * enough that a hundred of them still buy nothing. Fifteen of those products
 * cost under $5. Traffic to that category is not the problem; what it sells is.
 *
 * WHAT THE $20-40 BAND LOOKS LIKE, measured rather than assumed. Search the
 * band directly with the API's own min_sale_price/max_sale_price and most of
 * what comes back has no evaluate_rate and no order volume at all - new
 * listings with no history. Asking productdetail.get about them individually
 * returns the same blanks, so the data is genuinely absent, not merely omitted
 * from the search response. Sorting by volume without the price parameters and
 * filtering the band here instead returns listings that have actually sold:
 * across eight keywords, 59 of 765 results cleared every bar.
 *
 * So the floor is calibrated to the band, not copied from the cheap end. In the
 * $2-7 range a 200-order minimum is easy - those products sell in thousands. At
 * $20-40 a product with 200 orders is a strong seller, and the floor here is 20,
 * paired with the same 84% positive-feedback bar used everywhere else. A listing
 * with no feedback figure at all is never kept, whatever its price.
 *
 * WHAT IT DOES NOT DO. It does not publish anything. Candidates sit in a list
 * until a human keeps or rejects them, and a rejection is remembered so the
 * same listing never comes back. The links in the admin table are plain
 * aliexpress.com item URLs, not the affiliate links - clicking through your own
 * tracking link to browse is exactly the pattern AliExpress reads as fraud.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-scout.php
 * Requires:    lookdog-harvest.php (ID variants, title key, catalogue lookups)
 *              wp-content/mu-plugins/lookdog-aliexpress.php (API client)
 */

defined( 'ABSPATH' ) || exit;

/**
 * What the scout is looking for.
 *
 * One entry per bucket. `keywords` are swept one per run so no single request
 * carries the whole sweep; a keyword costs about two seconds and two API calls.
 *
 * @return array
 */
function lookdog_scout_buckets() {
	$buckets = array(
		'grooming' => array(
			'label'      => 'Grooming, $20-40',
			'cat'        => 'grooming',
			'min_price'  => 20.0,
			'max_price'  => 40.0,
			'min_rate'   => 84.0,
			'min_volume' => 20,
			'pages'      => 2,
			'keywords'   => array(
				'professional dog clipper',
				'dog nail grinder professional',
				'pet grooming kit',
				'dog grooming table',
				'dog bathing tub',
				'dog grooming vacuum',
				'dog hair dryer blower',
				'pet grooming hammock',
				'dog grooming scissors set',
				'quiet dog trimmer rechargeable',
			),
		),
	);

	/**
	 * Filters the scout's buckets, for adding a category without editing this file.
	 *
	 * @param array $buckets Bucket definitions keyed by slug.
	 */
	return apply_filters( 'lookdog_scout_buckets', $buckets );
}

/**
 * Candidates held for one bucket.
 *
 * @param string $bucket Bucket slug.
 * @return array Keyed by AliExpress product ID.
 */
function lookdog_scout_store( $bucket ) {
	return (array) get_option( 'lookdog_scout_' . $bucket, array() );
}

/**
 * Product IDs already answered "no", so they are never offered twice.
 *
 * Held apart from the candidate store and as bare IDs, because the store is
 * pruned and this list is not: it has to outlive every record it refers to.
 *
 * @return array<string,int> ID => rejection timestamp.
 */
function lookdog_scout_rejected() {
	return (array) get_option( 'lookdog_scout_rejected', array() );
}

/**
 * The plain aliexpress.com URL for a product.
 *
 * The API answers in the 3256... numbering; item URLs use the 1005... one, and
 * the two differ by 2^51 (see lookdog_ae_id_variants). This deliberately does
 * not return the affiliate link: browsing your own tracked links is the
 * behaviour that gets a publisher penalised.
 *
 * @param string $id Product ID in either numbering.
 * @return string
 */
function lookdog_scout_item_url( $id ) {
	$variants = array_map( 'intval', lookdog_ae_id_variants( $id ) );
	return 'https://www.aliexpress.com/item/' . min( $variants ) . '.html';
}

/**
 * Sweep one keyword and merge whatever clears the bar into the bucket.
 *
 * @param string $bucket  Bucket slug.
 * @param string $keyword Search phrase.
 * @return array Counts. Never the records - they are far too large to hand back.
 */
function lookdog_scout_sweep( $bucket, $keyword ) {
	$buckets = lookdog_scout_buckets();
	if ( empty( $buckets[ $bucket ] ) ) {
		return array( 'error' => 'unknown bucket ' . $bucket );
	}
	$cfg = $buckets[ $bucket ];

	$store    = lookdog_scout_store( $bucket );
	$rejected = lookdog_scout_rejected();
	$have_ids = lookdog_harvest_existing_ids();
	$have_key = lookdog_harvest_existing_titles();
	foreach ( $store as $id => $rec ) {
		foreach ( lookdog_ae_id_variants( $id ) as $variant ) {
			$have_ids[ $variant ] = true;
		}
	}
	foreach ( array_keys( $rejected ) as $id ) {
		foreach ( lookdog_ae_id_variants( $id ) as $variant ) {
			$have_ids[ $variant ] = true;
		}
	}

	$stats = array(
		'seen'     => 0,
		'kept'     => 0,
		'no_price' => 0,
		'no_score' => 0,
		'low_bar'  => 0,
		'known'    => 0,
	);

	for ( $page = 1; $page <= (int) $cfg['pages']; $page++ ) {
		$resp = lookdog_ae_call(
			'aliexpress.affiliate.product.query',
			array(
				'keywords'        => $keyword,
				'category_ids'    => LOOKDOG_AE_PET_CAT,
				'page_size'       => '50',
				'page_no'         => (string) $page,
				// Deliberately not min_sale_price/max_sale_price: those return
				// the band's unproven listings. Sort by what sells, filter the
				// band here. See the header note.
				'sort'            => 'LAST_VOLUME_DESC',
				'target_currency' => 'USD',
				'target_language' => 'EN',
				'ship_to_country' => 'US',
				'tracking_id'     => 'default',
			)
		);

		$result = $resp['aliexpress_affiliate_product_query_response']['resp_result']['result'] ?? null;
		if ( ! $result || empty( $result['products']['product'] ) ) {
			break;
		}

		foreach ( $result['products']['product'] as $p ) {
			++$stats['seen'];

			$id = (string) ( $p['product_id'] ?? '' );
			if ( '' === $id || empty( $p['promotion_link'] ) ) {
				continue;
			}

			$price = (float) ( $p['target_sale_price'] ?? 0 );
			if ( $price < (float) $cfg['min_price'] || $price > (float) $cfg['max_price'] ) {
				++$stats['no_price'];
				continue;
			}

			// A blank rate is a listing with no sales history, not a perfect
			// one. Most of this band is blank; none of it is kept.
			$raw = isset( $p['evaluate_rate'] ) ? trim( (string) $p['evaluate_rate'] ) : '';
			if ( '' === $raw ) {
				++$stats['no_score'];
				continue;
			}
			$rate = (float) rtrim( $raw, '%' );
			$vol  = (int) ( $p['lastest_volume'] ?? 0 );
			if ( $rate < (float) $cfg['min_rate'] || $vol < (int) $cfg['min_volume'] ) {
				++$stats['low_bar'];
				continue;
			}

			if ( isset( $have_ids[ $id ] ) ) {
				++$stats['known'];
				continue;
			}

			$key  = lookdog_harvest_key( $p['product_title'] ?? '' );
			$imgs = array();
			if ( ! empty( $p['product_small_image_urls']['string'] ) ) {
				$imgs = array_slice( (array) $p['product_small_image_urls']['string'], 0, 6 );
			} elseif ( ! empty( $p['product_main_image_url'] ) ) {
				$imgs = array( $p['product_main_image_url'] );
			}

			$store[ $id ] = array(
				'id'     => $id,
				'title'  => (string) ( $p['product_title'] ?? '' ),
				'key'    => $key,
				'rate'   => $rate,
				'volume' => $vol,
				'price'  => (string) ( $p['target_sale_price'] ?? '' ),
				'promo'  => (string) $p['promotion_link'],
				'main'   => (string) ( $p['product_main_image_url'] ?? '' ),
				'imgs'   => $imgs,
				'kw'     => $keyword,
				'found'  => time(),
				// Not a rejection reason. A near-match to something already
				// stocked is often the point - a $25 clipper next to the $8 one
				// is an upgrade, and that is a judgement call, not a filter.
				'dupish' => isset( $have_key[ $key ] ) ? 1 : 0,
				'status' => 'new',
			);
			foreach ( lookdog_ae_id_variants( $id ) as $variant ) {
				$have_ids[ $variant ] = true;
			}
			++$stats['kept'];
		}
	}

	lookdog_scout_save( $bucket, $store );
	$stats['total'] = count( lookdog_scout_store( $bucket ) );
	return $stats;
}

/**
 * Persist a bucket, trimming it so the option cannot grow without limit.
 *
 * Anything marked "keep" survives the trim; the pending list is cut to the 80
 * best-selling records, because a list longer than that is not read.
 *
 * @param string $bucket Bucket slug.
 * @param array  $store  Records keyed by ID.
 * @return void
 */
function lookdog_scout_save( $bucket, $store ) {
	$keep    = array();
	$pending = array();
	foreach ( $store as $id => $rec ) {
		if ( 'keep' === ( $rec['status'] ?? 'new' ) ) {
			$keep[ $id ] = $rec;
		} else {
			$pending[ $id ] = $rec;
		}
	}
	uasort(
		$pending,
		static function ( $a, $b ) {
			return (int) $b['volume'] <=> (int) $a['volume'];
		}
	);
	update_option( 'lookdog_scout_' . $bucket, $keep + array_slice( $pending, 0, 80, true ), false );
}

/**
 * Answer a candidate.
 *
 * @param string $bucket Bucket slug.
 * @param string $id     Product ID.
 * @param string $answer 'keep' or 'no'.
 * @return void
 */
function lookdog_scout_answer( $bucket, $id, $answer ) {
	$store = lookdog_scout_store( $bucket );
	if ( empty( $store[ $id ] ) ) {
		return;
	}
	if ( 'no' === $answer ) {
		$rejected        = lookdog_scout_rejected();
		$rejected[ $id ] = time();
		update_option( 'lookdog_scout_rejected', $rejected, false );
		unset( $store[ $id ] );
	} else {
		$store[ $id ]['status'] = 'keep';
	}
	lookdog_scout_save( $bucket, $store );
}

/**
 * How many candidates are waiting on an answer, across every bucket.
 *
 * @return int
 */
function lookdog_scout_pending() {
	$n = 0;
	foreach ( array_keys( lookdog_scout_buckets() ) as $bucket ) {
		foreach ( lookdog_scout_store( $bucket ) as $rec ) {
			if ( 'new' === ( $rec['status'] ?? 'new' ) ) {
				++$n;
			}
		}
	}
	return $n;
}

/* -------------------------------------------------------------------------
 * The weekly sweep.
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'lookdog_scout_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', 'lookdog_scout_cron' );
		}
	}
);

add_filter(
	'cron_schedules', // phpcs:ignore WordPress.WP.CronInterval
	static function ( $s ) {
		if ( empty( $s['weekly'] ) ) {
			$s['weekly'] = array( 'interval' => WEEK_IN_SECONDS, 'display' => __( 'Once weekly', 'lookdog' ) );
		}
		return $s;
	}
);

add_action(
	'lookdog_scout_cron',
	static function () {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$log = array( 'when' => time(), 'kept' => 0, 'seen' => 0 );
		foreach ( lookdog_scout_buckets() as $bucket => $cfg ) {
			foreach ( $cfg['keywords'] as $kw ) {
				$s             = lookdog_scout_sweep( $bucket, $kw );
				$log['kept']  += (int) ( $s['kept'] ?? 0 );
				$log['seen']  += (int) ( $s['seen'] ?? 0 );
			}
		}
		update_option( 'lookdog_scout_last', $log, false );
	}
);

/* -------------------------------------------------------------------------
 * The screen.
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function () {
		$pending = lookdog_scout_pending();
		$title   = __( 'Product scout', 'lookdog' );
		if ( $pending ) {
			$title .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $pending . '</span></span>';
		}
		add_submenu_page(
			'lookdog-stats',
			__( 'Product scout', 'lookdog' ),
			$title,
			'manage_options',
			'lookdog-scout',
			'lookdog_scout_screen'
		);
	},
	11
);

/**
 * Handle the buttons before anything is printed, so a redirect is still possible.
 *
 * @return void
 */
add_action(
	'admin_init',
	static function () {
		if ( ! isset( $_POST['lookdog_scout'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'lookdog_scout' );

		$bucket = sanitize_key( wp_unslash( $_POST['bucket'] ?? '' ) );
		$action = sanitize_key( wp_unslash( $_POST['lookdog_scout'] ) );
		$notice = '';

		if ( 'run' === $action ) {
			$buckets = lookdog_scout_buckets();
			$words   = $buckets[ $bucket ]['keywords'] ?? array();
			$pos     = (int) get_option( 'lookdog_scout_pos_' . $bucket, 0 ) % max( 1, count( $words ) );
			if ( $words ) {
				@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				$stats = lookdog_scout_sweep( $bucket, $words[ $pos ] );
				update_option( 'lookdog_scout_pos_' . $bucket, $pos + 1, false );
				$notice = sprintf(
					/* translators: 1: search phrase, 2: results read, 3: candidates kept. */
					__( 'Searched %1$s - read %2$d listings, kept %3$d.', 'lookdog' ),
					$words[ $pos ],
					(int) ( $stats['seen'] ?? 0 ),
					(int) ( $stats['kept'] ?? 0 )
				);
			}
		} elseif ( 'keep' === $action || 'no' === $action ) {
			lookdog_scout_answer( $bucket, preg_replace( '~\D~', '', wp_unslash( $_POST['id'] ?? '' ) ), $action );
		}

		wp_safe_redirect(
			add_query_arg(
				array_filter(
					array(
						'page' => 'lookdog-scout',
						'msg'  => $notice ? rawurlencode( $notice ) : null,
					)
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
);

/**
 * One yes/no button.
 *
 * @param string $bucket Bucket slug.
 * @param string $id     Product ID.
 * @param string $action 'keep' or 'no'.
 * @param string $label  Button text.
 * @param string $class  Button class.
 * @return void
 */
function lookdog_scout_button( $bucket, $id, $action, $label, $class ) {
	?>
	<form method="post" style="display:inline">
		<?php wp_nonce_field( 'lookdog_scout' ); ?>
		<input type="hidden" name="bucket" value="<?php echo esc_attr( $bucket ); ?>">
		<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
		<button class="button <?php echo esc_attr( $class ); ?>" name="lookdog_scout" value="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
}

/**
 * The scout screen.
 *
 * @return void
 */
function lookdog_scout_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$buckets = lookdog_scout_buckets();
	$last    = (array) get_option( 'lookdog_scout_last', array() );
	$next    = wp_next_scheduled( 'lookdog_scout_cron' );
	$msg     = isset( $_GET['msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['msg'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
<div class="wrap">
	<h1><?php esc_html_e( 'Product scout', 'lookdog' ); ?></h1>

	<?php if ( $msg ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
	<?php endif; ?>

	<p class="description" style="max-width:46em">
		<?php esc_html_e( 'A weekly search for products in a price band the catalogue is short of. Nothing here is published: keep what looks worth stocking and it moves to the top of the list to be written up; reject it and that listing is never offered again. The links open the ordinary AliExpress page, not your affiliate link.', 'lookdog' ); ?>
	</p>

	<p class="description">
		<?php
		if ( ! empty( $last['when'] ) ) {
			printf(
				/* translators: 1: human time difference, 2: listings read, 3: candidates found. */
				esc_html__( 'Last full sweep %1$s ago: read %2$s listings, found %3$s worth a look.', 'lookdog' ),
				esc_html( human_time_diff( (int) $last['when'] ) ),
				esc_html( number_format_i18n( (int) $last['seen'] ) ),
				esc_html( number_format_i18n( (int) $last['kept'] ) )
			);
		} else {
			esc_html_e( 'The first full sweep has not run yet.', 'lookdog' );
		}
		if ( $next ) {
			echo ' ';
			printf(
				/* translators: %s: human time difference. */
				esc_html__( 'Next one due in %s, the next time somebody visits the site.', 'lookdog' ),
				esc_html( human_time_diff( (int) $next ) )
			);
		}
		?>
	</p>

	<?php foreach ( $buckets as $bucket => $cfg ) : ?>
		<?php
		$store = lookdog_scout_store( $bucket );
		uasort(
			$store,
			static function ( $a, $b ) {
				$sa = 'keep' === ( $a['status'] ?? 'new' ) ? 1 : 0;
				$sb = 'keep' === ( $b['status'] ?? 'new' ) ? 1 : 0;
				return $sa === $sb ? (int) $b['volume'] <=> (int) $a['volume'] : $sb <=> $sa;
			}
		);
		$words = $cfg['keywords'];
		$pos   = (int) get_option( 'lookdog_scout_pos_' . $bucket, 0 ) % max( 1, count( $words ) );
		?>
		<h2><?php echo esc_html( $cfg['label'] ); ?></h2>

		<form method="post" style="margin:0 0 12px">
			<?php wp_nonce_field( 'lookdog_scout' ); ?>
			<input type="hidden" name="bucket" value="<?php echo esc_attr( $bucket ); ?>">
			<button class="button" name="lookdog_scout" value="run">
				<?php
				printf(
					/* translators: %s: the next search phrase. */
					esc_html__( 'Search now for: %s', 'lookdog' ),
					esc_html( $words[ $pos ] ?? '' )
				);
				?>
			</button>
			<span class="description" style="margin-left:8px">
				<?php
				printf(
					/* translators: 1: position in the keyword list, 2: list length. */
					esc_html__( 'One phrase per click, %1$d of %2$d. The weekly sweep does all of them.', 'lookdog' ),
					(int) $pos + 1,
					count( $words )
				);
				?>
			</span>
		</form>

		<?php if ( ! $store ) : ?>
			<p><?php esc_html_e( 'Nothing waiting. Either the sweep has not run yet, or nothing in this band cleared the bar.', 'lookdog' ); ?></p>
			<?php continue; ?>
		<?php endif; ?>

		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:64px"></th>
					<th><?php esc_html_e( 'Product', 'lookdog' ); ?></th>
					<th style="width:80px"><?php esc_html_e( 'Price', 'lookdog' ); ?></th>
					<th style="width:80px"><?php esc_html_e( 'Positive', 'lookdog' ); ?></th>
					<th style="width:80px"><?php esc_html_e( 'Orders', 'lookdog' ); ?></th>
					<th style="width:170px"></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $store as $id => $r ) : ?>
				<tr<?php echo 'keep' === ( $r['status'] ?? 'new' ) ? ' style="background:#f0f6e8"' : ''; ?>>
					<td>
						<?php if ( $r['main'] ) : ?>
							<img src="<?php echo esc_url( $r['main'] ); ?>" alt="" width="56" height="56" style="width:56px;height:56px;object-fit:cover;border-radius:4px">
						<?php endif; ?>
					</td>
					<td>
						<a href="<?php echo esc_url( lookdog_scout_item_url( $id ) ); ?>" target="_blank" rel="nofollow noopener"><?php echo esc_html( $r['title'] ); ?></a>
						<div class="row-actions" style="left:auto">
							<span><?php echo esc_html( $r['kw'] ); ?></span>
							<?php if ( ! empty( $r['dupish'] ) ) : ?>
								&middot; <span style="color:#996800"><?php esc_html_e( 'similar to something already stocked', 'lookdog' ); ?></span>
							<?php endif; ?>
							<?php if ( 'keep' === ( $r['status'] ?? 'new' ) ) : ?>
								&middot; <strong><?php esc_html_e( 'kept - waiting to be written up', 'lookdog' ); ?></strong>
							<?php endif; ?>
						</div>
					</td>
					<td style="font-variant-numeric:tabular-nums">$<?php echo esc_html( $r['price'] ); ?></td>
					<td style="font-variant-numeric:tabular-nums"><?php echo esc_html( number_format( (float) $r['rate'], 1 ) ); ?>%</td>
					<td style="font-variant-numeric:tabular-nums"><?php echo esc_html( number_format_i18n( (int) $r['volume'] ) ); ?></td>
					<td>
						<?php if ( 'keep' !== ( $r['status'] ?? 'new' ) ) : ?>
							<?php lookdog_scout_button( $bucket, $id, 'keep', __( 'Keep', 'lookdog' ), 'button-primary' ); ?>
						<?php endif; ?>
						<?php lookdog_scout_button( $bucket, $id, 'no', __( 'Not for me', 'lookdog' ), '' ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>

	<p class="description" style="margin-top:16px">
		<?php
		printf(
			/* translators: %s: number of rejected listings. */
			esc_html__( '%s listings have been turned down and will not be offered again.', 'lookdog' ),
			esc_html( number_format_i18n( count( lookdog_scout_rejected() ) ) )
		);
		?>
	</p>
</div>
	<?php
}
