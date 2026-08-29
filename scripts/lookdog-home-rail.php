<?php
/**
 * LookDog - homepage product rail.
 *
 * [lookdog_product_rail category="best-sellers" limit="10"]
 *
 * A horizontally scrolling rail rather than a grid, because the category grid
 * sits directly below it and two grids in a row is a repeated layout family.
 * Built on the active "LookDog Navy & Ember" design.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-home-rail.php
 */

function lookdog_product_rail( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'category' => 'best-sellers',
			'limit'    => '10',
			'heading'  => 'What people actually buy',
		),
		$atts,
		'lookdog_product_rail'
	);

	$term = get_term_by( 'slug', $atts['category'], 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	/**
	 * Ordered by recorded order count, descending.
	 *
	 * This used to have no `orderby` at all, so it fell back to date and showed
	 * the ten most recently imported Best Sellers under a heading reading "What
	 * people actually buy". The single best-selling product on the site, a
	 * cooling mat with about 24,900 orders, was not on the homepage. A heading
	 * that makes a claim has to be backed by the query underneath it.
	 *
	 * `meta_key` is used rather than a `meta_query` so products with no recorded
	 * count still appear, sorted last, rather than dropping off the rail
	 * entirely when the supplier API is rate limiting.
	 */
	$ids = get_posts(
		/**
		 * Filtered so lookdog-link-check.php can keep withdrawn products off a
		 * band whose whole claim is that these are the ones people buy.
		 */
		apply_filters(
			'lookdog_rail_query_args',
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => absint( $atts['limit'] ),
				'fields'         => 'ids',
				'meta_key'       => '_lookdog_orders', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => array(
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				),
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				),
			)
		)
	);

	if ( empty( $ids ) ) {
		return '';
	}

	$archive = get_term_link( $term );

	ob_start();
	?>
<section class="ld-band ld-band--tint">
	<div class="ld-wrap">
		<div class="ld-rail__head">
			<h2 class="ld-h2"><?php echo esc_html( $atts['heading'] ); ?></h2>
			<?php if ( ! is_wp_error( $archive ) ) : ?>
				<a class="ld-textlink" href="<?php echo esc_url( $archive ); ?>">See all <?php echo esc_html( (string) $term->count ); ?></a>
			<?php endif; ?>
		</div>
		<ul class="ld-rail">
			<?php
			foreach ( $ids as $pid ) :
				$thumb = get_post_thumbnail_id( $pid );
				$name  = get_the_title( $pid );
				?>
			<li class="ld-rail__item">
				<a class="ld-pcard" href="<?php echo esc_url( (string) get_permalink( $pid ) ); ?>">
					<span class="ld-pcard__media">
						<?php
						if ( $thumb ) {
							echo wp_get_attachment_image(
								$thumb,
								'woocommerce_thumbnail',
								false,
								array(
									'alt'     => $name,
									'loading' => 'lazy',
								)
							);
						}
						?>
					</span>
					<span class="ld-pcard__name"><?php echo esc_html( $name ); ?></span>
					<span class="ld-pcard__cta">See the write-up</span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_product_rail', 'lookdog_product_rail' );
