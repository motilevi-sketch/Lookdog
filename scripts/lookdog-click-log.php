<?php
/**
 * LookDog - who is clicking the affiliate links.
 *
 * WHY THIS EXISTS
 * Between 29 August and 2 September 2026 the daily click count went 4, 25, 45,
 * 52 - on a site with almost no traffic - and those clicks were spread across
 * 118 different products with never more than five on any one, at every hour of
 * the day and night. Real visitors do not behave like that: human traffic
 * concentrates on a few products and leaves most of the catalogue untouched. A
 * flat, catalogue-wide spread is what something walking the site looks like.
 *
 * That matters more than a tidy dashboard. AliExpress penalised this account on
 * 31 August under a scheme that explicitly targets fraudulent click
 * performance, and 31 August is the day the count first jumped. Whether the two
 * are connected cannot be established by staring at totals, so this records
 * what is doing the clicking.
 *
 * WHAT IS STORED, AND WHAT IS NOT
 * The user agent, the referring host, the product, and the time. The IP address
 * is NOT stored: it is salted and hashed to twelve characters, which is enough
 * to tell "one source clicking two hundred times" from "two hundred people
 * clicking once" and not enough to identify anybody. The log is capped at 400
 * entries and rolls, so it is a diagnostic window rather than a permanent
 * record of visitors.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-click-log.php
 */

defined( 'ABSPATH' ) || exit;

/** How many clicks to keep. Enough to see a pattern, small enough for one row. */
const LOOKDOG_CLICK_LOG_MAX = 400;

/**
 * Does this user agent belong to something automated?
 *
 * Deliberately generous. A false "automated" on an unusual browser is a
 * question worth asking; a false "browser" on a crawler is the thing that would
 * hide the answer we are looking for.
 *
 * @param string $ua User agent string.
 * @return bool
 */
function lookdog_click_is_bot( $ua ) {
	if ( '' === trim( $ua ) ) {
		return true; // A real browser always sends one.
	}
	return (bool) preg_match(
		'~bot|crawl|spider|slurp|scrape|scrapy|curl|wget|python|java/|go-http|okhttp|axios|node-fetch|libwww|httpclient|headless|phantom|puppeteer|playwright|selenium|lighthouse|preview|monitor|uptime|claude|anthropic|gptbot|openai|perplexity|ccbot~i',
		$ua
	);
}

/**
 * Does the request contradict the browser its user agent claims to be?
 *
 * DELIBERATELY SEPARATE FROM lookdog_click_is_bot(), and never used to block
 * anything. That function gates the affiliate redirect: a false positive there
 * costs a real buyer their click-through and the site its commission, so it
 * stays conservative and matches only agents that admit what they are. This one
 * only labels a row in the log, so it can afford to be suspicious.
 *
 * WHY IT EXISTS. Of 400 logged clicks, 94 passed the bot check - and 87 of those
 * were desktop, 60 of them reporting the identical Chrome-on-Mac string, one
 * click each from 85 different addresses, spread evenly through the night. That
 * is not what an audience looks like. It is also not something a user-agent
 * blocklist can catch, because the string being sent is a perfectly ordinary
 * one.
 *
 * So this tests the claim instead of the string. Chrome has sent Sec-CH-UA on
 * every secure request since version 89; a request whose user agent says Chrome
 * but which sends no such header is not Chrome. Accept-Language is the same kind
 * of test - every real browser sends one.
 *
 * Neither is proof. A privacy extension or a corporate proxy can strip headers,
 * which is exactly why this labels and never blocks.
 *
 * @param string $ua User agent string.
 * @return bool
 */
function lookdog_click_looks_spoofed( $ua ) {
	if ( ! preg_match( '~Chrome/(\d+)~i', $ua, $m ) || (int) $m[1] < 89 ) {
		return false; // Only Chromium makes a claim this can be checked against.
	}
	if ( ! isset( $_SERVER['HTTP_SEC_CH_UA'] ) ) {
		return true;
	}
	if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
		return true;
	}
	return false;
}

/**
 * Record one click. Hooked to the action lookdog-stats.php fires.
 *
 * @param int $post_id Product clicked.
 * @return void
 */
function lookdog_click_log_record( $post_id ) {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '';
	$rf = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), PHP_URL_HOST ) : '';
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	$log   = (array) get_option( 'lookdog_click_log', array() );
	$log[] = array(
		't'   => time(),
		'p'   => (int) $post_id,
		'ua'  => $ua,
		'ref' => $rf,
		// Salted so it cannot be turned back into an address, stable so repeat
		// visits from one source group together.
		'src' => substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 12 ),
		'bot' => lookdog_click_is_bot( $ua ) ? 1 : 0,
		// Labels only. See lookdog_click_looks_spoofed(): the affiliate redirect
		// is never withheld on the strength of this.
		'sus' => lookdog_click_looks_spoofed( $ua ) ? 1 : 0,
		// The headers the verdict was based on, so a row can be re-read later
		// rather than taken on trust. c = Sec-CH-UA, l = Accept-Language,
		// f = Sec-Fetch-Site.
		'h'   => ( isset( $_SERVER['HTTP_SEC_CH_UA'] ) ? 'c' : '-' )
			. ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? 'l' : '-' )
			. ( isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ? 'f' : '-' ),
	);

	if ( count( $log ) > LOOKDOG_CLICK_LOG_MAX ) {
		$log = array_slice( $log, -LOOKDOG_CLICK_LOG_MAX );
	}
	update_option( 'lookdog_click_log', $log, false );
}
add_action( 'lookdog_click', 'lookdog_click_log_record' );

/**
 * The log, grouped by user agent, newest activity first.
 *
 * @param int $days How far back to look.
 * @return array<int,array<string,mixed>>
 */
function lookdog_click_agents( $days = 7 ) {
	$from = time() - ( (int) $days * DAY_IN_SECONDS );
	$rows = array();

	foreach ( (array) get_option( 'lookdog_click_log', array() ) as $e ) {
		if ( empty( $e['t'] ) || $e['t'] < $from ) {
			continue;
		}
		$key = $e['ua'];
		if ( ! isset( $rows[ $key ] ) ) {
			$rows[ $key ] = array(
				'ua'       => $key,
				'bot'      => ! empty( $e['bot'] ),
				'sus'      => 0,
				'headers'  => array(),
				'clicks'   => 0,
				'products' => array(),
				'sources'  => array(),
				'refs'     => array(),
				'last'     => 0,
			);
		}
		$rows[ $key ]['clicks']++;
		if ( ! empty( $e['sus'] ) ) {
			$rows[ $key ]['sus']++;
		}
		if ( ! empty( $e['h'] ) ) {
			$rows[ $key ]['headers'][ $e['h'] ] = true;
		}
		$rows[ $key ]['products'][ (int) $e['p'] ]  = true;
		$rows[ $key ]['sources'][ $e['src'] ?? '' ] = true;
		if ( ! empty( $e['ref'] ) ) {
			$rows[ $key ]['refs'][ $e['ref'] ] = true;
		}
		$rows[ $key ]['last'] = max( $rows[ $key ]['last'], (int) $e['t'] );
	}

	foreach ( $rows as &$r ) {
		$r['products'] = count( $r['products'] );
		$r['sources']  = count( $r['sources'] );
		$r['refs']     = array_keys( $r['refs'] );
		$r['headers']  = array_keys( $r['headers'] );
	}
	unset( $r );

	usort(
		$rows,
		static function ( $a, $b ) {
			return $b['clicks'] <=> $a['clicks'];
		}
	);
	return $rows;
}

/**
 * Dashboard card. Uses the dashboard's own classes; only the badge is new.
 */
function lookdog_click_log_card() {
	$agents = lookdog_click_agents( 7 );
	$total  = 0;
	$bots   = 0;
	$sus    = 0;
	foreach ( $agents as $a ) {
		$total += $a['clicks'];
		if ( $a['bot'] ) {
			$bots += $a['clicks'];
		} else {
			$sus += (int) $a['sus'];
		}
	}
	?>
<style>
.ualist{margin:0;padding:0;list-style:none}
.ua{padding:12px 0;border-bottom:1px solid var(--line)}
.ua:last-child{border-bottom:0}
.ua__top{display:flex;align-items:center;justify-content:space-between;gap:12px}
.ua__n{font-size:15px;font-weight:700;color:var(--ink);font-variant-numeric:tabular-nums}
.ua__tag{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
	border-radius:20px;padding:3px 9px}
.ua__tag--bot{background:var(--bad);color:#fff}
.ua__tag--human{background:var(--good);color:#fff}
.ua__tag--sus{background:var(--warn,#B45309);color:#fff}
.ua__ua{display:block;margin-top:6px;font-size:12px;line-height:1.45;color:var(--body);
	word-break:break-all}
.ua__meta{display:block;margin-top:4px;font-size:12px;color:var(--muted)}
</style>
<div class="card<?php echo $bots ? ' alert bad' : ''; ?>">
	<h2>Who is clicking<?php if ( $bots ) : ?><span class="pill"><?php echo esc_html( (string) $bots ); ?></span><?php endif; ?></h2>

	<?php if ( ! $agents ) : ?>
		<p class="muted">Nothing logged yet. This fills up as people &mdash; or things &mdash; press the buy button.</p>
	<?php else : ?>
		<p class="muted" style="margin-bottom:12px">
			<?php
			printf(
				/* translators: 1: total clicks, 2: automated clicks. */
				esc_html__( '%1$s clicks in seven days, %2$s of them from something automated.', 'lookdog' ),
				esc_html( (string) $total ),
				esc_html( (string) $bots )
			);
			?>
		</p>
		<?php if ( $sus ) : ?>
			<p class="muted" style="margin:-6px 0 12px">
				<?php
				printf(
					/* translators: %s: clicks that passed the bot check but contradict their own user agent. */
					esc_html__( 'A further %s passed the bot check but sent none of the headers the browser they claim to be always sends. They still reached the affiliate link - this is a label, not a block.', 'lookdog' ),
					esc_html( (string) $sus )
				);
				?>
			</p>
		<?php endif; ?>
		<ul class="ualist">
			<?php foreach ( array_slice( $agents, 0, 8 ) as $a ) : ?>
				<li class="ua">
					<span class="ua__top">
						<span class="ua__n"><?php echo esc_html( (string) $a['clicks'] ); ?> clicks</span>
						<?php
						if ( $a['bot'] ) {
							$tag_class = 'ua__tag--bot';
							$tag_text  = __( 'automated', 'lookdog' );
						} elseif ( ! empty( $a['sus'] ) ) {
							$tag_class = 'ua__tag--sus';
							$tag_text  = __( 'claims a browser', 'lookdog' );
						} else {
							$tag_class = 'ua__tag--human';
							$tag_text  = __( 'browser', 'lookdog' );
						}
						?>
						<span class="ua__tag <?php echo esc_attr( $tag_class ); ?>">
							<?php echo esc_html( $tag_text ); ?>
						</span>
					</span>
					<span class="ua__ua"><?php echo esc_html( '' !== $a['ua'] ? $a['ua'] : 'no user agent sent' ); ?></span>
					<span class="ua__meta">
						<?php
						printf(
							/* translators: 1: distinct products, 2: distinct sources, 3: time ago. */
							esc_html__( '%1$s products &middot; %2$s source(s) &middot; last %3$s ago', 'lookdog' ),
							esc_html( (string) $a['products'] ),
							esc_html( (string) $a['sources'] ),
							esc_html( human_time_diff( $a['last'] ) )
						);
						if ( $a['refs'] ) {
							echo ' &middot; ' . esc_html( implode( ', ', array_slice( $a['refs'], 0, 3 ) ) );
						}
						if ( ! empty( $a['headers'] ) ) {
							/* translators: %s: which of the Sec-CH-UA, Accept-Language and Sec-Fetch-Site headers arrived. */
							echo ' &middot; ' . esc_html( sprintf( __( 'headers %s', 'lookdog' ), implode( '/', $a['headers'] ) ) );
						}
						?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
	<?php
}
