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

/**
 * The three articles the landing page offers.
 *
 * Newest first, minus the personal piece, which has its own place further down
 * the page. Not a hardcoded list: a comparison published tomorrow appears here
 * the same day, which is the only way a landing page stays current on a site
 * that is still being written.
 *
 * @return WP_Post[]
 */
function lookdog_bio_reading( $limit = 3 ) {
	$about = (int) get_option( 'lookdog_about_post' );
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit + 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'exclude'        => $about ? array( $about ) : array(),
		)
	);
	return array_slice( $posts, 0, $limit );
}

/**
 * The numbers under the promise.
 *
 * Read live every time. A landing page that claims "hundreds of products" is
 * making a noise; one that says 250 and can be checked in a click is making a
 * statement, and it cannot drift out of date the way written copy does.
 */
function lookdog_bio_facts() {
	$products = wp_count_posts( 'product' );
	$products = isset( $products->publish ) ? (int) $products->publish : 0;

	$words = 0;
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 60 ) );
	foreach ( $posts as $p ) {
		$words += str_word_count( wp_strip_all_tags( (string) $p->post_content ) );
	}

	return array(
		'products' => $products,
		'floor'    => function_exists( 'lookdog_rating_floor' ) ? lookdog_rating_floor() : '',
		'articles' => count( $posts ),
		'words'    => $words,
	);
}

function lookdog_bio_links_shortcode() {
	$featured = lookdog_bio_featured_id();
	$facts    = lookdog_bio_facts();
	$about    = (int) get_option( 'lookdog_about_post' );
	$rows     = array(
		array( 'term' => 73, 'note' => 'The most-ordered items on the site' ),
		array( 'term' => 68, 'note' => 'Chew toys, puzzles and tug' ),
		array( 'term' => 74, 'note' => 'Beds, blankets and cooling mats' ),
		array( 'term' => 71, 'note' => 'Brushes, clippers and bath kit' ),
	);

	ob_start();
	?>
	<div class="ld-bio">

		<header class="ld-bio__head">
			<?php
			$face = $about ? get_post_thumbnail_id( $about ) : 0;
			if ( $face ) :
				?>
				<img class="ld-bio__face" src="<?php echo esc_url( (string) wp_get_attachment_image_url( $face, 'thumbnail' ) ); ?>"
				     alt="Bell, the Shih Tzu behind LookDog" width="84" height="84" loading="eager">
			<?php endif; ?>
			<h1 class="ld-bio__mark">LookDog</h1>
			<p class="ld-bio__strap">Honest buying guidance for dog owners. We say what a product does badly as well as what it does well.</p>
			<p class="ld-bio__free">Everything here is free to read. We sell nothing, and you can close the tab having bought nothing at all.</p>
		</header>

		<?php if ( $facts['products'] ) : ?>
		<ul class="ld-bio__facts">
			<li><b><?php echo esc_html( number_format_i18n( $facts['products'] ) ); ?></b><span>products, each with its drawbacks written down</span></li>
			<?php if ( $facts['floor'] ) : ?>
				<li><b><?php echo esc_html( $facts['floor'] ); ?>%</b><span>the lowest feedback score we list</span></li>
			<?php endif; ?>
			<li><b><?php echo esc_html( number_format_i18n( $facts['articles'] ) ); ?></b><span>guides, <?php echo esc_html( number_format_i18n( $facts['words'] ) ); ?> words, no email needed</span></li>
		</ul>
		<?php endif; ?>

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

		<?php $reading = lookdog_bio_reading(); ?>
		<?php if ( $reading ) : ?>
		<section class="ld-bio__reading">
			<p class="ld-bio__eyebrow">Read before you buy anything</p>
			<?php foreach ( $reading as $post ) : ?>
				<?php $w = str_word_count( wp_strip_all_tags( (string) $post->post_content ) ); ?>
				<a class="ld-bio__read" href="<?php echo esc_url( (string) get_permalink( $post ) ); ?>">
					<span class="ld-bio__readname"><?php echo esc_html( get_the_title( $post ) ); ?></span>
					<span class="ld-bio__readmeta"><?php echo esc_html( number_format_i18n( $w ) ); ?> words</span>
				</a>
			<?php endforeach; ?>
			<a class="ld-bio__ghost" href="<?php echo esc_url( (string) get_permalink( (int) get_option( 'page_for_posts' ) ) ); ?>">All the guides &rarr;</a>
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

		<?php if ( $about && 'publish' === get_post_status( $about ) ) : ?>
		<section class="ld-bio__who">
			<p class="ld-bio__eyebrow">Who writes this</p>
			<a class="ld-bio__whocard" href="<?php echo esc_url( (string) get_permalink( $about ) ); ?>">
				<?php if ( has_post_thumbnail( $about ) ) : ?>
					<?php echo get_the_post_thumbnail( $about, 'thumbnail', array( 'alt' => '', 'class' => 'ld-bio__whoimg', 'loading' => 'lazy' ) ); ?>
				<?php endif; ?>
				<span class="ld-bio__whotext">
					<span class="ld-bio__whoname">One person, 47 years of dogs, and Bell</span>
					<span class="ld-bio__whonote">Why this site exists, the mistakes I made first, and why none of it costs you anything.</span>
				</span>
			</a>
		</section>
		<?php endif; ?>

		<section class="ld-bio__foot">
			<a class="ld-bio__ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Everything on LookDog</a>
			<p class="ld-bio__social">
				<a href="https://www.instagram.com/lookdog435/" rel="me noopener">Instagram</a>
				<a href="https://www.tiktok.com/@lookdog435" rel="me noopener">TikTok</a>
				<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">Contact</a>
			</p>
			<p class="ld-bio__disclosure">LookDog lists products sold on AliExpress. We may earn a commission if you buy through our links, at no extra cost to you. We do not sell, ship or handle returns ourselves.</p>
		</section>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'lookdog_bio_links', 'lookdog_bio_links_shortcode' );
