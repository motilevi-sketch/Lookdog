<?php
/**
 * LookDog - affiliate click tracking and an admin stats screen.
 *
 * The problem this solves: on an affiliate site the only action that can ever
 * earn anything is a click on "Check Price on AliExpress", and that click
 * leaves the site. Nothing in WordPress or WooCommerce records it. Until now
 * the site could tell you a product page was viewed 400 times and had no way
 * of telling you whether anybody pressed the button.
 *
 * lookdog-analytics.php already sends an `affiliate_click` event to GA4, but
 * that is dead code until a Measurement ID is stored, and it depends on the
 * visitor allowing Google scripts to run - which a meaningful share block. This
 * counts server side instead, so the number is complete and works with no third
 * party involved at all.
 *
 * WHERE THE NUMBERS LIVE
 *   _lookdog_clicks       post meta, lifetime click count per product
 *   _lookdog_clicks_last  post meta, unix time of the most recent click
 *   lookdog_click_days    option, site-wide clicks per day (small, 120 days)
 *
 * Per-product totals are post meta rather than one big option so the table can
 * be sorted by the database and cannot outgrow a single row.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-stats.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Route the buy button through /out/{id} so the click can be counted.
 *
 * Applied to the external-product button URL. If anything about the product is
 * wrong the original URL is returned untouched - a broken redirect here would
 * break the buy button on every product on the site, so this fails open.
 *
 * @param string     $url     Destination the button would use.
 * @param WC_Product $product Product being rendered.
 * @return string
 */
function lookdog_click_button_url( $url, $product ) {
	if ( is_admin() || ! $product instanceof WC_Product || ! $url ) {
		return $url;
	}
	$id = (int) $product->get_id();
	if ( ! $id ) {
		return $url;
	}
	return home_url( '/out/' . $id );
}
add_filter( 'woocommerce_product_add_to_cart_url', 'lookdog_click_button_url', 10, 2 );

/*
 * A note on rel="sponsored", which is deliberately not set here.
 *
 * Routing through /out/{id} means the AliExpress URL no longer appears in the
 * page source at all - a crawler sees an internal link to a page that returns
 * X-Robots-Tag: noindex, nofollow and a 302. That is a stronger signal than any
 * rel attribute, and it makes the attribute redundant. Astra also renders its
 * own loop button markup rather than WooCommerce's, so the usual filter for
 * adding rel never fires on this theme; an earlier version of this file set it
 * and silently did nothing.
 */

/**
 * Record one click.
 *
 * @param int $post_id Product ID.
 * @return void
 */
function lookdog_click_count( $post_id ) {
	$post_id = (int) $post_id;
	$total   = (int) get_post_meta( $post_id, '_lookdog_clicks', true );
	update_post_meta( $post_id, '_lookdog_clicks', $total + 1 );
	update_post_meta( $post_id, '_lookdog_clicks_last', time() );

	$days = (array) get_option( 'lookdog_click_days', array() );
	$day  = gmdate( 'Y-m-d' );
	$days[ $day ] = ( isset( $days[ $day ] ) ? (int) $days[ $day ] : 0 ) + 1;
	if ( count( $days ) > 120 ) {
		ksort( $days );
		$days = array_slice( $days, -120, null, true );
	}
	update_option( 'lookdog_click_days', $days, false );

	/**
	 * Fired for every counted click.
	 *
	 * Kept as an action so anything that wants to know who is clicking - see
	 * lookdog-click-log.php - can listen without this function growing a second
	 * job it does not need to know about.
	 *
	 * @param int $post_id Product clicked.
	 */
	do_action( 'lookdog_click', $post_id );
}

add_action(
	'init',
	static function () {
		add_rewrite_rule( '^out/([0-9]+)/?$', 'index.php?lookdog_out=$matches[1]', 'top' );
	}
);

add_filter(
	'query_vars',
	static function ( $vars ) {
		$vars[] = 'lookdog_out';
		return $vars;
	}
);

/**
 * Is the current request an automated one?
 *
 * The real test lives in lookdog-click-log.php, which owns the pattern list.
 * This falls back to the two crawlers actually seen hitting this site, so the
 * guard still holds if that file is ever disabled.
 *
 * @return bool
 */
function lookdog_out_is_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
	if ( function_exists( 'lookdog_click_is_bot' ) ) {
		return lookdog_click_is_bot( $ua );
	}
	return '' === trim( $ua ) || (bool) preg_match( '~bot|crawl|spider|claude|anthropic~i', $ua );
}

/**
 * Keep well-behaved crawlers off the redirect in the first place.
 *
 * The guard above is what actually protects the affiliate links; this saves the
 * polite ones the round trip, and says plainly that /out/ is not content.
 *
 * @param string $rules Existing robots.txt body.
 * @return string
 */
function lookdog_out_robots( $rules ) {
	return $rules . "\nUser-agent: *\nDisallow: /out/\n";
}
add_filter( 'robots_txt', 'lookdog_out_robots', 20 );

add_action(
	'template_redirect',
	static function () {
		$id = (int) get_query_var( 'lookdog_out' );
		if ( ! $id ) {
			return;
		}

		// A withdrawn product still has its old affiliate URL in the database,
		// and that URL still answers 200 - it just lands somewhere that is not
		// the product. Sending a buyer there is worse than sending them
		// nowhere, so they go to the product page instead, which says plainly
		// that it is gone and names what we list in its place. Archive cards
		// are the route that matters here: the single page has already had its
		// button removed, but a category or tag grid still shows one.
		if ( function_exists( 'lookdog_is_unavailable' ) && lookdog_is_unavailable( $id ) ) {
			$page = get_permalink( $id );
			wp_safe_redirect( $page ? $page : home_url( '/' ), 302 );
			exit;
		}

		$dest = trim( (string) get_post_meta( $id, '_product_url', true ) );

		// Nothing to send them to: fall back to the product page rather than a
		// 404, so a missing meta value never becomes a dead buy button.
		if ( '' === $dest || ! wp_http_validate_url( $dest ) ) {
			$fallback = get_permalink( $id );
			wp_safe_redirect( $fallback ? $fallback : home_url( '/' ), 302 );
			exit;
		}

		// Crawlers never reach the affiliate link.
		//
		// This was not a theory. Click logging started on 3 September and by
		// the 5th it held 400 records: 307 ClaudeBot, 93 AhrefsBot, and not one
		// human. The daily totals jumped from about 40 to 297 on the day the
		// crawlers found the redirect. Every one of those was a real request to
		// an affiliate URL, generated by no buyer at all - which is what
		// AliExpress calls invalid traffic, on a site already appealing a
		// penalty.
		//
		// A crawler is sent to the product page instead: the link still
		// resolves for anything following it, the affiliate URL is never
		// handed out, and the visit is counted separately so the split stays
		// visible rather than silently disappearing.
		if ( lookdog_out_is_bot() ) {
			$days = (array) get_option( 'lookdog_click_bot_days', array() );
			$key  = gmdate( 'Y-m-d' );

			$days[ $key ] = ( isset( $days[ $key ] ) ? (int) $days[ $key ] : 0 ) + 1;
			if ( count( $days ) > 120 ) {
				ksort( $days );
				$days = array_slice( $days, -120, null, true );
			}
			update_option( 'lookdog_click_bot_days', $days, false );

			$page = get_permalink( $id );
			wp_safe_redirect( $page ? $page : home_url( '/' ), 302 );
			exit;
		}

		// Owner clicks would otherwise inflate every number on the dashboard.
		if ( ! is_user_logged_in() ) {
			lookdog_click_count( $id );
		}

		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'lookdog affiliate redirect' );
		// Belt and braces alongside the action above. A cached affiliate
		// redirect would skip PHP entirely - the click uncounted, the visitor
		// sent to whatever destination was cached earlier - and the stakes are
		// higher here than on an ordinary page. Testing found the redirect was
		// NOT being cached, so this is hardening rather than a fix; it also
		// covers proxies between the visitor and LiteSpeed, which the action
		// does not reach.
		header( 'X-LiteSpeed-Cache-Control: no-cache', true );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true );
		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Referrer-Policy: no-referrer-when-downgrade', true );
		wp_redirect( $dest, 302 );
		exit;
	},
	1
);

/**
 * Site-wide clicks over the last N days.
 *
 * @param int $days Days to include, counting back from today.
 * @return int
 */
function lookdog_clicks_since( $days ) {
	$rows  = (array) get_option( 'lookdog_click_days', array() );
	$from  = gmdate( 'Y-m-d', time() - ( (int) $days * DAY_IN_SECONDS ) );
	$total = 0;
	foreach ( $rows as $day => $n ) {
		if ( $day >= $from ) {
			$total += (int) $n;
		}
	}
	return $total;
}

/**
 * Products ordered by lifetime clicks.
 *
 * @param int $limit Rows to return.
 * @return array<int,array{id:int,title:string,clicks:int}>
 */
function lookdog_top_clicked( $limit = 25 ) {
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_title, CAST( m.meta_value AS UNSIGNED ) AS clicks
			 FROM {$wpdb->postmeta} m
			 INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			 WHERE m.meta_key = '_lookdog_clicks'
			   AND p.post_type = 'product' AND p.post_status = 'publish'
			 ORDER BY clicks DESC, p.post_title ASC
			 LIMIT %d",
			(int) $limit
		)
	);
	$out = array();
	foreach ( (array) $rows as $r ) {
		$out[] = array(
			'id'     => (int) $r->ID,
			'title'  => (string) $r->post_title,
			'clicks' => (int) $r->clicks,
		);
	}
	return $out;
}

/**
 * Plugins switched off because this site does not sell anything directly, and
 * the condition that would make each one useful again.
 *
 * Kept here rather than in a note somewhere, because the reason a plugin was
 * disabled is exactly the thing nobody remembers eighteen months later, and the
 * cost of forgetting is either an unnecessary reinstall or a missing feature
 * nobody can explain.
 *
 * @return array<string,array{name:string,does:string,back:string}>
 */
function lookdog_dormant_plugins() {
	return array(
		'woo-cart-abandonment-recovery/woo-cart-abandonment-recovery.php' => array(
			'name' => 'Cart Abandonment Recovery',
			'does' => 'Captures an email address at checkout and chases people who leave without paying.',
			'back' => 'When lookdog.club has a real checkout of its own. It can do nothing while every product is an outbound affiliate link, because there is no cart to abandon.',
		),
		'surecart/surecart.php' => array(
			'name' => 'SureCart',
			'does' => 'A separate shop and checkout platform, for selling your own products rather than linking to someone else\'s.',
			'back' => 'When you sell something you own — a guide, a course, a subscription. Not needed for affiliate products, which WooCommerce already handles as external links.',
		),
		'woocommerce-payments/woocommerce-payments.php' => array(
			'name' => 'WooPayments',
			'does' => 'Takes card payments through WooCommerce.',
			'back' => 'When you fulfil an order and take the money yourself. It was never connected to an account, and the site has never processed an order.',
		),
	);
}

add_action(
	'admin_menu',
	static function () {
		add_menu_page(
			__( 'LookDog Stats', 'lookdog' ),
			__( 'LookDog', 'lookdog' ),
			'manage_options',
			'lookdog-stats',
			'lookdog_stats_screen',
			'dashicons-chart-bar',
			26
		);
	}
);

/**
 * The stats screen.
 *
 * @return void
 */
function lookdog_stats_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$ga4      = function_exists( 'lookdog_ga4_id' ) ? lookdog_ga4_id() : '';
	$days     = (array) get_option( 'lookdog_click_days', array() );
	$total    = array_sum( array_map( 'intval', $days ) );
	$top      = lookdog_top_clicked( 25 );
	$go_stats = (array) get_option( 'lookdog_go_stats', array() );
	$products = (int) wp_count_posts( 'product' )->publish;
	?>
<div class="wrap lookdog-stats">
	<h1><?php esc_html_e( 'LookDog Stats', 'lookdog' ); ?></h1>

	<?php if ( '' === $ga4 ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Google Analytics is not connected.', 'lookdog' ); ?></strong>
			<?php esc_html_e( 'The numbers below are outbound clicks counted by this site. For visitor numbers, traffic sources and search terms you also need a GA4 Measurement ID.', 'lookdog' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * The honest headline.
	 *
	 * Until 5 September every crawler that followed a buy button was counted as
	 * a click, so the all-time figure is inflated and cannot be repaired -
	 * there is no record of which of the older ones were real. Saying so on the
	 * screen is better than quietly restating a number that was never true.
	 */
	$bot_days  = (array) get_option( 'lookdog_click_bot_days', array() );
	$bot_total = array_sum( array_map( 'intval', $bot_days ) );
	?>

	<?php if ( $total ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Read the click numbers with care.', 'lookdog' ); ?></strong>
			<?php esc_html_e( 'Until 5 September 2026 a crawler following a buy button counted as a click. Between 3 and 5 September every logged click was automated - 307 from ClaudeBot, 93 from AhrefsBot, none from a person - and the daily total jumped from about 40 to 297 on the day the crawlers found the link. They are now stopped before the affiliate link and counted separately, so figures from 5 September onward are people. Earlier ones are not.', 'lookdog' ); ?></p>
			<?php if ( $bot_total ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: number of automated requests turned away. */
						esc_html__( '%s automated requests have been turned away since the guard went in.', 'lookdog' ),
						'<strong>' . esc_html( number_format_i18n( $bot_total ) ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="ld-cards">
		<div class="ld-card">
			<span class="ld-card__n"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
			<span class="ld-card__l"><?php esc_html_e( 'Affiliate clicks, all time', 'lookdog' ); ?></span>
		</div>
		<div class="ld-card">
			<span class="ld-card__n"><?php echo esc_html( number_format_i18n( $bot_total ) ); ?></span>
			<span class="ld-card__l"><?php esc_html_e( 'Crawlers turned away', 'lookdog' ); ?></span>
		</div>
		<div class="ld-card">
			<span class="ld-card__n"><?php echo esc_html( number_format_i18n( lookdog_clicks_since( 30 ) ) ); ?></span>
			<span class="ld-card__l"><?php esc_html_e( 'Last 30 days', 'lookdog' ); ?></span>
		</div>
		<div class="ld-card">
			<span class="ld-card__n"><?php echo esc_html( number_format_i18n( lookdog_clicks_since( 7 ) ) ); ?></span>
			<span class="ld-card__l"><?php esc_html_e( 'Last 7 days', 'lookdog' ); ?></span>
		</div>
		<div class="ld-card">
			<span class="ld-card__n"><?php echo esc_html( number_format_i18n( $products ) ); ?></span>
			<span class="ld-card__l"><?php esc_html_e( 'Published products', 'lookdog' ); ?></span>
		</div>
	</div>

	<?php
	/**
	 * Who is clicking, first.
	 *
	 * It sits above "most clicked" because the owner opens this screen from the
	 * admin menu, not the phone dashboard, and because a click total means
	 * nothing until you know whether a person produced it. The same card is on
	 * /dashboard/; this is the copy in the place he actually looks.
	 */
	if ( function_exists( 'lookdog_click_agents' ) ) :
		$agents = lookdog_click_agents( 7 );
		$auto   = 0;
		foreach ( $agents as $a ) {
			if ( $a['bot'] ) {
				$auto += $a['clicks'];
			}
		}
		?>
		<h2><?php esc_html_e( 'Who is clicking', 'lookdog' ); ?></h2>
		<?php if ( ! $agents ) : ?>
			<p class="description">
				<?php esc_html_e( 'Nothing logged yet. Recording started on 3 September 2026 and fills up as the buy button is pressed. Your own clicks are never counted while you are logged in.', 'lookdog' ); ?>
			</p>
		<?php else : ?>
			<?php if ( $auto ) : ?>
				<div class="notice notice-error inline" style="margin:0 0 12px">
					<p>
						<?php
						printf(
							/* translators: %s: number of automated clicks. */
							esc_html__( '%s of the clicks in the last seven days came from something automated rather than a person. AliExpress penalises publishers for click fraud, so this is worth chasing down.', 'lookdog' ),
							'<strong>' . esc_html( number_format_i18n( $auto ) ) . '</strong>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>
			<table class="widefat striped">
				<thead><tr>
					<th style="width:80px"><?php esc_html_e( 'Clicks', 'lookdog' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Kind', 'lookdog' ); ?></th>
					<th><?php esc_html_e( 'Browser or program', 'lookdog' ); ?></th>
					<th style="width:150px"><?php esc_html_e( 'Spread', 'lookdog' ); ?></th>
					<th style="width:120px"><?php esc_html_e( 'Came from', 'lookdog' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $agents as $a ) : ?>
					<tr>
						<td><strong><?php echo esc_html( number_format_i18n( $a['clicks'] ) ); ?></strong></td>
						<td>
							<?php if ( $a['bot'] ) : ?>
								<span style="color:#b32d2e;font-weight:600"><?php esc_html_e( 'automated', 'lookdog' ); ?></span>
							<?php else : ?>
								<span style="color:#007017;font-weight:600"><?php esc_html_e( 'browser', 'lookdog' ); ?></span>
							<?php endif; ?>
						</td>
						<td><code style="font-size:11px"><?php echo esc_html( '' !== $a['ua'] ? $a['ua'] : 'no user agent sent' ); ?></code></td>
						<td>
							<?php
							printf(
								/* translators: 1: products, 2: sources. */
								esc_html__( '%1$s products, %2$s source(s)', 'lookdog' ),
								esc_html( number_format_i18n( $a['products'] ) ),
								esc_html( number_format_i18n( $a['sources'] ) )
							);
							?>
						</td>
						<td><?php echo esc_html( $a['refs'] ? implode( ', ', array_slice( $a['refs'], 0, 2 ) ) : '&mdash;' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'One source clicking across many products is a program walking the catalogue. Several sources with one or two clicks each are people. IP addresses are not stored - only a salted hash, enough to count sources and not enough to identify anyone.', 'lookdog' ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Most clicked products', 'lookdog' ); ?></h2>
	<?php if ( ! $top ) : ?>
		<p><?php esc_html_e( 'No clicks recorded yet. Counting started when this screen was installed; your own clicks are never counted while you are logged in.', 'lookdog' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Product', 'lookdog' ); ?></th>
				<th style="width:120px"><?php esc_html_e( 'Clicks', 'lookdog' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $top as $row ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( (string) get_permalink( $row['id'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a></td>
					<td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Short link clicks', 'lookdog' ); ?></h2>
	<p class="description"><?php esc_html_e( 'The /go/ links used in social posts and the bio page.', 'lookdog' ); ?></p>
	<?php if ( ! $go_stats ) : ?>
		<p><?php esc_html_e( 'No short link clicks recorded yet.', 'lookdog' ); ?></p>
	<?php else : ?>
		<?php
		uasort(
			$go_stats,
			static function ( $a, $b ) {
				return (int) $b['total'] <=> (int) $a['total'];
			}
		);
		?>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Link', 'lookdog' ); ?></th>
				<th style="width:120px"><?php esc_html_e( 'Clicks', 'lookdog' ); ?></th>
				<th style="width:180px"><?php esc_html_e( 'Last click', 'lookdog' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $go_stats as $slug => $row ) : ?>
				<tr>
					<td><code>/go/<?php echo esc_html( $slug ); ?></code></td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['total'] ) ); ?></td>
					<td><?php
						echo empty( $row['last'] )
							? '&mdash;'
							: esc_html( date_i18n( 'j M Y, H:i', (int) $row['last'] ) );
					?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Daily link check', 'lookdog' ); ?></h2>
	<?php
	$lc = get_option( 'lookdog_link_check_report' );
	if ( ! is_array( $lc ) ) :
		?>
		<p><?php esc_html_e( 'Has not run yet. It runs every night at 04:30.', 'lookdog' ); ?></p>
	<?php else : ?>
		<p class="description">
			<?php
			printf(
				/* translators: 1: date and time of the last run, 2: products checked, 3: products confirmed live. */
				esc_html__( 'Last run %1$s — checked %2$s, still listed %3$s.', 'lookdog' ),
				esc_html( $lc['ran'] ),
				esc_html( number_format_i18n( (int) $lc['checked'] ) ),
				esc_html( number_format_i18n( (int) $lc['alive'] ) )
			);
			if ( ! empty( $lc['stopped_early'] ) ) {
				echo ' ';
				esc_html_e( 'Stopped early because the supplier stopped answering; it resumes where it left off tomorrow.', 'lookdog' );
			}
			?>
		</p>
		<?php
		$gone   = function_exists( 'lookdog_unavailable_ids' ) ? lookdog_unavailable_ids() : array();
		$strike = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_lookdog_miss', 'compare' => 'EXISTS' ) ),
			)
		);
		?>
		<?php if ( $gone ) : ?>
			<h3><?php esc_html_e( 'No longer sold — replace these', 'lookdog' ); ?></h3>
			<p class="description"><?php esc_html_e( 'The supplier has stopped listing these. The buy button is already hidden and the page says so. They are not swapped automatically, because picking a substitute is a judgement about what a buyer actually wanted.', 'lookdog' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Product', 'lookdog' ); ?></th><th style="width:140px"><?php esc_html_e( 'Gone since', 'lookdog' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $gone as $gid ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( (string) get_edit_post_link( $gid ) ); ?>"><?php echo esc_html( get_the_title( $gid ) ); ?></a></td>
						<td><?php echo esc_html( (string) get_post_meta( $gid, '_lookdog_unavailable_since', true ) ?: '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php if ( $strike ) : ?>
			<h3><?php esc_html_e( 'Watching', 'lookdog' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Missing from the supplier\'s answer, but not yet confirmed gone. Three consecutive misses are needed, because the API goes quiet under load and one silence proves nothing. These are still on sale meanwhile.', 'lookdog' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Product', 'lookdog' ); ?></th><th style="width:140px"><?php esc_html_e( 'Misses', 'lookdog' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $strike as $sid ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( (string) get_permalink( $sid ) ); ?>"><?php echo esc_html( get_the_title( $sid ) ); ?></a></td>
						<td><?php echo esc_html( (string) get_post_meta( $sid, '_lookdog_miss', true ) ); ?> <?php esc_html_e( 'of 3', 'lookdog' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php if ( ! $gone && ! $strike ) : ?>
			<p><?php esc_html_e( 'Every product was still listed at the last check.', 'lookdog' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$dormant = array();
	foreach ( lookdog_dormant_plugins() as $file => $info ) {
		if ( ! is_plugin_active( $file ) ) {
			$dormant[ $file ] = $info;
		}
	}
	if ( $dormant ) :
		?>
		<h2><?php esc_html_e( 'Switched off, and when you would need them back', 'lookdog' ); ?></h2>
		<p class="description"><?php esc_html_e( 'These were deactivated because this site earns from affiliate links rather than selling anything itself. Nothing here is deleted — each one can be switched back on from Plugins.', 'lookdog' ); ?></p>
		<table class="widefat striped">
			<thead><tr>
				<th style="width:190px"><?php esc_html_e( 'Plugin', 'lookdog' ); ?></th>
				<th><?php esc_html_e( 'What it does', 'lookdog' ); ?></th>
				<th><?php esc_html_e( 'Switch it back on when', 'lookdog' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $dormant as $info ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $info['name'] ); ?></strong></td>
					<td><?php echo esc_html( $info['does'] ); ?></td>
					<td><?php echo esc_html( $info['back'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Where else to look', 'lookdog' ); ?></h2>
	<ul class="ld-links">
		<li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> &mdash; <?php esc_html_e( 'what people searched to find you, and which pages Google has indexed. Already verified for this site.', 'lookdog' ); ?></li>
		<li><a href="https://portals.aliexpress.com" target="_blank" rel="noopener">AliExpress Portals</a> &mdash; <?php esc_html_e( 'the only place that shows actual orders and commission. Clicks below are what left this site; whether they bought is only visible there.', 'lookdog' ); ?></li>
		<li><a href="https://analytics.google.com" target="_blank" rel="noopener">Google Analytics</a> &mdash; <?php esc_html_e( 'visitor numbers and traffic sources, once a Measurement ID is stored.', 'lookdog' ); ?></li>
	</ul>
</div>
<style>
.lookdog-stats .ld-cards{display:flex;flex-wrap:wrap;gap:16px;margin:20px 0 28px;}
.lookdog-stats .ld-card{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:18px 22px;min-width:180px;}
.lookdog-stats .ld-card__n{display:block;font-size:30px;font-weight:600;line-height:1.1;color:#14213D;font-variant-numeric:tabular-nums;}
.lookdog-stats .ld-card__l{display:block;margin-top:6px;font-size:13px;color:#646970;}
.lookdog-stats h2{margin-top:32px;}
.lookdog-stats table{max-width:900px;}
.lookdog-stats .ld-links{max-width:900px;}
.lookdog-stats .ld-links li{margin-bottom:8px;}
</style>
	<?php
}
