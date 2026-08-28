<?php
/**
 * LookDog - the drawbacks band.
 *
 * [lookdog_drawbacks]
 *
 * The homepage said, in the method band, that every listing names what a
 * product does badly. It said it and never showed it, which is the weakest
 * possible form of the claim - any site can write that sentence.
 *
 * This band shows it. Four real "Cons" lines, lifted verbatim from four real
 * product pages, each credited and linked so anyone can check. All 167 products
 * carry one, so there is nothing to curate and nothing to fake; the band simply
 * reads the catalogue.
 *
 * It is the one section of this homepage a competitor cannot copy by copying
 * the layout, because the layout is not the asset. The 167 written drawbacks
 * are.
 *
 * ROTATION. Four are chosen from a seed derived from the date, so the band is
 * stable for a day - the same for every visitor, cacheable, and quotable in a
 * screenshot - and different tomorrow. Random-per-request would flicker and
 * make the page untestable.
 *
 * SPREAD. One product from each of four different categories, rather than four
 * from whatever the query returned, so the band cannot open with four toys and
 * imply that toys are the only things with problems.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-drawbacks.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every product's Cons line, keyed by post ID.
 *
 * The line lives in `post_content` under a "Pros and Cons" heading, written by
 * the importer as `<strong>Cons:</strong> …</p>`. Parsing the content is
 * deliberate rather than lazy: it means the band can never disagree with the
 * product page, because it is the same sentence. If the format ever changes,
 * this returns fewer rows and the band hides itself rather than inventing one.
 *
 * Cached for a day. Delete `lookdog_drawbacks` after editing product copy.
 *
 * @return array<int,string>
 */
function lookdog_drawback_lines() {
	$cached = get_transient( 'lookdog_drawbacks' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$out = array();
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $ids as $id ) {
		$content = (string) get_post_field( 'post_content', $id );
		if ( ! preg_match( '#<strong>Cons:</strong>\s*(.*?)</p>#s', $content, $m ) ) {
			continue;
		}
		$line = trim( wp_strip_all_tags( $m[1] ) );
		if ( '' !== $line ) {
			$out[ $id ] = $line;
		}
	}

	set_transient( 'lookdog_drawbacks', $out, DAY_IN_SECONDS );

	return $out;
}

/**
 * Four drawbacks, one per category, stable for the day.
 *
 * @return array<int,array{id:int,line:string,cat:string}>
 */
function lookdog_drawback_picks( $wanted = 4 ) {
	$lines = lookdog_drawback_lines();
	if ( ! $lines ) {
		return array();
	}

	$cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => array( get_option( 'default_product_cat' ), 73 ), // 73 is Best Sellers, a curation layer.
		)
	);
	if ( is_wp_error( $cats ) || ! $cats ) {
		return array();
	}

	// One deterministic seed for the whole selection, rolled at midnight UTC.
	$seed = (int) gmdate( 'Ymd' );

	usort( $cats, static fn( $a, $b ) => $a->term_id <=> $b->term_id );
	$offset = $seed % max( 1, count( $cats ) );
	$cats   = array_merge( array_slice( $cats, $offset ), array_slice( $cats, 0, $offset ) );

	$picks = array();
	foreach ( $cats as $cat ) {
		if ( count( $picks ) >= $wanted ) {
			break;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $cat->term_id,
					),
				),
			)
		);

		$ids = array_values( array_filter( $ids, static fn( $id ) => isset( $lines[ $id ] ) && ! isset( $picks[ $id ] ) ) );
		if ( ! $ids ) {
			continue;
		}

		$id           = $ids[ ( $seed + $cat->term_id ) % count( $ids ) ];
		$picks[ $id ] = array(
			'id'   => $id,
			'line' => $lines[ $id ],
			'cat'  => $cat->name,
		);
	}

	return array_values( $picks );
}

function lookdog_drawbacks( $atts = array() ) {
	$atts  = shortcode_atts(
		array(
			'heading' => 'The part other sites leave out',
			'count'   => '4',
		),
		$atts,
		'lookdog_drawbacks'
	);
	$picks = lookdog_drawback_picks( absint( $atts['count'] ) );
	if ( count( $picks ) < 2 ) {
		return '';
	}

	$total     = count( lookdog_drawback_lines() );
	$published = (int) wp_count_posts( 'product' )->publish;

	// "167 of our 167 listings carry one" is what a counter writes, not a person.
	$coverage = $total >= $published
		? 'Every one of our ' . number_format_i18n( $published ) . ' listings carries one.'
		: number_format_i18n( $total ) . ' of our ' . number_format_i18n( $published ) . ' listings carry one.';

	ob_start();
	?>
<section class="ld-band">
	<div class="ld-wrap">
		<div class="ld-draw__head">
			<h2 class="ld-h2"><?php echo esc_html( $atts['heading'] ); ?></h2>
			<p class="ld-draw__lede">Every listing on this site says what the product does badly, in plain words, next to what it does well. Not a rating out of five &mdash; a sentence. Here are four of them, straight off four product pages, changed daily.</p>
		</div>

		<ul class="ld-draws">
			<?php foreach ( $picks as $p ) : ?>
				<li class="ld-draw">
					<p class="ld-draw__line"><?php echo esc_html( $p['line'] ); ?></p>
					<p class="ld-draw__src">
						<a href="<?php echo esc_url( (string) get_permalink( $p['id'] ) ); ?>"><?php echo esc_html( get_the_title( $p['id'] ) ); ?></a>
						<span class="ld-draw__cat"><?php echo esc_html( $p['cat'] ); ?></span>
					</p>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="ld-draw__foot"><?php echo esc_html( $coverage ); ?> It is the whole reason to read a page here rather than the seller&rsquo;s.</p>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_drawbacks', 'lookdog_drawbacks' );

add_action(
	'wp_head',
	static function () {
		global $post;
		if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, 'lookdog_drawbacks' ) ) {
			return;
		}
		?>
<style id="lookdog-drawbacks-css">
.ld-draw__head{max-width:66ch;margin-bottom:44px}
.ld-draw__lede{margin:16px 0 0;color:#3A3F4B;font-size:16px;line-height:1.65}
/* Two flowing columns rather than a card grid or a row of equal boxes: the
   items are different lengths and should look it, and no other band on this
   page uses a column flow. */
.ld-draws{list-style:none;margin:0;padding:0;columns:2;column-gap:56px}
.ld-draw{break-inside:avoid;margin:0 0 34px;padding-left:20px;
border-left:2px solid #D5D5CE}
.ld-draw__line{margin:0;color:#14213D;font-size:18px;line-height:1.55}
.ld-draw__src{margin:10px 0 0;font-size:13px;line-height:1.5}
.ld-draw__src a{color:#EA670B;font-weight:600;text-decoration:none;
border-bottom:1px solid #F97316}
.ld-draw__src a:hover,.ld-draw__src a:focus{color:#14213D;border-bottom-color:#14213D}
.ld-draw__cat{display:block;margin-top:3px;color:#5A5F6B;font-size:12px;
letter-spacing:.08em;text-transform:uppercase}
.ld-draw__foot{margin:14px 0 0;padding-top:24px;border-top:1px solid #E6E6E1;
color:#5A5F6B;font-size:14px;line-height:1.6;max-width:70ch}
@media (max-width:820px){
	.ld-draws{columns:1}
	.ld-draw{margin-bottom:28px}
}
@media (max-width:640px){
	.ld-draw__head{margin-bottom:32px}
	.ld-draw__line{font-size:16.5px}
}
</style>
		<?php
	},
	20
);
