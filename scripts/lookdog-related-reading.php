<?php
/**
 * LookDog - "further reading" link from a product to the guide that covers it.
 *
 * Product pages had no editorial outbound links at all: WooCommerce's related
 * products block sends people sideways to more products, and the header nav is
 * the same on every page. This adds the one link that answers the question a
 * reader actually has on a product page, which is whether they need the thing.
 *
 * Deliberately partial. A category only appears here when a guide genuinely
 * covers it; the rest get nothing rather than a link to something loosely
 * related, which is worth less than no link and reads as filler.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-related-reading.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * product_cat term id => [ post id, the sentence that earns the click ].
 * Add a row here when a new guide lands.
 */
function lookdog_reading_map() {
	return apply_filters( 'lookdog_reading_map', array(
		70 => array( 3777, 'How much to feed, what is never safe, and why the same food is riskier for a small dog.' ),
		68 => array( 3344, 'Sizing a toy safely, playing tug without the risks, and when a toy has to go in the bin.' ),
		71 => array( 4497, 'Which tools your dog\'s coat actually needs, and the brushing order that stops mats forming.' ),
		69 => array( 4498, 'What restrains a dog in a car and what only contains one, plus fixing travel sickness early.' ),
		74 => array( 4499, 'Sizing a bed properly, testing whether the foam supports, and which cooling mats work.' ),
		72 => array( 4500, 'Bluetooth tags versus GPS, why neither replaces a microchip, and what the training tech does.' ),
	) );
}

/**
 * product_tag slug => [ post id, the sentence that earns the click ].
 *
 * Preferred over the category map below, because it is far more specific. A
 * reader looking at a rope toy in Dog Toys is better served by "how do I stop
 * my dog chewing everything" than by the general toy-safety guide: it answers
 * the question that brought them to a chew toy in the first place.
 */
function lookdog_problem_reading_map() {
	return apply_filters( 'lookdog_problem_reading_map', array(
		'pulls-on-the-lead'   => array( 5027, 'Pulling is a habit your dog was paid for thousands of times. What actually stops it, and what a harness does not do.' ),
		'chews-everything'    => array( 5028, 'Teething and boredom look identical and need opposite answers. The thumbnail test, and when it is not chewing at all.' ),
		'eats-too-fast'       => array( 5029, 'Gulping draws air in, and in deep-chested breeds that carries a real risk. What slows a dog down, and what a bowl cannot do.' ),
		'sheds-everywhere'    => array( 5030, 'You cannot stop shedding, only move where the hair lands. Which tool suits which coat, and which ones cause damage.' ),
		'gets-too-hot'        => array( 5031, 'The seven-second pavement test, why humidity beats temperature for danger, and the heatstroke signs to know.' ),
		'barks-too-much'      => array( 5032, 'Six different barks with six different fixes, and an honest account of where deterrents help and where they harm.' ),
		'runs-off'            => array( 5033, 'Why recall fails, and the difference between a Bluetooth tag and GPS — one of them is useless in woodland.' ),
		'walking-in-the-dark' => array( 5034, 'A driver on dipped beams cannot stop inside what they can see. What that means for what you buy.' ),
		'hates-the-car'       => array( 5035, 'Telling sickness from fear, and why a seatbelt tether is a restraint rather than crash protection.' ),
		'bad-breath'          => array( 5036, 'Bad breath is gum disease, not hygiene. Why brushing is the only thing that reliably works.' ),
		'puppy'               => array( 4524, 'What a puppy actually needs in the first six months, and when — rather than the thirty things you will be sold.' ),
		'clipping-at-home'    => array( 5252, 'Almost every bad home haircut is a step done out of order, not a bad tool. Where the dog stands, blade heat, and the face and feet.' ),
		'overgrown-nails'     => array( 4497, 'Why nails should never touch the floor, and the little-and-often trim that fixes a long nail one big cut cannot.' ),
		'wont-hold-still-for-bath' => array( 5289, 'Slipping and fear look the same in a wet dog but need opposite fixes, and holding tighter only helps one of them.' ),
		'coat-mats'           => array( 5296, 'Mats start in the same few places, and each has its own cause. The comb test that finds them, and why bathing a matted dog makes it worse.' ),
		'carrying-a-small-dog' => array( 5298, 'Pick the carrier for the trip, not the look: where each format puts the weight on you, and how to size one by measurement rather than the label.' ),
	) );
}

/**
 * Comparison article => the products it puts side by side.
 *
 * The most specific link of the three. Somebody looking at one slow feeder has
 * already decided they want a slow feeder; what they do not know is that the
 * next three bowls along are the same object, or that a lick mat would suit
 * their dog better. That is worth more to them than the category guide and more
 * than the problem article, so it is checked first.
 *
 * Written this way round - article to products - because that is how a
 * comparison is edited: add a row to the table, add the id here.
 */
function lookdog_compare_reading_map() {
	return apply_filters( 'lookdog_compare_reading_map', array(
		5089 => array(
			'blurb'    => 'Three different products are sold as trackers. What a Bluetooth tag cannot do, and the price test that catches a mislabelled one.',
			'products' => array( 4065, 4072, 4079, 4086, 4142, 4149, 4900, 4914 ),
		),
		5090 => array(
			'blurb'    => 'Ridged bowls, lick mats, snuffle mats and balls do different jobs. Which suits dry food, which suits wet, and which a flat-faced dog cannot use.',
			'products' => array( 3419, 3525, 3792, 3799, 3813, 3855, 4209, 4285, 4466, 4697, 4718, 4732, 4760, 4767 ),
		),
		5091 => array(
			'blurb'    => 'None of them refrigerate anything. How long each kind stays cool, and which one is still working in hour six.',
			'products' => array( 3658, 3665, 3672, 3679, 4536, 4543, 4550, 5005 ),
		),
		5092 => array(
			'blurb'    => 'Where the lead clips changes what happens when your dog leans into it. Front, back, fit, and what a car tether does not do.',
			'products' => array( 3981, 4390, 4397, 4851, 4942, 4949 ),
		),
		5098 => array(
			'blurb'    => 'A booster seat, a hammock and a tether solve three different problems. Which one actually restrains a dog, and which just protects the seats.',
			'products' => array( 3375, 4452, 3995, 3988, 3981, 3974, 3967, 3960, 4432, 4445 ),
		),
		5099 => array(
			'blurb'    => 'Sinking foam, summer heat, winter draughts and a dog that wants walls around them are four different problems. Which bed actually fixes which one.',
			'products' => array( 3623, 3602, 3679, 3658, 3672, 4606, 4592, 3616, 3644, 3630 ),
		),
		5100 => array(
			'blurb'    => 'A brush that works on a double coat does nothing on a silky one. Deshedding tools, dematting combs and nail clippers versus a grinder, matched to the coat.',
			'products' => array( 3904, 3316, 4383, 3890, 3897, 4837, 3918, 3337, 3876, 3869 ),
		),
		5101 => array(
			'blurb'    => 'None of these are built for a walk. Which muzzle suits a vet visit, a flat-faced breed or a dog that has never worn one, and why duration matters more than the shape.',
			'products' => array( 4872, 4886, 4879, 4893 ),
		),
	) );
}

function lookdog_reading_for_product( $post_id ) {
	// A comparison that names this exact product beats everything else.
	foreach ( lookdog_compare_reading_map() as $article_id => $row ) {
		if ( ! in_array( (int) $post_id, $row['products'], true ) ) {
			continue;
		}
		if ( 'publish' !== get_post_status( $article_id ) ) {
			continue;
		}
		return array(
			'url'   => get_permalink( $article_id ),
			'title' => get_the_title( $article_id ),
			'blurb' => $row['blurb'],
		);
	}

	// The problem the product solves beats the category it sits in.
	$problems = lookdog_problem_reading_map();
	$tags     = wp_get_object_terms( $post_id, 'product_tag', array( 'fields' => 'slugs' ) );
	if ( ! is_wp_error( $tags ) ) {
		foreach ( $tags as $slug ) {
			if ( empty( $problems[ $slug ] ) ) {
				continue;
			}
			list( $article_id, $blurb ) = $problems[ $slug ];
			if ( 'publish' !== get_post_status( $article_id ) ) {
				continue;
			}
			return array(
				'url'   => get_permalink( $article_id ),
				'title' => get_the_title( $article_id ),
				'blurb' => $blurb,
			);
		}
	}

	$map   = lookdog_reading_map();
	$terms = wp_get_object_terms( $post_id, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term_id ) {
		if ( empty( $map[ $term_id ] ) ) {
			continue;
		}
		list( $guide_id, $blurb ) = $map[ $term_id ];
		if ( 'publish' !== get_post_status( $guide_id ) ) {
			continue;
		}
		return array(
			'url'   => get_permalink( $guide_id ),
			'title' => get_the_title( $guide_id ),
			'blurb' => $blurb,
		);
	}
	return null;
}

add_filter( 'the_content', static function ( $content ) {
	if ( ! is_singular( 'product' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$guide = lookdog_reading_for_product( get_the_ID() );
	if ( ! $guide ) {
		return $content;
	}

	$block  = '<aside class="ld-reading">';
	$block .= '<p class="ld-reading__label">Before you buy</p>';
	$block .= '<p class="ld-reading__title"><a href="' . esc_url( $guide['url'] ) . '">' . esc_html( $guide['title'] ) . '</a></p>';
	$block .= '<p class="ld-reading__blurb">' . esc_html( $guide['blurb'] ) . '</p>';
	$block .= '</aside>';

	return $content . $block;
}, 25 );

add_action( 'wp_head', static function () {
	if ( ! is_singular( 'product' ) || ! lookdog_reading_for_product( get_queried_object_id() ) ) {
		return;
	}
	?>
<style id="lookdog-reading">
.ld-reading{margin:34px 0 0;padding:20px 0 0;border-top:1px solid #E6E6E1;font-family:Poppins,sans-serif}
.ld-reading__label{margin:0;font-size:11px;font-weight:600;letter-spacing:.09em;
text-transform:uppercase;color:#5A5F6B}
.ld-reading__title{margin:8px 0 0;font-size:18px;font-weight:600;line-height:1.3}
.ld-reading__title a{color:#14213D;text-decoration:none;transition:color .15s ease}
.ld-reading__title a:hover{color:#EA670B}
.ld-reading__blurb{margin:6px 0 0;font-size:14px;line-height:1.55;color:#3A3F4B}
</style>
	<?php
}, 20 );
