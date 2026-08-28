<?php
/**
 * LookDog - homepage hero.
 *
 * [lookdog_hero]
 *
 * Replaces a cover block whose headline was "Smart Accessories for Happy Dogs"
 * above "Discover useful toys, travel gear, feeding essentials and smart
 * products selected to make life better for dogs and their owners." Both
 * sentences could sit on any pet site ever built. They said nothing about what
 * this one does differently, which is the only thing worth putting in a hero:
 * every listing here writes down what a product does badly, and the ranking is
 * not for sale.
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
	$trust[] = 'drawbacks written on every listing';
	$trust[] = 'we say where the commission comes from';

	ob_start();
	?>
<section class="ld-hero">
	<?php if ( $src ) : ?>
		<img class="ld-hero__bg" src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $atts['alt'] ); ?>" loading="eager" fetchpriority="high" />
	<?php endif; ?>
	<div class="ld-hero__scrim" aria-hidden="true"></div>
	<div class="ld-wrap ld-hero__inner">
		<h1 class="ld-hero__title">Dog gear, with the drawbacks written down</h1>
		<p class="ld-hero__copy">Toys, beds, travel kit, feeding gear, grooming tools and trackers, with a buying guide behind each one. Every listing says what the product does badly as well as what it does well &mdash; including when that costs us the sale.</p>
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
