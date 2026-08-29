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

add_action(
	'template_redirect',
	static function () {
		$id = (int) get_query_var( 'lookdog_out' );
		if ( ! $id ) {
			return;
		}

		$dest = trim( (string) get_post_meta( $id, '_product_url', true ) );

		// Nothing to send them to: fall back to the product page rather than a
		// 404, so a missing meta value never becomes a dead buy button.
		if ( '' === $dest || ! wp_http_validate_url( $dest ) ) {
			$fallback = get_permalink( $id );
			wp_safe_redirect( $fallback ? $fallback : home_url( '/' ), 302 );
			exit;
		}

		// Owner clicks would otherwise inflate every number on the dashboard.
		if ( ! is_user_logged_in() ) {
			lookdog_click_count( $id );
		}

		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'lookdog affiliate redirect' );
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

	<div class="ld-cards">
		<div class="ld-card">
			<span class="ld-card__n"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
			<span class="ld-card__l"><?php esc_html_e( 'Affiliate clicks, all time', 'lookdog' ); ?></span>
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
