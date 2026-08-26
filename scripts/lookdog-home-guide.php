<?php
/**
 * LookDog - featured guide band and method band.
 *
 * [lookdog_featured_guide]  asymmetric editorial band for the latest guide
 * [lookdog_method]          how products are chosen, as plain columns
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-home-guide.php
 */

/**
 * Featured guide.
 *
 * An asymmetric editorial split on a navy ground, anchored by a real pull quote
 * from the article rather than a stock photo. The site's guides carry the
 * arguments that product pages cannot, so the homepage should send people to
 * one. Deliberately the only asymmetric band on the page: dials put variance at
 * 0.45, which means composed by default with asymmetry used once, on purpose.
 */
function lookdog_featured_guide( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'post_id' => '0',
			'quote'   => 'Thirteen grams of dark chocolate is a genuine emergency for a 4kg dog. For a 35kg dog it is very likely nothing.',
			'kicker'  => 'From the guides',
		),
		$atts,
		'lookdog_featured_guide'
	);

	$pid = absint( $atts['post_id'] );
	if ( ! $pid ) {
		$latest = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$pid = $latest ? (int) $latest[0] : 0;
	}
	if ( ! $pid ) {
		return '';
	}

	$title   = get_the_title( $pid );
	$link    = (string) get_permalink( $pid );
	$words   = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $pid ) ) );
	$excerpt = wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $pid ) );
	$excerpt = trim( str_replace( 'Affiliate notice: LookDog may earn a commission if you purchase through this link, at no additional cost to you.', '', $excerpt ) );
	$excerpt = wp_trim_words( $excerpt, 34, '&hellip;' );

	ob_start();
	?>
<section class="ld-band ld-band--ink">
	<div class="ld-wrap ld-guide">
		<figure class="ld-guide__quote">
			<blockquote><?php echo esc_html( $atts['quote'] ); ?></blockquote>
			<figcaption><?php echo esc_html( number_format_i18n( $words ) ); ?> words, free to read</figcaption>
		</figure>
		<div class="ld-guide__body">
			<p class="ld-guide__kicker"><?php echo esc_html( $atts['kicker'] ); ?></p>
			<h2 class="ld-guide__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( '' !== $excerpt ) : ?>
				<p class="ld-guide__copy"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<a class="ld-btn" href="<?php echo esc_url( $link ); ?>">Read the guide</a>
		</div>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_featured_guide', 'lookdog_featured_guide' );

/**
 * How products are chosen.
 *
 * Replaces four identical emoji feature boxes. Plain columns separated by a
 * hairline rule rather than cards: the design bans rows of identical feature
 * cards, and none of these are click targets, so a card would be false
 * elevation. Counts are read live so the copy cannot drift from the catalogue.
 */
function lookdog_method() {
	$products = wp_count_posts( 'product' );
	$products = isset( $products->publish ) ? (int) $products->publish : 0;
	$cats     = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'fields'     => 'ids',
			'exclude'    => array( get_option( 'default_product_cat' ) ),
		)
	);
	$cats = is_wp_error( $cats ) ? 0 : count( $cats );

	$points = array(
		array(
			'stat'  => number_format_i18n( $products ),
			'label' => 'products listed',
			'copy'  => 'Every one clears a minimum customer rating before it is listed. Nothing goes up because it pays better.',
		),
		array(
			'stat'  => number_format_i18n( $cats ),
			'label' => 'categories',
			'copy'  => 'Chosen for distinct types rather than near-duplicates, so a category is a real range and not the same item eight times.',
		),
		array(
			'stat'  => 'Both',
			'label' => 'sides of it',
			'copy'  => 'Each listing says what the product does badly as well as what it does well, including when that costs us the sale.',
		),
	);

	ob_start();
	?>
<section class="ld-band">
	<div class="ld-wrap">
		<h2 class="ld-h2 ld-h2--lead">How things get on this site</h2>
		<div class="ld-method">
			<?php foreach ( $points as $p ) : ?>
				<div class="ld-method__col">
					<p class="ld-method__stat"><?php echo esc_html( $p['stat'] ); ?><span><?php echo esc_html( $p['label'] ); ?></span></p>
					<p class="ld-method__copy"><?php echo esc_html( $p['copy'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_method', 'lookdog_method' );
