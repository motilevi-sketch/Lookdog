<?php
/**
 * LookDog - the writing, listed on the homepage.
 *
 * [lookdog_reading_index]
 *
 * Seventeen articles, twenty-four thousand words, and until this band existed
 * the homepage linked to none of them. Everything above it - the hero, the
 * rail, the price drops, the category cards - sends a reader towards a
 * checkout. The one thing on the site that is worth reading before spending
 * anything was reachable only through the header menu.
 *
 * That is bad for readers and worse for search: an article nothing links to is
 * an article Google has little reason to crawl again, and internal links are
 * the only signal here we actually control.
 *
 * Plain typography, no thumbnails, no cards. The homepage already spends a
 * photographic cover on the hero, a scrolling card rail on best sellers, an
 * image list on price drops and a card grid on categories; the design brief
 * does not let a layout family repeat, and these are headlines rather than
 * things to look at. The titles are questions because that is how the articles
 * were written, so they need no explaining sentence underneath.
 *
 * Two columns because the split is real, not decorative: one side is for a
 * reader with a problem, the other for a reader with a purchase in mind. The
 * word counts are read live and are the only claim the band makes.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-home-reading.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * The articles, split into the two columns.
 *
 * Membership is the post category, not a hardcoded list, so a new article
 * appears here the day it is published without anybody remembering to come
 * back and edit this file.
 *
 * The problem column is ordered to match the "Start with the problem" hub
 * rather than by date. That order was chosen deliberately - most common first -
 * and a reader who meets the same ten items in two different orders on two
 * pages has to read both lists twice. Articles without a matching problem tag,
 * and every article in the second column, fall back to newest first.
 *
 * @return array{problems:WP_Post[],guides:WP_Post[]}
 */
function lookdog_reading_columns() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	// article id => problem tag slug, from the map the product pages already use.
	$slug_of = array();
	if ( function_exists( 'lookdog_problem_reading_map' ) ) {
		foreach ( lookdog_problem_reading_map() as $slug => $row ) {
			$slug_of[ (int) $row[0] ] = $slug;
		}
	}

	// problem tag slug => hub position.
	$rank_of = array();
	if ( function_exists( 'lookdog_problem_terms' ) ) {
		$i = 0;
		foreach ( lookdog_problem_terms() as $term ) {
			$rank_of[ $term->slug ] = ++$i;
		}
	}

	// The personal piece is a post like any other and belongs in neither
	// column: it is not something to read before buying, it is who is doing
	// the writing. It gets its own line above the columns instead.
	$about = (int) get_option( 'lookdog_about_post' );

	$problems = array();
	$guides   = array();
	foreach ( $posts as $post ) {
		if ( $about && (int) $post->ID === $about ) {
			continue;
		}
		if ( has_category( 'common-problems', $post ) ) {
			$problems[] = $post;
		} else {
			$guides[] = $post;
		}
	}

	usort(
		$problems,
		static function ( $a, $b ) use ( $slug_of, $rank_of ) {
			$ra = isset( $slug_of[ $a->ID ], $rank_of[ $slug_of[ $a->ID ] ] ) ? $rank_of[ $slug_of[ $a->ID ] ] : PHP_INT_MAX;
			$rb = isset( $slug_of[ $b->ID ], $rank_of[ $slug_of[ $b->ID ] ] ) ? $rank_of[ $slug_of[ $b->ID ] ] : PHP_INT_MAX;
			if ( $ra === $rb ) {
				return strcmp( $b->post_date, $a->post_date );
			}
			return $ra - $rb;
		}
	);

	return array(
		'problems' => $problems,
		'guides'   => $guides,
	);
}

/**
 * One column of titles.
 *
 * @param string    $label Column heading.
 * @param WP_Post[] $posts Articles.
 */
function lookdog_reading_column( $label, $posts ) {
	if ( ! $posts ) {
		return;
	}
	?>
<div class="ld-read__col">
	<h3 class="ld-read__label"><?php echo esc_html( $label ); ?></h3>
	<ul class="ld-read__list">
		<?php foreach ( $posts as $post ) : ?>
			<?php $words = str_word_count( wp_strip_all_tags( (string) $post->post_content ) ); ?>
		<li class="ld-read__row">
			<a class="ld-read__link" href="<?php echo esc_url( (string) get_permalink( $post ) ); ?>">
				<span class="ld-read__title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
				<span class="ld-read__words"><?php echo esc_html( number_format_i18n( $words ) ); ?></span>
			</a>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
	<?php
}

/**
 * The "who writes this" post, if one is set.
 *
 * @return ?array{url:string,title:string}
 */
function lookdog_about_article() {
	$id = (int) get_option( 'lookdog_about_post' );
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return null;
	}
	return array(
		'url'   => (string) get_permalink( $id ),
		'title' => get_the_title( $id ),
	);
}

function lookdog_reading_index( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'heading'  => 'Read before you buy anything',
			'problems' => 'If something is going wrong',
			'guides'   => 'If you are choosing between things',
		),
		$atts,
		'lookdog_reading_index'
	);

	$cols = lookdog_reading_columns();
	if ( ! $cols['problems'] && ! $cols['guides'] ) {
		return '';
	}

	$count = count( $cols['problems'] ) + count( $cols['guides'] );
	$words = 0;
	foreach ( array_merge( $cols['problems'], $cols['guides'] ) as $post ) {
		$words += str_word_count( wp_strip_all_tags( (string) $post->post_content ) );
	}

	$blog = (int) get_option( 'page_for_posts' );

	ob_start();
	?>
<section class="ld-band ld-read">
	<div class="ld-wrap">
		<div class="ld-read__head">
			<h2 class="ld-h2"><?php echo esc_html( $atts['heading'] ); ?></h2>
			<p class="ld-read__lede">
				<?php
				printf(
					/* translators: 1: number of articles, 2: total word count. */
					esc_html__( '%1$s articles, %2$s words, no email address and no account. They say when the thing you are looking at is the wrong answer, which costs us the sale and is the only way any of this is worth reading.', 'lookdog' ),
					esc_html( number_format_i18n( $count ) ),
					esc_html( number_format_i18n( $words ) )
				);
				?>
			</p>
			<?php $about = lookdog_about_article(); ?>
			<?php if ( $about || $blog ) : ?>
				<p class="ld-read__all">
					<?php if ( $about ) : ?>
						<a class="ld-textlink" href="<?php echo esc_url( $about['url'] ); ?>"><?php esc_html_e( 'Who writes this, and why it is free', 'lookdog' ); ?> &rarr;</a>
					<?php endif; ?>
					<?php if ( $blog ) : ?>
						<a class="ld-textlink" href="<?php echo esc_url( (string) get_permalink( $blog ) ); ?>"><?php esc_html_e( 'All of it, newest first', 'lookdog' ); ?> &rarr;</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<div class="ld-read__cols">
			<?php
			lookdog_reading_column( $atts['problems'], $cols['problems'] );
			lookdog_reading_column( $atts['guides'], $cols['guides'] );
			?>
		</div>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_reading_index', 'lookdog_reading_index' );

add_action(
	'wp_head',
	static function () {
		global $post;
		if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, 'lookdog_reading_index' ) ) {
			return;
		}
		?>
<style id="lookdog-reading-index">
.ld-read__head{max-width:64ch;margin-bottom:44px}
.ld-read__lede{margin:16px 0 0;color:#3A3F4B;font-size:16px;line-height:1.65}
.ld-read__all{display:flex;flex-wrap:wrap;gap:14px 30px;margin:20px 0 0}
.ld-read__cols{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:34px 64px}
.ld-read__label{margin:0 0 4px;padding-bottom:14px;border-bottom:2px solid #14213D;
color:#5A5F6B;font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase}
.ld-read__list{list-style:none;margin:0;padding:0}
.ld-read__row{border-bottom:1px solid #E6E6E1}
.ld-read__link{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:baseline;
padding:15px 4px;text-decoration:none;transition:background .15s ease}
.ld-read__link:hover,.ld-read__link:focus{background:#F8F8F6}
.ld-read__link:focus-visible{outline:3px solid #F97316;outline-offset:-3px}
.ld-read__title{color:#14213D;font-size:16.5px;font-weight:600;line-height:1.35;
transition:color .15s ease}
.ld-read__link:hover .ld-read__title,.ld-read__link:focus .ld-read__title{color:#EA670B}
.ld-read__words{color:#5A5F6B;font-size:12.5px;white-space:nowrap;font-variant-numeric:tabular-nums}
.ld-read__words::after{content:" words"}
@media (max-width:820px){
	.ld-read__cols{grid-template-columns:minmax(0,1fr);gap:38px}
}
@media (max-width:640px){
	.ld-read__head{margin-bottom:34px}
	.ld-read__title{font-size:16px}
	.ld-read__link{gap:2px 14px;padding:13px 2px}
}
</style>
		<?php
	},
	20
);
