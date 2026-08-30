<?php
/**
 * LookDog - a phone dashboard at /dashboard/.
 *
 * wp-admin works on a phone in the sense that it loads. It is not something
 * anyone checks over a coffee: the menu eats the screen, the tables scroll
 * sideways, and the four numbers worth knowing are on four different screens.
 *
 * This is one screen, built for a thumb, that answers the only questions worth
 * asking daily: is anything broken, is anyone clicking, and what should I do
 * about it. It renders standalone rather than inside the admin theme, so it
 * loads in about a tenth of the markup, and it carries a web app manifest so
 * Android's "Add to Home screen" gives it an icon and opens it without browser
 * chrome.
 *
 * ACCESS. Administrators only, and never cached. A visitor is sent to the login
 * screen and returned here afterwards.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-dashboard.php
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		add_rewrite_rule( '^dashboard/?$', 'index.php?lookdog_dash=1', 'top' );
		add_rewrite_rule( '^dashboard/manifest\.json$', 'index.php?lookdog_dash=manifest', 'top' );
	}
);

add_filter(
	'query_vars',
	static function ( $vars ) {
		$vars[] = 'lookdog_dash';
		return $vars;
	}
);

/**
 * Percentage change between two numbers, or null when there is no basis for one.
 *
 * Returning null rather than 0 matters: "no change" and "nothing to compare
 * against" look identical as a number and mean completely different things on a
 * site that launched last week.
 *
 * @param int $now    Current period.
 * @param int $before Previous period.
 * @return ?int
 */
function lookdog_dash_delta( $now, $before ) {
	if ( $before <= 0 ) {
		return null;
	}
	return (int) round( ( ( $now - $before ) / $before ) * 100 );
}

/**
 * Clicks recorded on a given day.
 *
 * @param string $day Y-m-d.
 * @return int
 */
function lookdog_dash_clicks_on( $day ) {
	$rows = (array) get_option( 'lookdog_click_days', array() );
	return isset( $rows[ $day ] ) ? (int) $rows[ $day ] : 0;
}

/**
 * Clicks across a window that ended $offset days ago.
 *
 * @param int $days   Window length.
 * @param int $offset Days back the window ends.
 * @return int
 */
function lookdog_dash_clicks_window( $days, $offset = 0 ) {
	$rows  = (array) get_option( 'lookdog_click_days', array() );
	$total = 0;
	for ( $i = $offset; $i < $offset + $days; $i++ ) {
		$day    = gmdate( 'Y-m-d', time() - ( $i * DAY_IN_SECONDS ) );
		$total += isset( $rows[ $day ] ) ? (int) $rows[ $day ] : 0;
	}
	return $total;
}

/**
 * Everything the dashboard shows, gathered once.
 *
 * @return array<string,mixed>
 */
function lookdog_dash_data() {
	$gone   = function_exists( 'lookdog_unavailable_ids' ) ? lookdog_unavailable_ids() : array();
	$strike = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => '_lookdog_miss', 'compare' => 'EXISTS' ) ),
		)
	);

	$jobs = array();
	foreach ( array(
		'lookdog_price_watch' => array( 'Price check', 'lookdog_price_watch_report' ),
		'lookdog_link_check'  => array( 'Link check', 'lookdog_link_check_report' ),
	) as $hook => $meta ) {
		$next   = wp_next_scheduled( $hook );
		$report = get_option( $meta[1] );
		$jobs[] = array(
			'name'    => $meta[0],
			'next'    => $next,
			// More than two hours late means WP-Cron is not firing, which on a
			// quiet site is normal: it only runs when somebody visits.
			'overdue' => $next && $next < ( time() - 2 * HOUR_IN_SECONDS ),
			'last'    => is_array( $report ) && ! empty( $report['ran'] ) ? $report['ran'] : '',
		);
	}

	$cats = array();
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) ) as $t ) {
		$cats[] = array( 'name' => $t->name, 'count' => (int) $t->count, 'url' => get_term_link( $t ) );
	}
	usort( $cats, static function ( $a, $b ) { return $b['count'] <=> $a['count']; } );

	$go = (array) get_option( 'lookdog_go_stats', array() );
	uasort( $go, static function ( $a, $b ) { return (int) $b['total'] <=> (int) $a['total']; } );

	return array(
		'today'     => lookdog_dash_clicks_on( gmdate( 'Y-m-d' ) ),
		'yesterday' => lookdog_dash_clicks_on( gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ) ),
		'week'      => lookdog_dash_clicks_window( 7 ),
		'week_prev' => lookdog_dash_clicks_window( 7, 7 ),
		'month'     => lookdog_dash_clicks_window( 30 ),
		'all'       => array_sum( array_map( 'intval', (array) get_option( 'lookdog_click_days', array() ) ) ),
		'top'       => function_exists( 'lookdog_top_clicked' ) ? lookdog_top_clicked( 8 ) : array(),
		'gone'      => $gone,
		'strike'    => $strike,
		'jobs'      => $jobs,
		'cats'      => $cats,
		'products'  => (int) wp_count_posts( 'product' )->publish,
		'articles'  => (int) wp_count_posts( 'post' )->publish,
		'drops'     => function_exists( 'lookdog_price_drops' ) ? lookdog_price_drops( 5 ) : array(),
		'go'        => array_slice( $go, 0, 6, true ),
		'ga4'       => function_exists( 'lookdog_ga4_id' ) ? lookdog_ga4_id() : '',
	);
}

/**
 * Render the dashboard, or the manifest, and stop.
 *
 * @return void
 */
function lookdog_dash_render() {
	$mode = get_query_var( 'lookdog_dash' );
	if ( ! $mode ) {
		return;
	}

	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'lookdog dashboard' );
	header( 'X-Robots-Tag: noindex, nofollow', true );

	if ( 'manifest' === $mode ) {
		$icon = (int) get_option( 'site_icon' );
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode(
			array(
				'name'             => 'LookDog',
				'short_name'       => 'LookDog',
				'start_url'        => home_url( '/dashboard/' ),
				'scope'            => home_url( '/dashboard/' ),
				'display'          => 'standalone',
				'background_color' => '#14213D',
				'theme_color'      => '#14213D',
				'icons'            => $icon ? array(
					array( 'src' => wp_get_attachment_image_url( $icon, array( 192, 192 ) ), 'sizes' => '192x192', 'type' => 'image/png' ),
					array( 'src' => wp_get_attachment_image_url( $icon, 'full' ), 'sizes' => '512x512', 'type' => 'image/png' ),
				) : array(),
			)
		);
		exit;
	}

	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_safe_redirect( wp_login_url( home_url( '/dashboard/' ) ) );
		exit;
	}

	$d    = lookdog_dash_data();
	$icon = (int) get_option( 'site_icon' );
	// Anything the owner should actually do something about, counted for the
	// badge at the top. A dashboard whose first screen is decorative gets
	// checked twice and then never again.
	$todo = count( $d['gone'] ) + count( array_filter( $d['jobs'], static function ( $j ) { return $j['overdue']; } ) );
	if ( function_exists( 'lookdog_actions_open' ) ) {
		$todo += count( lookdog_actions_open() );
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#14213D">
<meta name="robots" content="noindex,nofollow">
<title>LookDog</title>
<link rel="manifest" href="<?php echo esc_url( home_url( '/dashboard/manifest.json' ) ); ?>">
<?php if ( $icon ) : ?>
	<link rel="apple-touch-icon" href="<?php echo esc_url( (string) wp_get_attachment_image_url( $icon, array( 192, 192 ) ) ); ?>">
	<link rel="icon" href="<?php echo esc_url( (string) wp_get_attachment_image_url( $icon, array( 192, 192 ) ) ); ?>">
<?php endif; ?>
<style>
*{box-sizing:border-box}
:root{
	--bg:#F4F4F1;--card:#FFFFFF;--ink:#14213D;--body:#3A3F4B;--muted:#6B7280;
	--line:#E4E4DE;--accent:#F97316;--good:#15803D;--warn:#B45309;--bad:#B91C1C;
}
@media (prefers-color-scheme:dark){
	:root{--bg:#0F1420;--card:#18202F;--ink:#F2F4F8;--body:#C6CCD8;--muted:#8B94A6;
	--line:#26303F;--good:#4ADE80;--warn:#FBBF24;--bad:#F87171;}
}
html,body{margin:0;padding:0}
body{background:var(--bg);color:var(--body);
	font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
	padding:0 0 40px;-webkit-font-smoothing:antialiased}
header{background:var(--ink);color:#fff;padding:calc(env(safe-area-inset-top) + 18px) 18px 18px;
	position:sticky;top:0;z-index:5}
header h1{margin:0;font-size:19px;letter-spacing:-.01em}
header .sub{margin:3px 0 0;font-size:13px;color:#A9B4C8}
.wrap{padding:16px 14px 0;max-width:640px;margin:0 auto}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;
	padding:16px;margin:0 0 14px}
.card h2{margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:.09em;
	text-transform:uppercase;color:var(--muted)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
.stat b{display:block;font-size:30px;line-height:1.05;color:var(--ink);
	font-variant-numeric:tabular-nums;letter-spacing:-.02em}
.stat span{display:block;margin-top:4px;font-size:12.5px;color:var(--muted)}
.delta{font-size:12.5px;font-weight:600;margin-left:6px}
.up{color:var(--good)}.down{color:var(--bad)}.flat{color:var(--muted)}
.row{display:flex;justify-content:space-between;align-items:center;gap:12px;
	padding:11px 0;border-bottom:1px solid var(--line);min-height:44px}
.row:last-child{border-bottom:0;padding-bottom:0}
.row:first-of-type{padding-top:0}
.row a{color:var(--ink);text-decoration:none;font-size:14.5px;line-height:1.35}
.row a:active{color:var(--accent)}
.num{flex:0 0 auto;font-variant-numeric:tabular-nums;font-size:13.5px;color:var(--muted);font-weight:600}
.alert{border-left:4px solid var(--warn);background:var(--card)}
.alert.bad{border-left-color:var(--bad)}
.alert p{margin:0 0 10px;font-size:14.5px;line-height:1.55}
.alert p:last-child{margin-bottom:0}
.ok{color:var(--good);font-weight:600}
.pill{display:inline-block;background:var(--bad);color:#fff;border-radius:20px;
	padding:1px 9px;font-size:12px;font-weight:700;margin-left:7px}
.links{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.links a{display:flex;align-items:center;justify-content:center;text-align:center;
	min-height:52px;padding:10px;border:1px solid var(--line);border-radius:12px;
	background:var(--card);color:var(--ink);text-decoration:none;font-size:14px;font-weight:600}
.links a:active{border-color:var(--accent);color:var(--accent)}
.foot{text-align:center;color:var(--muted);font-size:12px;padding:8px 0 0}
.muted{color:var(--muted);font-size:13.5px;margin:0}
</style>
</head>
<body>
<header>
	<h1>LookDog<?php if ( $todo ) : ?><span class="pill"><?php echo esc_html( (string) $todo ); ?></span><?php endif; ?></h1>
	<p class="sub"><?php echo esc_html( date_i18n( 'D j M, H:i' ) ); ?></p>
</header>
<div class="wrap">

<?php if ( $d['gone'] ) : ?>
	<div class="card alert bad">
		<h2>Replace these</h2>
		<p>The supplier has stopped listing <?php echo esc_html( (string) count( $d['gone'] ) ); ?> product<?php echo count( $d['gone'] ) === 1 ? '' : 's'; ?>. The buy button is already hidden.</p>
		<?php foreach ( array_slice( $d['gone'], 0, 6 ) as $gid ) : ?>
			<div class="row"><a href="<?php echo esc_url( (string) get_permalink( $gid ) ); ?>"><?php echo esc_html( get_the_title( $gid ) ); ?></a></div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php foreach ( $d['jobs'] as $j ) : ?>
	<?php if ( $j['overdue'] ) : ?>
		<div class="card alert">
			<p><strong><?php echo esc_html( $j['name'] ); ?> is overdue.</strong> WordPress only runs scheduled jobs when somebody visits the site, so on a quiet day they slip. Opening the site usually clears it.</p>
		</div>
	<?php endif; ?>
<?php endforeach; ?>

<?php
// The owner's own list, above the numbers. What needs doing outranks what
// happened. See lookdog-actions.php.
if ( function_exists( 'lookdog_actions_card' ) ) {
	lookdog_actions_card();
}
?>

<div class="grid">
	<div class="stat">
		<b><?php echo esc_html( number_format_i18n( $d['today'] ) ); ?></b>
		<span>Clicks today</span>
	</div>
	<div class="stat">
		<b><?php echo esc_html( number_format_i18n( $d['yesterday'] ) ); ?></b>
		<span>Yesterday</span>
	</div>
	<div class="stat">
		<b><?php echo esc_html( number_format_i18n( $d['week'] ) ); ?><?php
		$dl = lookdog_dash_delta( $d['week'], $d['week_prev'] );
		if ( null !== $dl ) {
			$cls = 0 === $dl ? 'flat' : ( $dl > 0 ? 'up' : 'down' );
			echo '<span class="delta ' . esc_attr( $cls ) . '">' . ( $dl > 0 ? '+' : '' ) . esc_html( (string) $dl ) . '%</span>';
		}
		?></b>
		<span>Last 7 days</span>
	</div>
	<div class="stat">
		<b><?php echo esc_html( number_format_i18n( $d['all'] ) ); ?></b>
		<span>All time</span>
	</div>
</div>

<?php if ( $d['top'] ) : ?>
	<div class="card">
		<h2>Most clicked</h2>
		<?php foreach ( $d['top'] as $t ) : ?>
			<div class="row">
				<a href="<?php echo esc_url( (string) get_permalink( $t['id'] ) ); ?>"><?php echo esc_html( $t['title'] ); ?></a>
				<span class="num"><?php echo esc_html( number_format_i18n( $t['clicks'] ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="card">
		<h2>Most clicked</h2>
		<p class="muted">No clicks recorded yet. Your own are never counted while you are logged in.</p>
	</div>
<?php endif; ?>

<?php if ( $d['drops'] ) : ?>
	<div class="card">
		<h2>Price drops &mdash; worth posting</h2>
		<?php foreach ( $d['drops'] as $dr ) : ?>
			<div class="row">
				<a href="<?php echo esc_url( (string) get_permalink( $dr['id'] ) ); ?>"><?php echo esc_html( get_the_title( $dr['id'] ) ); ?></a>
				<span class="num">$<?php echo esc_html( (string) $dr['from'] ); ?> &rarr; $<?php echo esc_html( (string) $dr['to'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php if ( $d['strike'] ) : ?>
	<div class="card">
		<h2>Watching <?php echo esc_html( (string) count( $d['strike'] ) ); ?></h2>
		<p class="muted">Missing from the supplier once or twice. Three misses in a row before anything is pulled.</p>
	</div>
<?php endif; ?>

<div class="card">
	<h2>Catalogue</h2>
	<?php foreach ( $d['cats'] as $c ) : ?>
		<div class="row">
			<a href="<?php echo esc_url( (string) $c['url'] ); ?>"><?php echo esc_html( $c['name'] ); ?></a>
			<span class="num"><?php echo esc_html( number_format_i18n( $c['count'] ) ); ?></span>
		</div>
	<?php endforeach; ?>
	<div class="row">
		<span style="font-size:14.5px">Products / articles</span>
		<span class="num"><?php echo esc_html( number_format_i18n( $d['products'] ) . ' / ' . number_format_i18n( $d['articles'] ) ); ?></span>
	</div>
</div>

<?php if ( $d['go'] ) : ?>
	<div class="card">
		<h2>Social short links</h2>
		<?php foreach ( $d['go'] as $slug => $row ) : ?>
			<div class="row">
				<a href="<?php echo esc_url( home_url( '/go/' . $slug ) ); ?>">/go/<?php echo esc_html( (string) $slug ); ?></a>
				<span class="num"><?php echo esc_html( number_format_i18n( (int) $row['total'] ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="card">
	<h2>Nightly jobs</h2>
	<?php foreach ( $d['jobs'] as $j ) : ?>
		<div class="row">
			<span style="font-size:14.5px"><?php echo esc_html( $j['name'] ); ?>
				<?php if ( ! $j['overdue'] ) : ?><span class="ok">&check;</span><?php endif; ?>
			</span>
			<span class="num"><?php echo $j['next'] ? esc_html( date_i18n( 'D H:i', $j['next'] ) ) : 'off'; ?></span>
		</div>
	<?php endforeach; ?>
	<?php if ( ! $d['ga4'] ) : ?>
		<div class="row"><span style="font-size:14.5px;color:var(--warn)">Google Analytics not connected</span></div>
	<?php endif; ?>
</div>

<div class="card">
	<h2>Go to</h2>
	<div class="links">
		<a href="https://analytics.google.com" target="_blank" rel="noopener">Analytics</a>
		<a href="https://search.google.com/search-console" target="_blank" rel="noopener">Search Console</a>
		<a href="https://portals.aliexpress.com" target="_blank" rel="noopener">Commission</a>
		<a href="<?php echo esc_url( admin_url() ); ?>">wp-admin</a>
	</div>
</div>

<p class="foot">Add to Home screen for a one-tap icon</p>
</div>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'lookdog_dash_render', 1 );
