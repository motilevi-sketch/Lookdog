<?php
/**
 * LookDog - "Start with the problem" index.
 *
 * [lookdog_problems]  the hub list
 * [lookdog_problems_link]  one-line text link, for dropping into another page
 *
 * The catalogue is organised by what a thing IS - a bowl, a harness, a bed -
 * which is how a shop thinks. Nobody arrives thinking "I need a front-clip
 * harness"; they arrive thinking "my dog drags me down the street". This is the
 * second axis: ten problems, each a `product_tag` whose archive carries real
 * guidance and the products that address it.
 *
 * A tag exists only where the catalogue can honestly answer the problem. Four
 * products with a straight explanation beats twelve padded out with anything
 * loosely related - the same rule the category-to-guide map follows.
 *
 * Three term meta keys drive a row:
 *
 *   lookdog_problem_line   one line of card copy
 *   lookdog_problem_order  sort position, and what makes a tag a problem at all
 *   lookdog_problem_image  attachment ID for the thumbnail
 *
 * The thumbnails default to the highest-selling product carrying the tag, which
 * is right eight times in ten and wrong in a way worth watching: it put a
 * squeaky ball on "bad breath and teeth" and a training lead on both "pulling"
 * and "running off". Those two are set by hand. Check the picture matches the
 * problem when you add a tag.
 *
 * Laid out as a list with hairline rules rather than a card grid. The homepage
 * already spends its card grid on categories, and the design brief does not let
 * a layout family repeat.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-problems.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every problem tag that has at least one product, in display order.
 *
 * Membership is `lookdog_problem_order`, not simply "is a product_tag". The
 * taxonomy also carries tags that are not problems - `puppy` is a life stage,
 * and listing it here would put "Puppy essentials" between "Barking too much"
 * and "Chewing everything" as though it were something to fix. Give a new
 * problem an order and it appears; leave the meta off and it does not.
 *
 * @return WP_Term[]
 */
function lookdog_problem_terms() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
			'meta_query' => array(
				array(
					'key'     => 'lookdog_problem_order',
					'compare' => 'EXISTS',
				),
			),
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			$oa = (int) get_term_meta( $a->term_id, 'lookdog_problem_order', true ) ?: PHP_INT_MAX;
			$ob = (int) get_term_meta( $b->term_id, 'lookdog_problem_order', true ) ?: PHP_INT_MAX;
			return $oa === $ob ? strcasecmp( $a->name, $b->name ) : $oa - $ob;
		}
	);

	return $terms;
}

function lookdog_problems( $atts = array() ) {
	$atts  = shortcode_atts( array( 'heading' => 'Start with the problem' ), $atts, 'lookdog_problems' );
	$terms = lookdog_problem_terms();
	if ( ! $terms ) {
		return '';
	}

	ob_start();
	?>
<section class="ld-band">
	<div class="ld-wrap">
		<?php if ( '' !== $atts['heading'] ) : ?>
			<h2 class="ld-h2 ld-h2--lead"><?php echo esc_html( $atts['heading'] ); ?></h2>
		<?php endif; ?>
		<ul class="ld-probs">
			<?php foreach ( $terms as $t ) : ?>
				<?php
				$line = (string) get_term_meta( $t->term_id, 'lookdog_problem_line', true );
				$img  = (int) get_term_meta( $t->term_id, 'lookdog_problem_image', true );
				$url  = get_term_link( $t );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				?>
				<li class="ld-prob">
					<a class="ld-prob__link" href="<?php echo esc_url( $url ); ?>">
						<span class="ld-prob__media">
							<?php
							if ( $img ) {
								echo wp_get_attachment_image( $img, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) );
							}
							?>
						</span>
						<span class="ld-prob__body">
							<span class="ld-prob__name"><?php echo esc_html( $t->name ); ?></span>
							<?php if ( '' !== $line ) : ?>
								<span class="ld-prob__copy"><?php echo esc_html( $line ); ?></span>
							<?php endif; ?>
						</span>
						<span class="ld-prob__count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_problems', 'lookdog_problems' );

/**
 * A single text link, for pages that should point at the hub without carrying
 * the whole list.
 */
function lookdog_problems_link( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'text' => 'Or start from a problem instead &rarr;',
			'page' => 'shop-by-problem',
		),
		$atts,
		'lookdog_problems_link'
	);

	$page = get_page_by_path( sanitize_title( $atts['page'] ) );
	if ( ! $page ) {
		return '';
	}

	return '<p class="ld-probs__link"><a class="ld-textlink" href="' . esc_url( (string) get_permalink( $page ) ) . '">'
		. wp_kses( $atts['text'], array() ) . '</a></p>';
}
add_shortcode( 'lookdog_problems_link', 'lookdog_problems_link' );

add_action(
	'wp_head',
	static function () {
		global $post;
		$content = $post instanceof WP_Post ? (string) $post->post_content : '';
		if ( ! has_shortcode( $content, 'lookdog_problems' ) && ! has_shortcode( $content, 'lookdog_problems_link' ) ) {
			return;
		}
		?>
<style id="lookdog-problems-css">
.ld-probs{list-style:none;margin:0;padding:0;border-top:1px solid #E6E6E1;}
.ld-prob{border-bottom:1px solid #E6E6E1;}
.ld-prob__link{display:grid;grid-template-columns:76px minmax(0,1fr) auto;gap:26px;align-items:center;
padding:20px 4px;text-decoration:none;transition:background .18s ease;}
.ld-prob__link:hover,.ld-prob__link:focus{background:#F8F8F6;}
.ld-prob__link:focus-visible{outline:3px solid #F97316;outline-offset:-3px;}
.ld-prob__media{display:block;width:76px;height:76px;border-radius:10px;overflow:hidden;background:#EFEFEC;}
.ld-prob__media img{width:100%;height:100%;object-fit:cover;display:block;}
.ld-prob__name{display:block;color:#14213D;font-size:19px;font-weight:600;line-height:1.25;}
.ld-prob__copy{display:block;margin-top:6px;color:#3A3F4B;font-size:14.5px;line-height:1.6;max-width:70ch;}
.ld-prob__count{color:#5A5F6B;font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;
white-space:nowrap;}
.ld-prob__count::after{content:" items";}
.ld-probs__link{margin:26px 0 0;text-align:center;}
@media (max-width:640px){
	.ld-prob__link{grid-template-columns:56px minmax(0,1fr);gap:16px;padding:16px 2px;}
	.ld-prob__media{width:56px;height:56px;}
	.ld-prob__name{font-size:17px;}
	.ld-prob__count{grid-column:2;font-size:11px;}
}
</style>
		<?php
	},
	20
);
