<?php
/**
 * LookDog - dynamic product category grid.
 *
 * Replaces the hand-written HTML block that used to sit on the homepage. That
 * version hardcoded six <img> tags and six blurbs, so a new category never
 * appeared and a changed image never showed. It also used flex-wrap with
 * justify-content:center, which orphaned and centred the last card whenever the
 * card count did not divide evenly across the row.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-category-grid.php
 *
 * Usage: [lookdog_category_grid]
 *   exclude   comma-separated slugs to omit (default: uncategorized)
 *   order     comma-separated slugs to force to the front
 *   min       minimum card width in px before the grid rewraps (default 280)
 *
 * Card blurb comes from the term meta `lookdog_card_blurb`, deliberately NOT
 * the term description: the description is reserved for the longer category
 * copy that belongs on the archive page for SEO, and that would be far too
 * long to sit inside a card.
 */

function lookdog_category_grid( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'exclude' => 'uncategorized',
			'order'   => 'best-sellers,dog-toys,travel-gear,feeding-care,grooming,smart-accessories,beds-comfort',
			'min'     => '280',
		),
		$atts,
		'lookdog_category_grid'
	);

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$exclude = array_filter( array_map( 'trim', explode( ',', $atts['exclude'] ) ) );
	$terms   = array_values(
		array_filter(
			$terms,
			static function ( $t ) use ( $exclude ) {
				return ! in_array( $t->slug, $exclude, true );
			}
		)
	);

	// Explicit ordering first, anything new falls in alphabetically after it.
	$order = array_values( array_filter( array_map( 'trim', explode( ',', $atts['order'] ) ) ) );
	usort(
		$terms,
		static function ( $a, $b ) use ( $order ) {
			$ia = array_search( $a->slug, $order, true );
			$ib = array_search( $b->slug, $order, true );
			$ia = ( false === $ia ) ? PHP_INT_MAX : $ia;
			$ib = ( false === $ib ) ? PHP_INT_MAX : $ib;
			if ( $ia === $ib ) {
				return strcasecmp( $a->name, $b->name );
			}
			return $ia <=> $ib;
		}
	);

	$min = absint( $atts['min'] ) ?: 280;

	ob_start();
	?>
<div class="lookdog-catgrid" style="--ld-min:<?php echo esc_attr( $min ); ?>px;">
	<?php foreach ( $terms as $term ) : ?>
		<?php
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$blurb = (string) get_term_meta( $term->term_id, 'lookdog_card_blurb', true );
		if ( '' === $blurb ) {
			$blurb = wp_trim_words( wp_strip_all_tags( $term->description ), 16, '&hellip;' );
		}
		$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$name     = $term->name;
		?>
	<div class="lookdog-catgrid__card">
		<a class="lookdog-catgrid__media" href="<?php echo esc_url( $link ); ?>" aria-hidden="true" tabindex="-1">
			<?php
			if ( $thumb_id ) {
				// The wrapping link is aria-hidden, so this alt is for image search
				// rather than screen readers.
				//
				// This used to pass 'title' => '' to stop a junk tooltip appearing,
				// with a comment blaming wp_get_attachment_image for falling back to
				// the attachment filename. That was wrong: the function adds no title
				// at all. SureRank's Image_Seo was rewriting the rendered HTML and
				// building titles from the page title, which is why every card here
				// read "Home". It is switched off in lookdog-image-attrs.php, so no
				// title argument is needed.
				echo wp_get_attachment_image(
					$thumb_id,
					'woocommerce_thumbnail',
					false,
					array(
						'alt'     => $name,
						'loading' => 'lazy',
					)
				);
			}
			?>
		</a>
		<div class="lookdog-catgrid__body">
			<h3 class="lookdog-catgrid__title"><?php echo esc_html( $name ); ?></h3>
			<?php if ( '' !== $blurb ) : ?>
				<p class="lookdog-catgrid__copy"><?php echo esc_html( $blurb ); ?></p>
			<?php endif; ?>
			<div>
				<a class="lookdog-catgrid__cta" href="<?php echo esc_url( $link ); ?>">
					<?php
					/* translators: %s: product category name. */
					echo esc_html( sprintf( __( 'Explore %s', 'lookdog' ), $name ) );
					?>
				</a>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_category_grid', 'lookdog_category_grid' );

/**
 * Grid styles. Printed once, only on pages that use the shortcode.
 *
 * @return void
 */
function lookdog_category_grid_styles() {
	global $post;
	if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, 'lookdog_category_grid' ) ) {
		return;
	}
	?>
<style id="lookdog-catgrid-css">
.lookdog-catgrid{
	display:grid;
	grid-template-columns:repeat(auto-fit,minmax(var(--ld-min,280px),1fr));
	gap:26px;
	align-items:stretch;
}
.lookdog-catgrid__card{
	background:#FFFFFF;
	border:1px solid #E6E6E1;
	border-radius:10px;
	overflow:hidden;
	display:flex;
	flex-direction:column;
	box-shadow:0 1px 3px rgba(20,33,61,.06);
	transition:transform .18s ease, box-shadow .18s ease;
}
.lookdog-catgrid__card:hover{
	transform:translateY(-2px);
	box-shadow:0 6px 18px rgba(20,33,61,.10);
}
.lookdog-catgrid__media{
	display:block;
	height:170px;
	overflow:hidden;
	background:#F1F1EE;
}
.lookdog-catgrid__media img{
	width:100%;
	height:100%;
	object-fit:cover;
	object-position:center;
	display:block;
}
.lookdog-catgrid__body{
	padding:24px 22px 26px;
	text-align:center;
	display:flex;
	flex-direction:column;
	flex:1;
}
.lookdog-catgrid__title{
	color:#14213D;
	font-size:20px;
	margin:0 0 10px;
}
.lookdog-catgrid__copy{
	color:#5A5F6B;
	font-size:14px;
	line-height:1.55;
	margin:0 0 20px;
	flex:1;
}
.lookdog-catgrid__cta{
	display:inline-block;
	background:#F97316;
	color:#fff;
	padding:11px 22px;
	border-radius:4px;
	text-decoration:none;
	font-weight:600;
	font-size:13px;
	letter-spacing:.3px;
}
.lookdog-catgrid__cta:hover,
.lookdog-catgrid__cta:focus{
	background:#EA670B;
	color:#fff;
}
.lookdog-catgrid__cta:focus-visible{
	outline:3px solid rgba(20,33,61,.35);
	outline-offset:2px;
}
@media (prefers-reduced-motion: reduce){
	.lookdog-catgrid__card{transition:none;}
	.lookdog-catgrid__card:hover{transform:none;}
}
</style>
	<?php
}
add_action( 'wp_head', 'lookdog_category_grid_styles', 20 );
