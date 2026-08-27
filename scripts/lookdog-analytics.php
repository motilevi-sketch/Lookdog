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
 */
add_action( 'wp_footer', static function () {
	if ( ! lookdog_ga4_id() || ! is_singular( 'product' ) || is_user_logged_in() ) {
		return;
	}

	$post_id = get_the_ID();
	$terms   = wp_get_object_terms( $post_id, 'product_cat', array( 'fields' => 'names' ) );
	$data    = array(
		'item_id'       => (string) $post_id,
		'item_name'     => get_the_title( $post_id ),
		'item_category' => is_wp_error( $terms ) ? '' : implode( ', ', $terms ),
		'ae_id'         => (string) get_post_meta( $post_id, '_lookdog_ae_id', true ),
	);
	?>
<script>
(function(){
  var d = <?php echo wp_json_encode( $data ); ?>;
  document.addEventListener('click', function(e){
    var a = e.target.closest('a');
    if (!a || !a.href) return;
    if (a.href.indexOf('aliexpress') === -1 && a.href.indexOf('s.click') === -1) return;
    if (typeof gtag !== 'function') return;
    gtag('event', 'affiliate_click', {
      item_id: d.item_id,
      item_name: d.item_name,
      item_category: d.item_category,
      ae_id: d.ae_id,
      link_url: a.href,
      transport_type: 'beacon'
    });
  }, true);
})();
</script>
	<?php
}, 20 );
