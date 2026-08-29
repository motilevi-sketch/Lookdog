<?php
/**
 * LookDog - Google Analytics 4, with affiliate click tracking.
 *
 * Page views alone are close to useless on an affiliate site. The only action
 * that can earn anything is a click on "Check Price on AliExpress", and that
 * click leaves the site, so nothing records it unless we do. This sends an
 * `affiliate_click` event carrying the product, its category and the AliExpress
 * item id, which is what turns GA4 from a visitor counter into a report on
 * which products actually convert attention into outbound clicks.
 *
 * Set the ID to switch it on:
 *   update_option( 'lookdog_ga4_id', 'G-XXXXXXXXXX' );
 * With no ID stored, nothing is printed at all.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-analytics.php
 */

defined( 'ABSPATH' ) || exit;

function lookdog_ga4_id() {
	return trim( (string) get_option( 'lookdog_ga4_id', '' ) );
}

/**
 * Consent Mode v2 defaults.
 *
 * Shipped as granted, which is what "install GA4" normally means and what makes
 * the reports work on day one. If this site serves EU or UK visitors it needs a
 * consent banner, and this filter is where that banner hooks in: return
 * 'denied' until the visitor has agreed, then call gtag('consent','update').
 */
function lookdog_ga4_consent_defaults() {
	return apply_filters( 'lookdog_ga4_consent_defaults', array(
		'ad_storage'             => 'granted',
		'ad_user_data'           => 'granted',
		'ad_personalization'     => 'granted',
		'analytics_storage'      => 'granted',
	) );
}

add_action( 'wp_head', static function () {
	$id = lookdog_ga4_id();
	if ( ! $id || is_admin() || is_user_logged_in() ) {
		return; // never count the owner's own browsing
	}
	$consent = wp_json_encode( lookdog_ga4_consent_defaults() );
	?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $id ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent','default',<?php echo $consent; // phpcs:ignore ?>);
gtag('js', new Date());
gtag('config', '<?php echo esc_js( $id ); ?>');
</script>
	<?php
}, 5 );

/**
 * Outbound affiliate click tracking.
 *
 * Bound on the document rather than on each button, so it survives any markup
 * WooCommerce or the theme changes. The click is not delayed or intercepted:
 * GA4 sends the event over the beacon transport, which survives the page
 * unloading, so there is no reason to hold the visitor up.
 *
 * TWO SHAPES OF BUTTON, because lookdog-stats.php now routes every buy button
 * through /out/{id} to count the click server side:
 *
 *   - On archives and cards the button is <a href="/out/123">.
 *   - On a single product it is a submit button inside
 *     <form class="cart" action="/out/123" method="get">, which is not a link
 *     at all and never fires a click handler bound to anchors.
 *
 * An earlier version matched only anchors whose href contained "aliexpress".
 * After cloaking, no such URL appears in the page and that listener matched
 * nothing anywhere on the site. Both shapes are handled here, and the raw
 * aliexpress match is kept so the event still fires if the cloaking is ever
 * removed.
 *
 * The server-side counter in lookdog-stats.php already knows which products are
 * clicked. What it cannot know is where the visitor came from, which is the
 * whole reason this event is still worth sending: GA4 joins the click to the
 * session's source, so "which products does TikTok traffic actually click"
 * becomes answerable.
 */
add_action( 'wp_footer', static function () {
	if ( ! lookdog_ga4_id() || is_user_logged_in() ) {
		return;
	}

	// Anywhere a buy button can appear, not just single product pages.
	$shop_view = is_singular( 'product' )
		|| ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) )
		|| is_front_page()
		|| is_search();
	if ( ! $shop_view ) {
		return;
	}

	$data = array( 'item_id' => '', 'item_name' => '', 'item_category' => '', 'ae_id' => '' );
	if ( is_singular( 'product' ) ) {
		$post_id = get_the_ID();
		$terms   = wp_get_object_terms( $post_id, 'product_cat', array( 'fields' => 'names' ) );
		// Term names and titles are stored with HTML entities ("Beds &amp;
		// Comfort"). These values go into a JSON payload, not into markup, so
		// the entity has to be decoded or the GA4 report shows the escape.
		$decode  = static function ( $text ) {
			return html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
		};
		$data    = array(
			'item_id'       => (string) $post_id,
			'item_name'     => $decode( get_the_title( $post_id ) ),
			'item_category' => is_wp_error( $terms ) ? '' : $decode( implode( ', ', $terms ) ),
			'ae_id'         => (string) get_post_meta( $post_id, '_lookdog_ae_id', true ),
		);
	}
	?>
<script>
(function(){
  var d = <?php echo wp_json_encode( $data ); ?>;
  function isOutbound(url){
    return !!url && (url.indexOf('/out/') !== -1 || url.indexOf('aliexpress') !== -1 || url.indexOf('s.click') !== -1);
  }
  function send(url){
    if (typeof gtag !== 'function') return;
    // On an archive the page knows no single product, so read the id back out
    // of the URL rather than sending an empty item_id.
    var m = url.match(/\/out\/(\d+)/);
    gtag('event', 'affiliate_click', {
      item_id: d.item_id || (m ? m[1] : ''),
      item_name: d.item_name,
      item_category: d.item_category,
      ae_id: d.ae_id,
      link_url: url,
      transport_type: 'beacon'
    });
  }
  document.addEventListener('click', function(e){
    var a = e.target.closest && e.target.closest('a');
    if (a && isOutbound(a.href)) send(a.href);
  }, true);
  // The single-product buy button submits a form; it is not an anchor.
  document.addEventListener('submit', function(e){
    var f = e.target;
    if (f && f.action && isOutbound(f.action)) send(f.action);
  }, true);
})();
</script>
	<?php
}, 20 );
