<?php
/**
 * LookDog - let the photo at the top of a guide say something.
 *
 * WHY THIS EXISTS. Every guide on the site opened with a supplier's product
 * photograph: a clipper on a white background, a hammock on a white background.
 * They were the featured image because something had to be, not because anyone
 * chose them, and a reader could tell.
 *
 * Five guides now open with a photograph of the author's own dog instead. That
 * only earns its place if it carries a caption, because the value of the photo
 * is not that it is prettier - it is that it is evidence, and evidence needs a
 * sentence saying what it shows. The play guide is the clearest case: it opens
 * on a dog holding a tennis ball, and the guide below it warns against tennis
 * balls. Without the caption that reads as carelessness. With it, it reads as
 * the honest thing the piece is actually about.
 *
 * Astra prints the featured image and stops. WordPress stores the caption on
 * the attachment (post_excerpt) and nothing renders it. So this filters
 * post_thumbnail_html and appends the caption under the image.
 *
 * DELIBERATELY NARROW. Single posts only, only the queried post, and only when
 * a caption exists. Archives, the shop, product galleries and every other
 * the_post_thumbnail() call in the theme pass through untouched - the filter
 * runs on all of them, so the guard matters more than the feature.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-guide-photo.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wrap the featured image in a figure and print its caption beneath it.
 *
 * @param string $html         Thumbnail markup, or '' when there is no thumbnail.
 * @param int    $post_id      Post the thumbnail belongs to.
 * @param int    $thumbnail_id Attachment id.
 * @return string
 */
function lookdog_guide_photo_caption( $html, $post_id, $thumbnail_id ) {
	if ( '' === $html || is_admin() || is_feed() ) {
		return $html;
	}

	// Only the post being read. Astra calls this for related posts and archive
	// cards too, and a caption under a 150px card is noise.
	if ( ! is_singular( 'post' ) || (int) $post_id !== (int) get_queried_object_id() ) {
		return $html;
	}

	$caption = wp_get_attachment_caption( (int) $thumbnail_id );
	if ( ! $caption ) {
		return $html;
	}

	return '<figure class="ld-photo">' . $html
		. '<figcaption class="ld-photo__caption">' . wp_kses_post( $caption ) . '</figcaption>'
		. '</figure>';
}
add_filter( 'post_thumbnail_html', 'lookdog_guide_photo_caption', 10, 3 );

add_action(
	'wp_head',
	static function () {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$thumb = get_post_thumbnail_id( get_queried_object_id() );
		if ( ! $thumb || ! wp_get_attachment_caption( $thumb ) ) {
			return;
		}
		?>
<style id="lookdog-guide-photo">
.ld-photo{margin:0;padding:0}
.ld-photo img{display:block;width:100%;height:auto;border-radius:8px}
.ld-photo__caption{margin:10px 0 0;padding:0;font-family:Poppins,sans-serif;
font-size:13.5px;line-height:1.55;color:#5A5F6B;max-width:62ch}
@media (max-width:520px){.ld-photo__caption{font-size:13px}}
</style>
		<?php
	},
	20
);
