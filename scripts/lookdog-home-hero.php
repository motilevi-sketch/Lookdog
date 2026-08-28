<?php
/**
 * LookDog - homepage hero.
 *
 * [lookdog_hero]
 *
 * Replaces a cover block whose headline was "Smart Accessories for Happy Dogs"
 * above "Discover useful toys, travel gear, feeding essentials and smart
 * products selected to make life better for dogs and their owners." Both
 * sentences could sit on any pet site ever built.
 *
 * The replacement, "Dog gear, with the drawbacks written down", led on the
 * site's editorial promise instead. The owner removed it on 28 August 2026,
 * along with the band that demonstrated it, so the hero now says plainly what
 * is sold. Their call, and the reasoning is recorded rather than argued: a
 * headline the owner will not defend is worse than a plain one.
 *
 * The trust line under the buttons is a single sentence rather than a row of
 * stat columns, deliberately. The "How things get on this site" band lower down
 * already uses big-number columns, and the design brief forbids repeating a
 * layout family down the page.
 *
 * Numbers are read live, so the hero cannot drift from the catalogue the way
 * hardcoded copy does.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-home-hero.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Background image.
 *
 * Attachment 3194 is the photograph the cover block used. Kept as an attribute
 * so it can be changed without editing this file.
 */
function lookdog_hero( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'image_id' => '3194',
			'alt'      => 'A Labrador lying on the floor surrounded by dog toys',
		),
		$atts,
		'lookdog_hero'
	);

	$src = wp_get_attachment_image_url( absint( $atts['image_id'] ), 'full' );

	$products = wp_count_posts( 'product' );
	$products = isset( $products->publish ) ? (int) $products->publish : 0;
	$floor    = function_exists( 'lookdog_rating_floor' ) ? lookdog_rating_floor() : '';

	// Each clause is something the site can be held to, in the order a sceptical
	// reader would ask for it: how much is here, how it was filtered, what the
	// listings admit, and who pays us.
	$trust = array();
	if ( $products ) {
		$trust[] = number_format_i18n( $products ) . ' products';
	}
	$trust[] = $floor
		? 'nothing under 80% seller feedback, lowest is ' . $floor . '%'
		: 'nothing under 80% seller feedback';
	$trust[] = 'a buying guide behind every category';
	$trust[] = 'we say where the commission comes from';

	ob_start();
	?>
<section class="ld-hero">
	<?php if ( $src ) : ?>
		<img class="ld-hero__bg" src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $atts['alt'] ); ?>" loading="eager" fetchpriority="high" />
	<?php endif; ?>
	<div class="ld-hero__scrim" aria-hidden="true"></div>
	<div class="ld-wrap ld-hero__inner">
		<h1 class="ld-hero__title">Dog toys, beds, travel gear and trackers</h1>
		<p class="ld-hero__copy">Six ranges, each with a buying guide behind it, covering toys, beds, travel kit, feeding gear, grooming tools and trackers. Everything links out to AliExpress, and we earn a commission if you buy through one.</p>
		<div class="ld-hero__actions">
			<a class="ld-pill" href="<?php echo esc_url( home_url( '/store/' ) ); ?>">Browse all products</a>
			<a class="ld-pill ld-pill--ghost" href="#categories">Choose a category</a>
		</div>
		<p class="ld-hero__trust"><?php echo esc_html( implode( ' · ', $trust ) ); ?></p>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_hero', 'lookdog_hero' );
