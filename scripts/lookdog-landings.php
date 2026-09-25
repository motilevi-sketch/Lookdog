<?php
/**
 * LookDog - where visitors arrive from.
 *
 * WHY THIS EXISTS. The owner started answering questions in Facebook groups and
 * had no way to tell whether a single person followed a reply to the site. The
 * click log records the buy button, and its referrer is always a page on this
 * site, so it says nothing about how the reader got here. Google Analytics is
 * not connected. This answers one question only: which outside site sent each
 * visit, and which page it landed on.
 *
 * WHY A BEACON AND NOT SERVER-SIDE. Every public page is served from the
 * LiteSpeed cache, and a cached page never reaches PHP. A counter in
 * template_redirect would count the handful of cache misses and miss everyone
 * else. So the cached page carries a few lines of script that report the
 * landing to admin-ajax.php, which is never cached.
 *
 * WHAT IS COUNTED. One count per arrival from outside: a page whose referrer is
 * another site, or none. Moving between pages here is not an arrival, and a
 * reload or a back button is not a new one. The source comes from, in order:
 * utm_source (the /go/ short links set it), Facebook's fbclid or Google's gclid
 * on the URL, then the referring host.
 *
 * WHAT IS NOT. No cookie, nothing written to the visitor's device, no address
 * and no user agent stored - only a day, a source and a path, added to a
 * running total. That is why it needs no consent banner and why it can only
 * ever answer "how many", never "who".
 *
 * HOW FAR TO TRUST IT. A named source (Facebook, Instagram, Google) needs a
 * real referrer or tagged link, so those counts are close to people. "Direct"
 * is every arrival with no referrer at all, which includes apps that strip it,
 * bookmarks, typed addresses - and any bot that runs JavaScript. Read it as a
 * ceiling, not an audience.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-landings.php
 */

defined( 'ABSPATH' ) || exit;

/** Readable names for the sources, in the order the dashboard lists them. */
function lookdog_land_labels() {
	return array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'google'    => 'Google',
		'search'    => 'Other search engines',
		'ai'        => 'AI assistants',
		'pinterest' => 'Pinterest',
		'tiktok'    => 'TikTok',
		'other'     => 'Other sites',
		'direct'    => 'Direct / no referrer',
	);
}

/**
 * Decide the source of one arrival.
 *
 * @param string $host Referring host, lowercase, may be ''.
 * @param string $utm  utm_source value, may be ''.
 * @param bool   $fb   URL carried fbclid.
 * @param bool   $g    URL carried gclid.
 * @return string Key from lookdog_land_labels().
 */
function lookdog_land_classify( $host, $utm, $fb, $g ) {
	$utm = strtolower( trim( $utm ) );
	if ( '' !== $utm ) {
		if ( preg_match( '~^(facebook|fb)~', $utm ) ) {
			return 'facebook';
		}
		if ( preg_match( '~^(instagram|ig)~', $utm ) ) {
			return 'instagram';
		}
		if ( 0 === strpos( $utm, 'google' ) ) {
			return 'google';
		}
		return 'other';
	}
	if ( $fb ) {
		return 'facebook';
	}
	if ( $g ) {
		return 'google';
	}
	if ( '' === $host ) {
		return 'direct';
	}

	$map = array(
		'facebook'  => '~(^|\.)(facebook\.com|fb\.com|fb\.me|messenger\.com)$~',
		'instagram' => '~(^|\.)instagram\.com$~',
		'google'    => '~(^|\.)google\.[a-z.]+$|^com\.google\.android~',
		'search'    => '~(^|\.)(bing\.com|duckduckgo\.com|yahoo\.com|yandex\.[a-z]+|ecosia\.org|brave\.com)$~',
		'ai'        => '~(^|\.)(chatgpt\.com|openai\.com|perplexity\.ai|claude\.ai|gemini\.google\.com|copilot\.microsoft\.com)$~',
		'pinterest' => '~(^|\.)pinterest\.[a-z.]+$|^pin\.it$~',
		'tiktok'    => '~(^|\.)tiktok\.com$~',
	);
	// AI assistants first: gemini.google.com would otherwise read as Google.
	if ( preg_match( $map['ai'], $host ) ) {
		return 'ai';
	}
	foreach ( $map as $key => $re ) {
		if ( 'ai' !== $key && preg_match( $re, $host ) ) {
			return $key;
		}
	}
	return 'other';
}

/**
 * Add one arrival to the running totals.
 *
 * @param string $source Key from lookdog_land_labels().
 * @param string $path   Landing path.
 * @param string $host   Referring host, kept only for 'other'.
 * @return void
 */
function lookdog_land_record( $source, $path, $host ) {
	$day = gmdate( 'Y-m-d' );

	if ( ! get_option( 'lookdog_land_since' ) ) {
		update_option( 'lookdog_land_since', $day, false );
	}

	$days = (array) get_option( 'lookdog_land_days', array() );
	$days[ $day ][ $source ] = ( isset( $days[ $day ][ $source ] ) ? (int) $days[ $day ][ $source ] : 0 ) + 1;
	if ( count( $days ) > 120 ) {
		ksort( $days );
		$days = array_slice( $days, -120, null, true );
	}
	update_option( 'lookdog_land_days', $days, false );

	// Which pages each source sends people to. Capped per source so the option
	// cannot grow without limit; the busiest paths survive the trim.
	$pages = (array) get_option( 'lookdog_land_pages', array() );
	$pages[ $source ][ $path ] = ( isset( $pages[ $source ][ $path ] ) ? (int) $pages[ $source ][ $path ] : 0 ) + 1;
	if ( count( $pages[ $source ] ) > 50 ) {
		arsort( $pages[ $source ] );
		$pages[ $source ] = array_slice( $pages[ $source ], 0, 50, true );
	}
	update_option( 'lookdog_land_pages', $pages, false );

	if ( 'other' === $source && '' !== $host ) {
		$other          = (array) get_option( 'lookdog_land_other', array() );
		$other[ $host ] = ( isset( $other[ $host ] ) ? (int) $other[ $host ] : 0 ) + 1;
		if ( count( $other ) > 50 ) {
			arsort( $other );
			$other = array_slice( $other, 0, 50, true );
		}
		update_option( 'lookdog_land_other', $other, false );
	}
}

/**
 * The beacon's receiving end.
 *
 * Public by necessity, since it is called from cached pages, so it trusts
 * nothing it is sent: the source is recomputed here from a validated host, the
 * path must look like a path, and a request that does not come from a page on
 * this site is dropped.
 */
function lookdog_land_receive() {
	status_header( 204 );

	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
	if ( function_exists( 'lookdog_click_is_bot' ) && lookdog_click_is_bot( $ua ) ) {
		exit;
	}
	if ( function_exists( 'lookdog_click_looks_spoofed' ) && lookdog_click_looks_spoofed( $ua ) ) {
		exit;
	}

	// The beacon is sent by a page on this site, so it carries this site as
	// its referrer. Anything else is somebody posting at the endpoint directly.
	$site = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$from = isset( $_SERVER['HTTP_REFERER'] ) ? strtolower( (string) wp_parse_url( wp_unslash( $_SERVER['HTTP_REFERER'] ), PHP_URL_HOST ) ) : '';
	if ( preg_replace( '~^www\.~', '', $from ) !== preg_replace( '~^www\.~', '', $site ) ) {
		exit;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- a public counter on cached pages; see above.
	$host = isset( $_POST['r'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['r'] ) ) ) : '';
	$utm  = isset( $_POST['u'] ) ? sanitize_key( wp_unslash( $_POST['u'] ) ) : '';
	$fb   = ! empty( $_POST['f'] );
	$g    = ! empty( $_POST['g'] );
	$path = isset( $_POST['p'] ) ? (string) wp_unslash( $_POST['p'] ) : '/';
	// phpcs:enable

	if ( '' !== $host && ! preg_match( '~^[a-z0-9.-]{1,120}$~', $host ) ) {
		exit;
	}
	// Our own host is navigation inside the site, not an arrival. The script
	// already skips it; this is the second lock.
	if ( '' !== $host && preg_replace( '~^www\.~', '', $host ) === preg_replace( '~^www\.~', '', $site ) ) {
		exit;
	}
	if ( ! preg_match( '~^/[A-Za-z0-9/_\-.%]{0,200}$~', $path ) ) {
		$path = '/';
	}
	// Pages that are not content: the dashboard and anything under wp-.
	if ( preg_match( '~^/(dashboard|wp-)~', $path ) ) {
		exit;
	}

	lookdog_land_record( lookdog_land_classify( $host, $utm, $fb, $g ), $path, $host );
	exit;
}
add_action( 'wp_ajax_nopriv_lookdog_land', 'lookdog_land_receive' );
// Logged-in visitors are the owner. Answer, count nothing.
add_action(
	'wp_ajax_lookdog_land',
	static function () {
		status_header( 204 );
		exit;
	}
);

/** The script on every public page. Static, so it is safe inside the cache. */
add_action(
	'wp_footer',
	static function () {
		if ( is_admin() || is_user_logged_in() || is_feed() ) {
			return;
		}
		$url = admin_url( 'admin-ajax.php' );
		?>
<script id="lookdog-land">
(function(){try{
var n=performance.getEntriesByType&&performance.getEntriesByType('navigation')[0];
if(n&&(n.type==='reload'||n.type==='back_forward'))return;
var h='';if(document.referrer){try{h=new URL(document.referrer).hostname.toLowerCase()}catch(e){}}
var me=location.hostname.replace(/^www\./,'');if(h&&h.replace(/^www\./,'')===me)return;
var q=new URLSearchParams(location.search),d=new FormData();
d.append('action','lookdog_land');d.append('r',h);d.append('u',(q.get('utm_source')||'').slice(0,40));
d.append('f',q.has('fbclid')?'1':'');d.append('g',q.has('gclid')?'1':'');d.append('p',location.pathname.slice(0,200));
var u=<?php echo wp_json_encode( $url ); ?>;
if(navigator.sendBeacon){navigator.sendBeacon(u,d)}else{fetch(u,{method:'POST',body:d,keepalive:true})}
}catch(e){}})();
</script>
		<?php
	},
	99
);

/**
 * Arrivals per source over the last $days days, busiest first.
 *
 * @param int $days Window.
 * @return array<string,int>
 */
function lookdog_land_summary( $days = 7 ) {
	$rows = (array) get_option( 'lookdog_land_days', array() );
	$out  = array();
	for ( $i = 0; $i < $days; $i++ ) {
		$day = gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
		foreach ( isset( $rows[ $day ] ) ? (array) $rows[ $day ] : array() as $src => $n ) {
			$out[ $src ] = ( isset( $out[ $src ] ) ? $out[ $src ] : 0 ) + (int) $n;
		}
	}
	arsort( $out );
	return $out;
}

/** Dashboard card. Uses the dashboard's own classes. */
function lookdog_land_card() {
	$since  = (string) get_option( 'lookdog_land_since', '' );
	$sum    = lookdog_land_summary( 7 );
	$labels = lookdog_land_labels();
	$pages  = (array) get_option( 'lookdog_land_pages', array() );

	// Named sources first, busiest first; "direct" always last, because it is
	// the least trustworthy row and should not lead the card.
	$direct = isset( $sum['direct'] ) ? (int) $sum['direct'] : 0;
	unset( $sum['direct'] );
	?>
<div class="card">
	<h2>Where visitors came from &middot; 7 days</h2>
	<?php if ( ! $sum && ! $direct ) : ?>
		<p class="muted">
			<?php
			echo '' !== $since
				? esc_html( 'Nothing counted yet. Counting since ' . date_i18n( 'j M', strtotime( $since ) ) . '.' )
				: esc_html( 'Counting starts with the next visit from outside the site.' );
			?>
		</p>
	<?php else : ?>
		<?php foreach ( $sum as $src => $n ) : ?>
			<div class="row">
				<span style="font-size:14.5px"><?php echo esc_html( isset( $labels[ $src ] ) ? $labels[ $src ] : $src ); ?></span>
				<span class="num"><?php echo esc_html( number_format_i18n( $n ) ); ?></span>
			</div>
		<?php endforeach; ?>
		<?php if ( $direct ) : ?>
			<div class="row">
				<span style="font-size:14.5px;color:var(--muted)"><?php echo esc_html( $labels['direct'] ); ?></span>
				<span class="num"><?php echo esc_html( number_format_i18n( $direct ) ); ?></span>
			</div>
		<?php endif; ?>

		<?php foreach ( array( 'facebook', 'instagram', 'google' ) as $src ) : ?>
			<?php
			if ( empty( $pages[ $src ] ) ) {
				continue;
			}
			$top = (array) $pages[ $src ];
			arsort( $top );
			?>
			<p class="muted" style="margin:14px 0 4px"><?php echo esc_html( $labels[ $src ] . ' sent people to (all time):' ); ?></p>
			<?php foreach ( array_slice( $top, 0, 3, true ) as $path => $n ) : ?>
				<div class="row">
					<a href="<?php echo esc_url( home_url( $path ) ); ?>"><?php echo esc_html( $path ); ?></a>
					<span class="num"><?php echo esc_html( number_format_i18n( (int) $n ) ); ?></span>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>

		<p class="muted" style="margin-top:12px">
			<?php echo esc_html( 'No cookies, nothing stored about the visitor. "Direct" includes apps that hide where they came from, and any bot that runs scripts, so treat it as a ceiling.' . ( '' !== $since ? ' Counting since ' . date_i18n( 'j M', strtotime( $since ) ) . '.' : '' ) ); ?>
		</p>
	<?php endif; ?>
</div>
	<?php
}
