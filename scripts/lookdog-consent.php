<?php
/**
 * LookDog - GDPR cookie consent for EEA, UK and Swiss visitors.
 *
 * Google Analytics sets cookies and sends data to Google. Under GDPR and the UK
 * PECR that needs consent BEFORE it happens, not after, which is why this works
 * through Consent Mode rather than by blocking the tag: gtag loads either way,
 * but until consent arrives it stores nothing and sends only cookieless pings.
 *
 * WHY REGION-SCOPED RATHER THAN DENIED FOR EVERYONE
 * Hostinger passes no country header, so this server genuinely cannot tell
 * where a visitor is. Consent Mode's own `region` key can: Google evaluates it
 * against the visitor's location itself. So the defaults are declared twice -
 * denied for the EEA, UK and Switzerland, granted for everywhere else - and
 * Google applies whichever matches. A visitor in Ohio keeps full analytics
 * without ever seeing a banner; a visitor in Dublin is denied until they say
 * otherwise, whatever this server believes about them.
 *
 * WHICH VISITORS SEE THE BANNER
 * The banner is client side because every page here is served from a static
 * LiteSpeed cache and a server-rendered decision would be cached and shown to
 * the wrong person. The browser's own IANA timezone is the signal.
 *
 * Both failure directions are safe. A European whose browser reports a New York
 * timezone sees no banner and stays denied by Google's region rule - data lost,
 * no breach. An American whose browser reports Europe/London sees a banner that
 * was not legally required - mildly annoying, no harm. What cannot happen is a
 * European being tracked without consent, because that call is Google's and not
 * this heuristic's.
 *
 * Set `lookdog_consent_scope` to 'all' to show the banner to every visitor and
 * deny by default worldwide.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-consent.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * EEA + UK + Switzerland.
 *
 * Switzerland is not in the EEA, but its revised Federal Act on Data Protection
 * imposes materially the same requirement, so it is treated the same way here.
 *
 * @return string[]
 */
function lookdog_consent_regions() {
	return array(
		// EU 27.
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
		'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
		'SI', 'ES', 'SE',
		// Rest of the EEA.
		'IS', 'LI', 'NO',
		// United Kingdom, and Switzerland under the revised FADP.
		'GB', 'CH',
	);
}

/** Whether the banner applies worldwide or only to the regions above. */
function lookdog_consent_scope() {
	return 'all' === get_option( 'lookdog_consent_scope', 'eu' ) ? 'all' : 'eu';
}

/**
 * Region-scoped Consent Mode v2 defaults.
 *
 * `wait_for_update` holds events for a moment so a visitor who clicks Accept
 * immediately is not recorded as denied. `ads_data_redaction` and
 * `url_passthrough` are what keep measurement partially working while consent
 * is withheld, without cookies.
 *
 * @param array $calls Existing default calls.
 * @return array
 */
function lookdog_consent_calls( $calls ) {
	$denied = array(
		'ad_storage'         => 'denied',
		'ad_user_data'       => 'denied',
		'ad_personalization' => 'denied',
		'analytics_storage'  => 'denied',
		'wait_for_update'    => 500,
	);

	if ( 'all' === lookdog_consent_scope() ) {
		return array( $denied );
	}

	// Order matters: Google applies the most specific matching default, so the
	// unscoped granted call must follow the region-scoped denied one.
	$regional           = $denied;
	$regional['region'] = lookdog_consent_regions();

	return array_merge( array( $regional ), $calls );
}
add_filter( 'lookdog_ga4_consent_calls', 'lookdog_consent_calls' );

/**
 * The privacy policy URL, preferring WordPress's own setting.
 *
 * @return string
 */
function lookdog_consent_privacy_url() {
	$id = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return (string) get_permalink( $id );
	}
	$page = get_page_by_path( 'privacy-policy' );
	return $page ? (string) get_permalink( $page ) : '';
}

/**
 * Reopen link, for the footer and for anywhere else it is wanted.
 *
 * A visitor must be able to withdraw consent as easily as they gave it, which
 * means a permanent way back to the banner - not a one-time popup.
 */
function lookdog_consent_link_shortcode( $atts = array() ) {
	$atts = shortcode_atts( array( 'text' => __( 'Cookie settings', 'lookdog' ) ), $atts, 'lookdog_cookie_settings' );
	return '<a href="#" class="ld-consent-open" data-ld-consent-open>' . esc_html( $atts['text'] ) . '</a>';
}
add_shortcode( 'lookdog_cookie_settings', 'lookdog_consent_link_shortcode' );

/**
 * Put the reopen link in the footer helpful-links menu.
 *
 * @param string   $items Menu HTML.
 * @param stdClass $args  Menu arguments.
 * @return string
 */
function lookdog_consent_menu_link( $items, $args ) {
	$menu = isset( $args->menu ) ? $args->menu : null;
	$id   = is_object( $menu ) ? (int) $menu->term_id : (int) $menu;
	if ( 61 !== $id ) {
		return $items;
	}
	return $items . '<li class="menu-item ld-consent-item"><a href="#" class="menu-link ld-consent-open" data-ld-consent-open>'
		. esc_html__( 'Cookie settings', 'lookdog' ) . '</a></li>';
}
add_filter( 'wp_nav_menu_items', 'lookdog_consent_menu_link', 10, 2 );

/**
 * The banner.
 *
 * Rendered on every page and hidden by default; the script decides whether to
 * show it. That is deliberate - the page is served from a static cache, so any
 * decision made in PHP would be baked in and served to the next visitor too.
 *
 * @return void
 */
function lookdog_consent_banner() {
	if ( ! function_exists( 'lookdog_ga4_id' ) || ! lookdog_ga4_id() || is_user_logged_in() ) {
		return;
	}

	$privacy = lookdog_consent_privacy_url();
	$regions = wp_json_encode( lookdog_consent_regions() );
	$scope   = lookdog_consent_scope();
	?>
<div class="ld-consent" id="ld-consent" role="dialog" aria-modal="false"
	aria-labelledby="ld-consent-title" aria-describedby="ld-consent-text" hidden>
	<div class="ld-consent__inner">
		<div class="ld-consent__copy">
			<p class="ld-consent__title" id="ld-consent-title"><?php esc_html_e( 'Can we measure how this site is used?', 'lookdog' ); ?></p>
			<p class="ld-consent__text" id="ld-consent-text">
				<?php esc_html_e( 'We use Google Analytics to see which pages and products people actually use. It sets cookies and sends data to Google. Say no and everything here works exactly the same.', 'lookdog' ); ?>
				<?php if ( $privacy ) : ?>
					<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', 'lookdog' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<div class="ld-consent__actions">
			<button type="button" class="ld-consent__btn ld-consent__btn--no" data-ld-consent="deny"><?php esc_html_e( 'Reject', 'lookdog' ); ?></button>
			<button type="button" class="ld-consent__btn ld-consent__btn--yes" data-ld-consent="allow"><?php esc_html_e( 'Accept', 'lookdog' ); ?></button>
		</div>
	</div>
</div>
<style id="lookdog-consent-css">
.ld-consent{position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#14213D;color:#F4F4F1;
	box-shadow:0 -2px 18px rgba(0,0,0,.28);}
.ld-consent[hidden]{display:none!important;}
.ld-consent__inner{max-width:1180px;margin:0 auto;padding:18px 22px;display:flex;gap:24px;
	align-items:center;justify-content:space-between;flex-wrap:wrap;}
.ld-consent__copy{flex:1 1 420px;min-width:0;}
.ld-consent__title{margin:0 0 6px;font-size:15px;font-weight:700;color:#FFFFFF;line-height:1.3;}
.ld-consent__text{margin:0;font-size:13.5px;line-height:1.55;color:#D6D9E0;}
.ld-consent__text a{color:#FFFFFF;text-decoration:underline;}
.ld-consent__text a:hover{color:#F97316;}
.ld-consent__actions{flex:0 0 auto;display:flex;gap:12px;}
/*
 * Both buttons are the same size, weight and font. Regulators have repeatedly
 * ruled that making "reject" quieter than "accept" invalidates the consent, so
 * neither is allowed to be the smaller or greyer option.
 */
.ld-consent__btn{
	min-width:132px;
	margin:0;
	padding:12px 24px;
	font-size:14px;
	font-weight:600;
	font-family:inherit;
	line-height:1.3;
	border-radius:6px;
	border:2px solid #FFFFFF;
	cursor:pointer;
}
.ld-consent__btn--yes{background:#FFFFFF;color:#14213D;}
.ld-consent__btn--no{background:transparent;color:#FFFFFF;}
.ld-consent__btn--yes:hover{background:#F97316;border-color:#F97316;color:#FFFFFF;}
.ld-consent__btn--no:hover{background:rgba(255,255,255,.14);}
.ld-consent__btn:focus-visible{outline:3px solid #F97316;outline-offset:2px;}
@media (max-width:640px){
	.ld-consent__inner{padding:16px;gap:14px;}
	.ld-consent__actions{width:100%;}
	.ld-consent__btn{flex:1 1 0;min-width:0;}
}
</style>
<script>
(function(){
	var KEY     = 'ld_consent_v1';
	var MAXAGE  = 365 * 24 * 60 * 60 * 1000; // re-ask after a year
	var SCOPE   = <?php echo wp_json_encode( $scope ); ?>;
	var REGIONS = <?php echo $regions; // phpcs:ignore ?>;
	var el      = document.getElementById('ld-consent');
	if (!el) return;

	function read(){
		try {
			var raw = localStorage.getItem(KEY);
			if (!raw) return null;
			var v = JSON.parse(raw);
			if (!v || !v.t || (Date.now() - v.t) > MAXAGE) return null;
			return v;
		} catch (e) { return null; }
	}

	function write(choice){
		try { localStorage.setItem(KEY, JSON.stringify({ c: choice, t: Date.now() })); } catch (e) {}
	}

	function apply(choice){
		if (typeof gtag !== 'function') return;
		var state = choice === 'allow' ? 'granted' : 'denied';
		gtag('consent', 'update', {
			ad_storage: state,
			ad_user_data: state,
			ad_personalization: state,
			analytics_storage: state
		});
	}

	// Timezone is the only location signal available in a cached page. Europe/*
	// covers the EEA and the UK; the Atlantic and Asia zones below are the
	// outlying territories of member states that do not sit under Europe/.
	function looksEuropean(){
		try {
			var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
			if (tz.indexOf('Europe/') === 0) return true;
			return [
				'Atlantic/Canary', 'Atlantic/Madeira', 'Atlantic/Azores',
				'Atlantic/Reykjavik', 'Atlantic/Faroe', 'Asia/Nicosia',
				'Asia/Famagusta', 'Indian/Reunion', 'America/Cayenne',
				'America/Martinique', 'America/Guadeloupe'
			].indexOf(tz) !== -1;
		} catch (e) {
			// No timezone API: assume the banner is needed rather than assume it is not.
			return true;
		}
	}

	function show(){ el.hidden = false; }
	function hide(){ el.hidden = true; }

	el.addEventListener('click', function(e){
		var btn = e.target.closest('[data-ld-consent]');
		if (!btn) return;
		var choice = btn.getAttribute('data-ld-consent');
		write(choice);
		apply(choice);
		hide();
	});

	// Reopening must be as easy as the original choice, so the footer link
	// clears the stored answer and brings the banner back.
	document.addEventListener('click', function(e){
		var open = e.target.closest('[data-ld-consent-open]');
		if (!open) return;
		e.preventDefault();
		show();
		el.scrollIntoView({ block: 'nearest' });
	});

	var stored = read();
	if (stored) {
		apply(stored.c);
	} else if (SCOPE === 'all' || looksEuropean()) {
		show();
	}
	// Outside the regions with no stored answer: no banner, and the unscoped
	// granted default already applies. Nothing to do.
})();
</script>
	<?php
}
add_action( 'wp_footer', 'lookdog_consent_banner', 25 );
