<?php
/**
 * LookDog - the two shop sidebar widgets that replaced the two empty ones.
 *
 * The shop sidebar carried "Filter by price" and "Top rated products". Neither
 * had ever rendered a single pixel, and neither ever could: every product is an
 * external affiliate listing, so none of them has a `_price` for WooCommerce to
 * build a range from, and with no reviews on the site there is nothing for a
 * top-rated list to rank. Both widgets self-suppress when they have no data, so
 * the failure was silent - the sidebar just looked short.
 *
 * These two are built on data the site actually holds: the ten problem tags,
 * and the AliExpress order counts stored on 162 of the 167 products.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-shop-sidebar.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * The taxonomy term the current shop view is scoped to, if any.
 *
 * lookdog-category-search.php works this out already, including the awkward
 * case of a search results page where the term is a query var rather than the
 * queried object. Fall back to the queried object if that file is ever removed.
 *
 * @return ?WP_Term
 */
function lookdog_sidebar_scope_term() {
	if ( function_exists( 'lookdog_search_scope' ) ) {
		$scope = lookdog_search_scope();
		return $scope['term'];
	}
	$term = get_queried_object();
	return $term instanceof WP_Term ? $term : null;
}

/**
 * Shared widget styles. Printed by whichever widget renders first.
 *
 * @return void
 */
function lookdog_sidebar_styles() {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	?>
<style id="lookdog-sidebar-css">
.ld-side{list-style:none;margin:0;padding:0;}
.ld-side li{margin:0;}
.ld-side__link{
	display:flex;
	gap:10px;
	align-items:baseline;
	justify-content:space-between;
	padding:7px 0;
	color:#14213D;
	text-decoration:none;
	font-size:14.5px;
	line-height:1.4;
}
.ld-side__link:hover,
.ld-side__link:focus{color:#F97316;}
.ld-side li.is-current .ld-side__link{font-weight:700;color:#14213D;}
.ld-side__count{
	flex:0 0 auto;
	color:#8A8F9B;
	font-size:12.5px;
	font-variant-numeric:tabular-nums;
}
.ld-side__more{
	display:inline-block;
	margin-top:12px;
	font-size:13px;
	font-weight:600;
	color:#14213D;
	text-decoration:underline;
}
.ld-side__more:hover{color:#F97316;}

.ld-top{list-style:none;margin:0;padding:0;}
.ld-top li{margin:0;border-bottom:1px solid #E6E6E1;}
.ld-top li:last-child{border-bottom:0;}
.ld-top__link{
	display:grid;
	grid-template-columns:52px minmax(0,1fr);
	gap:12px;
	align-items:center;
	padding:11px 0;
	text-decoration:none;
}
.ld-top__link:focus-visible{outline:3px solid #F97316;outline-offset:-3px;}
.ld-top__media{
	width:52px;
	height:52px;
	border-radius:6px;
	overflow:hidden;
	background:#EFEFEC;
}
.ld-top__media img{width:100%;height:100%;object-fit:cover;display:block;}
.ld-top__name{
	display:block;
	color:#14213D;
	font-size:14px;
	line-height:1.35;
}
.ld-top__link:hover .ld-top__name{color:#F97316;}
.ld-top__orders{
	display:block;
	margin-top:3px;
	color:#8A8F9B;
	font-size:12.5px;
	font-variant-numeric:tabular-nums;
}
</style>
	<?php
}

/**
 * "Shop by problem" - the ten problem tags, in their curated order.
 */
class LookDog_Problems_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'lookdog_problems',
			__( 'LookDog: Shop by problem', 'lookdog' ),
			array( 'description' => __( 'The ten problem tags, in their curated order, with the current one marked.', 'lookdog' ) )
		);
	}

	public function widget( $args, $instance ) {
		if ( ! function_exists( 'lookdog_problem_terms' ) ) {
			return;
		}
		$terms = lookdog_problem_terms();
		if ( ! $terms ) {
			return;
		}

		$current = lookdog_sidebar_scope_term();
		$title   = isset( $instance['title'] ) && '' !== $instance['title']
			? $instance['title']
			: __( 'Shop by problem', 'lookdog' );

		lookdog_sidebar_styles();

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<ul class="ld-side">';
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$is_current = $current instanceof WP_Term && $current->term_id === $term->term_id;
			printf(
				'<li class="%1$s"><a class="ld-side__link" href="%2$s"%3$s><span>%4$s</span><span class="ld-side__count">%5$s</span></a></li>',
				$is_current ? 'is-current' : '',
				esc_url( $link ),
				$is_current ? ' aria-current="page"' : '',
				esc_html( $term->name ),
				esc_html( number_format_i18n( $term->count ) )
			);
		}
		echo '</ul>';

		$hub = get_page_by_path( 'shop-by-problem' );
		if ( $hub ) {
			printf(
				'<a class="ld-side__more" href="%s">%s</a>',
				esc_url( (string) get_permalink( $hub ) ),
				esc_html__( 'All ten problems', 'lookdog' )
			);
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		printf(
			'<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" placeholder="%5$s" /></p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'lookdog' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title ),
			esc_attr__( 'Shop by problem', 'lookdog' )
		);
	}

	public function update( $new_instance, $old_instance ) {
		return array( 'title' => sanitize_text_field( (string) $new_instance['title'] ) );
	}
}

/**
 * "Most ordered" - ranked by the AliExpress order count already stored on each
 * product, and narrowed to the category or problem you are currently looking at
 * so it is not the same five products on every page.
 */
class LookDog_Ordered_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'lookdog_ordered',
			__( 'LookDog: Most ordered', 'lookdog' ),
			array( 'description' => __( 'Top products by AliExpress order count, scoped to the category being viewed.', 'lookdog' ) )
		);
	}

	public function widget( $args, $instance ) {
		$limit = isset( $instance['limit'] ) ? max( 1, (int) $instance['limit'] ) : 5;
		$term  = lookdog_sidebar_scope_term();

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'meta_key'            => '_lookdog_orders',
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( $term instanceof WP_Term ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			);
		}

		$query = new WP_Query( $query_args );
		// Never render an empty box - that is the failure this widget replaced.
		if ( ! $query->have_posts() ) {
			return;
		}

		if ( isset( $instance['title'] ) && '' !== $instance['title'] ) {
			$title = $instance['title'];
		} elseif ( $term instanceof WP_Term ) {
			// A category is a place ("in Grooming"); a problem is a purpose
			// ("for Pulling on the lead"). The wrong preposition reads as broken.
			if ( 'product_tag' === $term->taxonomy ) {
				/* translators: %s: problem name. */
				$title = sprintf( __( 'Most ordered for %s', 'lookdog' ), $term->name );
			} else {
				/* translators: %s: category name. */
				$title = sprintf( __( 'Most ordered in %s', 'lookdog' ), $term->name );
			}
		} else {
			$title = __( 'Most ordered', 'lookdog' );
		}

		lookdog_sidebar_styles();

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<ul class="ld-top">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$orders = (int) get_post_meta( get_the_ID(), '_lookdog_orders', true );
			?>
			<li>
				<a class="ld-top__link" href="<?php the_permalink(); ?>">
					<span class="ld-top__media">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail(
								'woocommerce_gallery_thumbnail',
								array(
									'alt'     => the_title_attribute( array( 'echo' => false ) ),
									'loading' => 'lazy',
								)
							);
						}
						?>
					</span>
					<span>
						<span class="ld-top__name"><?php the_title(); ?></span>
						<?php if ( $orders > 0 ) : ?>
							<span class="ld-top__orders">
								<?php
								printf(
									/* translators: %s: formatted order count. */
									esc_html( _n( '%s order on AliExpress', '%s orders on AliExpress', $orders, 'lookdog' ) ),
									esc_html( number_format_i18n( $orders ) )
								);
								?>
							</span>
						<?php endif; ?>
					</span>
				</a>
			</li>
			<?php
		}
		echo '</ul>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_reset_postdata();
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$limit = isset( $instance['limit'] ) ? (int) $instance['limit'] : 5;
		printf(
			'<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" placeholder="%5$s" /></p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'lookdog' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title ),
			esc_attr__( 'Most ordered', 'lookdog' )
		);
		printf(
			'<p><label for="%1$s">%2$s</label><input class="tiny-text" id="%1$s" name="%3$s" type="number" min="1" max="10" step="1" value="%4$d" /></p>',
			esc_attr( $this->get_field_id( 'limit' ) ),
			esc_html__( 'Products to show:', 'lookdog' ),
			esc_attr( $this->get_field_name( 'limit' ) ),
			(int) $limit
		);
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( (string) $new_instance['title'] ),
			'limit' => min( 10, max( 1, (int) $new_instance['limit'] ) ),
		);
	}
}

add_action(
	'widgets_init',
	static function () {
		register_widget( 'LookDog_Problems_Widget' );
		register_widget( 'LookDog_Ordered_Widget' );
	}
);
