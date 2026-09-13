<?php
/**
 * LookDog - crop the supplier clutter out of product thumbnails.
 *
 * Many AliExpress source photos carry a vendor logo, a "2026 NEW" ribbon or a
 * size-chart callout in a corner, meant to catch the eye scrolling a foreign
 * marketplace. Sitting on our own cards next to product photography we do
 * control, that clutter is what makes a card read as scraped rather than
 * curated.
 *
 * These are the small thumbnail crops only - the woocommerce_thumbnail size
 * used on the homepage rail, the shop and category grids, and related /
 * up-sell blocks. object-fit: cover already crops to fill the box; this adds
 * a modest zoom so the crop eats into the frame's edges, where that clutter
 * almost always sits, instead of showing the source photo untouched. It is a
 * crop, not a fix for every photo: a banner that spans the middle of a photo
 * is not solved by this and needs the photo replaced at the source, which is
 * done case by case as they turn up.
 *
 * The single product page's own gallery is untouched - full and large sizes
 * are not matched by this selector - because a shopper who clicks through
 * deserves to see the whole photo before deciding.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-product-thumbs.php
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function () {
		?>
<style id="lookdog-product-thumbs-css">
.astra-shop-thumbnail-wrap{overflow:hidden;}
.attachment-woocommerce_thumbnail{transform:scale(1.18);transform-origin:center;}
</style>
		<?php
	},
	5
);
