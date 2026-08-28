<?php
/**
 * LookDog - homepage guides index.
 *
 * [lookdog_guides_index]
 *
 * The homepage promoted one guide and buried the other five behind /blog/.
 * This lists all of them.
 *
 * An index list, which is a layout family the page does not otherwise use: the
 * homepage already runs a photographic cover, a scrolling rail, a card grid, an
 * asymmetric split and three stat columns, and the design forbids repeating one.
 *
 * Deliberately monochrome. Each guide has its own accent, but the site rule is
 * one accent per page, and six colour chips here would be the exact rainbow that
 * rule exists to prevent. The colour belongs on the article, not in the index.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-home-guides.php
 */

defined( 'ABSPATH' ) || exit;

function lookdog_guides_index( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'heading' => 'The guides',
			'limit'   => '8',
		),
		$atts,
		'lookdog_guides_index'
	);

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	if ( ! $posts ) {
		return '';
	}

	$blog = (int) get_option( 'page_for_posts' );

	ob_start();
	?>
<section class="ld-band ld-band--tint">
	<div class="ld-wrap">
		<div class="ld-rail__head">
			<h2 class="ld-h2"><?php echo esc_html( $atts['heading'] ); ?></h2>
			<?php if ( $blog ) : ?>
				<a class="ld-textlink" href="<?php echo esc_url( (string) get_permalink( $blog ) ); ?>">See all</a>
			<?php endif; ?>
		</div>
		<ol class="ld-index">
			<?php foreach ( $posts as $p ) : ?>
				<?php
				$words = str_word_count( wp_strip_all_tags( (string) $p->post_content ) );
				$line  = wp_trim_words( wp_strip_all_tags( (string) $p->post_excerpt ), 22, '&hellip;' );
				?>
			<li class="ld-index__row">
				<a class="ld-index__link" href="<?php echo esc_url( (string) get_permalink( $p ) ); ?>">
					<span class="ld-index__title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
					<?php if ( '' !== $line ) : ?>
						<span class="ld-index__line"><?php echo esc_html( $line ); ?></span>
					<?php endif; ?>
					<span class="ld-index__meta"><?php echo esc_html( number_format_i18n( $words ) ); ?> words</span>
				</a>
			</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lookdog_guides_index', 'lookdog_guides_index' );

add_action( 'wp_head', static function () {
	$post = get_post();
	if ( ! $post || ! has_shortcode( (string) $post->post_content, 'lookdog_guides_index' ) ) {
		return;
	}
	?>
<style id="lookdog-guides-index">
.ld-index{counter-reset:ldg;list-style:none;margin:0;padding:0;border-top:1px solid #D5D5CE}
.ld-index__row{border-bottom:1px solid #E6E6E1}
.ld-index__link{display:grid;grid-template-columns:44px minmax(0,1fr) auto;gap:4px 18px;
align-items:baseline;padding:20px 4px;text-decoration:none;color:inherit;
transition:background .15s ease}
.ld-index__link:hover{background:#FFFFFF}
.ld-index__link::before{counter-increment:ldg;content:counter(ldg,decimal-leading-zero);
grid-row:1/3;font-size:13px;font-weight:600;color:#5A5F6B;font-variant-numeric:tabular-nums;
padding-top:3px}
.ld-index__title{font-size:19px;font-weight:600;line-height:1.3;color:#14213D;
transition:color .15s ease}
.ld-index__link:hover .ld-index__title{color:#EA670B}
.ld-index__meta{font-size:13px;color:#5A5F6B;white-space:nowrap;font-variant-numeric:tabular-nums}
.ld-index__line{grid-column:2;font-size:15px;line-height:1.55;color:#3A3F4B}
@media (max-width:640px){
.ld-index__link{grid-template-columns:34px minmax(0,1fr);gap:3px 14px}
.ld-index__meta{grid-column:2;font-size:12px}
.ld-index__title{font-size:17px}
}
</style>
	<?php
}, 24 );
