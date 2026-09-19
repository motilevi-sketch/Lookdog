<?php
/**
 * LookDog - topic clusters between the guides.
 *
 * WHY THIS EXISTS. Products link to guides well: 26 of the 27 guides receive a
 * "Before you buy" link from at least one product page, and the grooming guide
 * receives 32. The guides barely link to each other at all - twelve of them are
 * not linked from the body of any other guide on the site. So the catalogue
 * points at the writing, and the writing points nowhere.
 *
 * That is the half that search engines read as topical authority: a set of
 * pages that visibly belong together and say so. A reader who has just finished
 * the shedding guide is one step from the grooming guide and the clipping
 * guide, and currently has no way to take it except the menu.
 *
 * WHY THIS IS A BLOCK AND NOT PROSE LINKS. The house voice rules are explicit:
 * "Each guide carries one out-link to another guide, at a genuine topical
 * bridge, and one to its category archive. Not five - six guides all
 * cross-linking to each other reads as an SEO exercise." That rule is right,
 * and threading three more links into every article's body to build clusters
 * would break it.
 *
 * So this does not touch a single word of any article. It is a navigational
 * footer, visibly separate from the writing, which is what it actually is - a
 * reader can tell at a glance that these are related guides rather than
 * something the author chose to say mid-sentence. The editorial out-link inside
 * each piece stays exactly as written.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-guide-clusters.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * The clusters, as subject => guide ids.
 *
 * Ordered within each cluster with the broad guide first, because that is the
 * one a reader arriving from a narrow problem piece most often wants next.
 *
 * A guide may sit in more than one cluster where it genuinely belongs to both:
 * the bath guide is coat care and it is also handling a dog that will not stand
 * still, and a reader arriving from either direction is well served by it.
 *
 * The About piece is deliberately absent. It has its own place on the site and
 * it is not a guide.
 *
 * @return array<string,int[]>
 */
function lookdog_guide_clusters() {
	return apply_filters(
		'lookdog_guide_clusters',
		array(
			'coat'      => array( 4497, 5252, 5030, 5289 ),
			'car'       => array( 4498, 5035, 5098 ),
			'rest'      => array( 4499, 5099, 5091, 5031 ),
			'outdoors'  => array( 4500, 5089, 5033, 5034 ),
			'feeding'   => array( 3777, 5090, 5029 ),
			// Two clusters, not one "behaviour" bucket. A six-guide cluster is
			// only ever read three deep, so the puppy guide and the play guide
			// were both being offered harnesses and pulling on the lead - true
			// of the bucket, useless to the reader. Walking problems and the
			// gear for them sit together; chewing, play and the puppy who does
			// both sit together.
			'lead'      => array( 5027, 5092, 5032 ),
			'chewplay'  => array( 5028, 3344, 4524 ),
			'handling'  => array( 5036, 5101, 5289 ),
		)
	);
}

/**
 * The other guides in this guide's cluster or clusters.
 *
 * Published-only, de-duplicated, and capped - a list long enough to be a menu
 * stops being a recommendation. Cluster order is preserved rather than sorted
 * by date, so the broad guide leads.
 *
 * @param int $post_id Guide being read.
 * @param int $limit   Most to return.
 * @return int[]
 */
function lookdog_guide_cluster_siblings( $post_id, $limit = 3 ) {
	$post_id = (int) $post_id;
	$out     = array();

	foreach ( lookdog_guide_clusters() as $ids ) {
		if ( ! in_array( $post_id, $ids, true ) ) {
			continue;
		}
		foreach ( $ids as $id ) {
			if ( $id === $post_id || isset( $out[ $id ] ) ) {
				continue;
			}
			if ( 'publish' !== get_post_status( $id ) ) {
				continue;
			}
			$out[ $id ] = true;
		}
	}

	return array_slice( array_keys( $out ), 0, (int) $limit );
}

add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$siblings = lookdog_guide_cluster_siblings( get_the_ID() );
		if ( ! $siblings ) {
			return $content;
		}

		$block  = '<aside class="ld-cluster" aria-label="Related guides">';
		$block .= '<p class="ld-cluster__label">Read next</p>';
		$block .= '<ul class="ld-cluster__list">';

		foreach ( $siblings as $id ) {
			$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ) );
			$block .= '<li class="ld-cluster__item"><a class="ld-cluster__link" href="' . esc_url( (string) get_permalink( $id ) ) . '">'
				. '<span class="ld-cluster__name">' . esc_html( get_the_title( $id ) ) . '</span>'
				/* translators: %s: word count of the linked guide. */
				. '<span class="ld-cluster__meta">' . esc_html( sprintf( __( '%s words', 'lookdog' ), number_format_i18n( $words ) ) ) . '</span>'
				. '</a></li>';
		}

		$block .= '</ul></aside>';

		return $content . $block;
	},
	25
);

add_action(
	'wp_head',
	static function () {
		if ( ! is_singular( 'post' ) || ! lookdog_guide_cluster_siblings( get_queried_object_id() ) ) {
			return;
		}
		?>
<style id="lookdog-cluster">
.ld-cluster{margin:40px 0 0;padding:22px 0 0;border-top:1px solid #E6E6E1;
font-family:Poppins,sans-serif}
.ld-cluster__label{margin:0 0 14px;font-size:11px;font-weight:600;letter-spacing:.09em;
text-transform:uppercase;color:#5A5F6B}
.ld-cluster__list{list-style:none;margin:0;padding:0;display:grid;gap:1px;
background:#E6E6E1;border:1px solid #E6E6E1;border-radius:8px;overflow:hidden}
.ld-cluster__item{background:#FFFFFF}
.ld-cluster__link{display:flex;align-items:baseline;justify-content:space-between;gap:16px;
padding:14px 16px;text-decoration:none;transition:background .15s ease}
.ld-cluster__link:hover,.ld-cluster__link:focus{background:#F8F8F6}
.ld-cluster__link:focus-visible{outline:3px solid #F97316;outline-offset:-3px}
.ld-cluster__name{color:#14213D;font-size:15px;font-weight:600;line-height:1.4}
.ld-cluster__link:hover .ld-cluster__name{color:#EA670B}
.ld-cluster__meta{flex:0 0 auto;color:#5A5F6B;font-size:12px;
font-variant-numeric:tabular-nums}
@media (max-width:520px){
	.ld-cluster__link{flex-direction:column;gap:4px}
	.ld-cluster__name{font-size:14.5px}
}
</style>
		<?php
	},
	20
);
