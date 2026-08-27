<?php
/**
 * LookDog - stop SureRank rewriting image alt and title attributes.
 *
 * SureRank's Image_Seo runs on the_content and fills in alt and title on every
 * image, building them from the *page* title. On a homepage titled "Home" that
 * put title="Home" on all ten product photographs in the rail and on the seven
 * category cards.
 *
 * Turning it off is the right call here on three counts:
 *
 * 1. Every image this site renders already gets deliberate alt text at the point
 *    it is created - the product name on product images, the category name on
 *    category cards - set during import rather than guessed at render time.
 * 2. The image `title` attribute carries no SEO weight. Search engines read alt;
 *    title only produces a tooltip, and a tooltip reading "Home" over a photo of
 *    a dog lead is worse than no tooltip.
 * 3. It overwrote deliberate empty alt. The category rows on /start carry
 *    alt="" on purpose, because the category name sits immediately beside the
 *    thumbnail; filling those in makes a screen reader announce each row twice.
 *
 * If a future image genuinely lacks alt text, fix it at the source rather than
 * turning this back on - a generated alt built from the page title is not a
 * description of the image.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-image-attrs.php
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'surerank_auto_set_image_title_and_alt', '__return_false' );
