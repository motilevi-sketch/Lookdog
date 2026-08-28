<?php
/**
 * LookDog - per-guide accent colour and header photo strip.
 *
 * Two things the guides were missing: photographs, and any colour of their own.
 *
 * COLOUR. The site rule is one accent, ember. That still holds per *page* - what
 * changed is that a guide may substitute its own. One accent per page, never two
 * on the same page, so each article is a coherent colour world and the ember
 * still owns the homepage, the products and the nav. The six were picked to sit
 * with the navy rather than to be six bright colours: all mid-dark, all at least
 * 4.5:1 on white so headings and links stay readable.
 *
 * PHOTOS. There are only three lifestyle photographs in the library but roughly a
 * thousand product shots, so the strip is built from the actual products each
 * guide discusses. That is honest - they are the things being written about - and
 * it is the only source that can never go stale or off-topic.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-article-photos.php
 */

defined( 'ABSPATH' ) || exit;

/** style key => [ accent, darker accent for hover/links ] */
function lookdog_article_accents() {
	return apply_filters( 'lookdog_article_accents', array(
		'ledger'   => array( '#B45309', '#92400E' ), // amber; food and warmth
		'field'    => array( '#B91C1C', '#991B1B' ), // signal red; this guide is about hazards
		'sequence' => array( '#0F766E', '#115E59' ), // teal; water and cleaning
		'bulletin' => array( '#0369A1', '#075985' ), // signal blue; roads and notices
		'calm'     => array( '#4F6152', '#3F4E42' ), // sage; the subject is rest
		'spec'     => array( '#334155', '#1E293B' ), // slate; a technical document
	) );
}

/** post id => product ids whose photographs open the guide. */
function lookdog_article_photo_map() {
	return apply_filters( 'lookdog_article_photos', array(
		3777 => array( 3792, 3390, 3834 ), // puzzle bowl, elevated feeder, storage
		3344 => array( 3427, 3448, 3553 ), // rolling ball, octopus, suction tug
		4497 => array( 3897, 3869, 4334 ), // dematting comb, nail grinder, towel
		4498 => array( 4390, 4404, 3960 ), // harness, retractable lead, car tether
		4499 => array( 3658, 4473, 3602 ), // cooling mat, ice-silk bed, plush bed
		4500 => array( 4142, 4216, 4181 ), // tracker collar, LED collar, launcher
	) );
}

function lookdog_article_photos( $post_id ) {
	$stored = (string) get_post_meta( $post_id, 'lookdog_article_photos', true );
	if ( '' !== $stored ) {
		return array_filter( array_map( 'absint', explode( ',', $stored ) ) );
	}
	$map = lookdog_article_photo_map();
	return isset( $map[ $post_id ] ) ? $map[ $post_id ] : array();
}

/** Swap the accent tokens for this guide. Everything else already reads them. */
add_action( 'wp_head', static function () {
	if ( ! function_exists( 'lookdog_is_guide' ) || ! lookdog_is_guide() ) {
		return;
	}
	$accents = lookdog_article_accents();
	$style   = lookdog_article_style( get_queried_object_id() );
	if ( empty( $accents[ $style ] ) ) {
		return;
	}
	list( $accent, $dark ) = $accents[ $style ];
	?>
<style id="lookdog-article-accent">
.ld-art{--accent:<?php echo esc_html( $accent ); ?>;--accent-dark:<?php echo esc_html( $dark ); ?>}
.ld-art .entry-content a{text-decoration-color:<?php echo esc_html( $accent ); ?>80}
.ld-art .ld-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:0 0 34px}
.ld-art .ld-strip img{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;
border-radius:3px;background:var(--surface2)}
.ld-art .ld-strip__cap{grid-column:1/-1;margin:2px 0 0;font-size:12px;letter-spacing:.07em;
text-transform:uppercase;color:var(--muted)}
@media (max-width:520px){.ld-art .ld-strip{gap:7px}}
</style>
	<?php
}, 23 );

/** The strip sits above the title, so it introduces the guide rather than interrupting it. */
add_action( 'astra_entry_top', static function () {
	if ( ! function_exists( 'lookdog_is_guide' ) || ! lookdog_is_guide() ) {
		return;
	}
	$ids = lookdog_article_photos( get_the_ID() );
	if ( count( $ids ) < 3 ) {
		return;
	}

	$out = '';
	foreach ( array_slice( $ids, 0, 3 ) as $pid ) {
		$thumb = get_post_thumbnail_id( $pid );
		if ( ! $thumb ) {
			continue;
		}
		$out .= '<a href="' . esc_url( (string) get_permalink( $pid ) ) . '">'
			. wp_get_attachment_image( $thumb, 'medium_large', false, array(
				'alt'     => get_the_title( $pid ),
				'loading' => 'eager',
			) )
			. '</a>';
	}
	if ( '' === $out ) {
		return;
	}

	echo '<div class="ld-strip">' . $out // phpcs:ignore
		. '<p class="ld-strip__cap">Products covered in this guide</p></div>';
}, 5 );
