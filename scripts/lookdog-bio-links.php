<?php
/**
 * LookDog - link-in-bio landing page.
 *
 * Instagram gives an account exactly one clickable link. This is what it points
 * at: the product from the current campaign first, because that is what someone
 * arriving from the ad came for, then the shortcuts they need if it was not.
 *
 * Used as [lookdog_bio_links] on the /start page. Styles live in
 * lookdog-bio-styles.php and only load when this shortcode is on the page.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-bio-links.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * The product the current campaign is running. Change this, not the markup.
 *
 * Everything below follows from it: the name, the photograph, the one-line
 * description and the link all come from the product itself, so swapping the id
 * swaps the whole card. An earlier version had the description and the /go/tug
 * link written into the markup beside this filter, which meant changing the
 * featured product would have left the old sales copy pointing at the old item.
 */
function lookdog_bio_featured_id() {
	return (int) apply_filters( 'lookdog_bio_featured_id', 3553 );
}

/** The product's own short description, minus the affiliate boilerplate. */
function lookdog_bio_featured_line( $post_id ) {
	$text = wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $post_id ) );
	$text = str_replace(
		'Affiliate notice: LookDog may earn a commission if you purchase through this link, at no additional cost to you.',
		'',
		$text
	);
	return wp_trim_words( trim( $text ), 30, '&hellip;' );
}

function lookdog_bio_term_image( $term_id, $size = 'medium' ) {
	$att = (int) get_term_meta( $term_id, 'thumbnail_id', true );
	return $att ? wp_get_attachment_image_url( $att, $size ) : '';
}

function lookdog_bio_links_shortcode() {
	$featured = lookdog_bio_featured_id();
	$rows     = array(
		array( 'term' => 73, 'note' => 'The 24 most-ordered items on the site' ),
		array( 'term' => 68, 'note' => 'Chew toys, puzzles and tug' ),
		array( 'term' => 74, 'note' => 'Beds, blankets and cooling mats' ),
		array( 'term' => 71, 'note' => 'Brushes, clippers and bath kit' ),
	);

	ob_start();
	?>
	<div class="ld-bio">

		<header class="ld-bio__head">
			<h1 class="ld-bio__mark">LookDog</h1>
			<p class="ld-bio__strap">Honest buying guidance for dog owners. We say what a product does badly as well as what it does well.</p>
		</header>

		<?php if ( 'publish' === get_post_status( $featured ) ) : ?>
		<section class="ld-bio__feature">
			<p class="ld-bio__eyebrow">From the post you just saw</p>
			<a class="ld-bio__featurecard" href="<?php echo esc_url( lookdog_go_url_for_post( $featured ) ); ?>">
				<?php if ( has_post_thumbnail( $featured ) ) : ?>
					<span class="ld-bio__shot">
						<?php echo get_the_post_thumbnail( $featured, 'medium_large', array( 'alt' => get_the_title( $featured ), 'loading' => 'eager' ) ); ?>
					</span>
				<?php endif; ?>
				<span class="ld-bio__featurebody">
					<span class="ld-bio__featurename"><?php echo esc_html( get_the_title( $featured ) ); ?></span>
					<span class="ld-bio__featurenote"><?php echo esc_html( lookdog_bio_featured_line( $featured ) ); ?></span>
					<span class="ld-bio__btn">Read the full write-up</span>
				</span>
			</a>
		</section>
		<?php endif; ?>

		<nav class="ld-bio__list" aria-label="Browse the catalogue">
			<?php
			foreach ( $rows as $row ) {
				$term = get_term( $row['term'], 'product_cat' );
				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}
				$img = lookdog_bio_term_image( $row['term'] );
				?>
				<a class="ld-bio__row" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php if ( $img ) : ?>
						<img class="ld-bio__rowimg" src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" width="56" height="56">
					<?php endif; ?>
					<span class="ld-bio__rowtext">
						<span class="ld-bio__rowname"><?php echo esc_html( $term->name ); ?></span>
						<span class="ld-bio__rownote"><?php echo esc_html( $row['note'] ); ?></span>
					</span>
					<span class="ld-bio__count"><?php echo (int) $term->count; ?></span>
				</a>
				<?php
			}
			?>
		</nav>

		<?php
		/**
		 * Browse by problem.
		 *
		 * This is the section a social post can actually point at. Neither
		 * Instagram nor TikTok allows a link in a caption, so a post about
		 * pulling on the lead says "link in bio" and this is what it lands on.
		 * Each row goes through /go/ so the traffic from each post is
		 * separable in analytics rather than arriving as one undifferentiated
		 * lump of "social".
		 *
		 * Order and membership come from the same term meta the site uses, so
		 * a new problem page appears here on its own.
		 */
		$problems = function_exists( 'lookdog_problem_terms' ) ? lookdog_problem_terms() : array();
		$go_for   = array(
			'pulls-on-the-lead'   => 'pull',
			'chews-everything'    => 'chew',
			'eats-too-fast'       => 'gulp',
			'sheds-everywhere'    => 'shed',
			'gets-too-hot'        => 'hot',
			'barks-too-much'      => 'bark',
			'runs-off'            => 'lost',
			'walking-in-the-dark' => 'dark',
			'hates-the-car'       => 'car',
			'bad-breath'          => 'teeth',
		);
		?>
		<?php if ( $problems ) : ?>
		<section class="ld-bio__problems">
			<p class="ld-bio__eyebrow">Start with what is going wrong</p>
			<nav class="ld-bio__chips" aria-label="Browse by problem">
				<?php
				foreach ( $problems as $t ) {
					$slug = isset( $go_for[ $t->slug ] ) ? $go_for[ $t->slug ] : '';
					$href = $slug ? home_url( '/go/' . $slug ) : get_term_link( $t );
					if ( is_wp_error( $href ) ) {
						continue;
					}
					?>
					<a class="ld-bio__chip" href="<?php echo esc_url( $href ); ?>">
						<?php echo esc_html( $t->name ); ?>
						<span class="ld-bio__chipn"><?php echo (int) $t->count; ?></span>
					</a>
					<?php
				}
				?>
			</nav>
			<a class="ld-bio__ghost" href="<?php echo esc_url( home_url( '/go/fix' ) ); ?>">See all ten &rarr;</a>
		</section>
		<?php endif; ?>

		<section class="ld-bio__foot">
			<a class="ld-bio__ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Everything on LookDog</a>
			<p class="ld-bio__disclosure">LookDog lists products sold on AliExpress. We may earn a commission if you buy through our links, at no extra cost to you. We do not sell, ship or handle returns ourselves.</p>
		</section>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'lookdog_bio_links', 'lookdog_bio_links_shortcode' );
