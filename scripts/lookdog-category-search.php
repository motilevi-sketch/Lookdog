<?php
/**
 * LookDog - product search, scoped to the category you are already standing in.
 *
 * The only search on the site was the WooCommerce product-search block in the
 * shop sidebar. Two problems with it: it searches all 167 products regardless
 * of where you are, so picking "Beds & Comfort" and then searching threw you
 * straight back out of the category; and on a phone the sidebar drops below
 * the product grid, so by the time you reach it you have already scrolled past
 * everything you might have wanted to search.
 *
 * This puts the box directly under the category description, above the first
 * row of products, and keeps the current category as the search scope. There
 * is always a one-click way out to an unscoped search, because a scoped search
 * that finds nothing is a dead end otherwise.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-category-search.php
 *
 * Renders on: the shop page, product category archives, product tag archives
 * (the "shop by problem" pages), and product search results.
 */

/**
 * Which taxonomy term, if any, the current view is scoped to.
 *
 * On an archive this is the queried term. On a search results page the term is
 * not the queried object, so it has to be read back out of the query vars that
 * the form itself submitted.
 *
 * @return array{taxonomy:string,term:?WP_Term}
 */
function lookdog_search_scope() {
	$none = array(
		'taxonomy' => '',
		'term'     => null,
	);

	if ( is_product_category() || is_product_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			return array(
				'taxonomy' => $term->taxonomy,
				'term'     => $term,
			);
		}
		return $none;
	}

	if ( is_search() ) {
		foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
			$slug = get_query_var( $taxonomy );
			if ( ! is_string( $slug ) || '' === $slug ) {
				continue;
			}
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term instanceof WP_Term ) {
				return array(
					'taxonomy' => $taxonomy,
					'term'     => $term,
				);
			}
		}
	}

	return $none;
}

/**
 * True on the views that should carry a search box.
 *
 * @return bool
 */
function lookdog_search_is_shop_view() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}
	if ( is_shop() || is_product_taxonomy() ) {
		return true;
	}
	return is_search() && 'product' === get_query_var( 'post_type' );
}

/**
 * Total published products, cached for a day. Used in the copy so the number
 * never has to be edited by hand when the catalogue grows.
 *
 * @return int
 */
function lookdog_search_product_count() {
	$count = get_transient( 'lookdog_product_count' );
	if ( false === $count ) {
		$count = (int) wp_count_posts( 'product' )->publish;
		set_transient( 'lookdog_product_count', $count, DAY_IN_SECONDS );
	}
	return (int) $count;
}

/**
 * The unscoped version of the current search, for the escape hatch link.
 *
 * @param string $query Search term.
 * @return string
 */
function lookdog_search_all_url( $query ) {
	return add_query_arg(
		array(
			's'         => rawurlencode( $query ),
			'post_type' => 'product',
		),
		home_url( '/' )
	);
}

/**
 * The search bar itself.
 *
 * @return void
 */
function lookdog_category_search_box() {
	static $done = false;
	if ( $done || ! lookdog_search_is_shop_view() ) {
		return;
	}
	$done = true;

	$scope    = lookdog_search_scope();
	$term     = $scope['term'];
	$query    = is_search() ? get_search_query() : '';
	$total    = lookdog_search_product_count();
	$in_scope = $term instanceof WP_Term ? (int) $term->count : $total;

	if ( $term instanceof WP_Term ) {
		/* translators: 1: number of products, 2: category name. */
		$placeholder = sprintf( __( 'Search %1$d products in %2$s', 'lookdog' ), $in_scope, $term->name );
	} else {
		/* translators: %d: number of products. */
		$placeholder = sprintf( __( 'Search all %d products', 'lookdog' ), $total );
	}

	lookdog_category_search_styles();
	?>
<div class="ld-search">
	<form class="ld-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="ld-search-input"><?php echo esc_html( $placeholder ); ?></label>
		<span class="ld-search__field">
			<svg class="ld-search__icon" width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
				<circle cx="8.5" cy="8.5" r="6" stroke="currentColor" stroke-width="2"/>
				<path d="M13 13l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
			<input class="ld-search__input" id="ld-search-input" type="search" name="s"
				value="<?php echo esc_attr( $query ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				autocomplete="off" />
		</span>
		<input type="hidden" name="post_type" value="product" />
		<?php if ( $term instanceof WP_Term ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $scope['taxonomy'] ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" />
		<?php endif; ?>
		<button class="ld-search__btn" type="submit"><?php esc_html_e( 'Search', 'lookdog' ); ?></button>
	</form>
	<?php
	$note = lookdog_category_search_note( $term, $query, $in_scope, $total );
	if ( '' !== $note ) :
		?>
		<p class="ld-search__note"><?php echo wp_kses_post( $note ); ?></p>
	<?php endif; ?>
</div>
	<?php
}
add_action( 'woocommerce_archive_description', 'lookdog_category_search_box', 20 );

/**
 * The line under the box: what is being searched, and the way back out.
 *
 * @param WP_Term|null $term     Scope term, if any.
 * @param string       $query    Current search term.
 * @param int          $in_scope Products inside the scope.
 * @param int          $total    Products in the catalogue.
 * @return string
 */
function lookdog_category_search_note( $term, $query, $in_scope, $total ) {
	// Not searching yet: say what the box will and will not cover.
	if ( '' === $query ) {
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		return sprintf(
			/* translators: 1: category name, 2: number of products in it. */
			esc_html__( 'Searches %1$s only — %2$d products.', 'lookdog' ),
			'<strong>' . esc_html( $term->name ) . '</strong>',
			(int) $in_scope
		);
	}

	$found = isset( $GLOBALS['wp_query'] ) ? (int) $GLOBALS['wp_query']->found_posts : 0;

	// A miss is answered by the empty-state paragraph below the box, which says
	// the same thing and offers the way out. Saying "0 results" twice is noise.
	if ( 0 === $found ) {
		return '';
	}

	/* translators: %d: number of matching products. */
	$results = sprintf( _n( '%d result', '%d results', $found, 'lookdog' ), $found );

	if ( ! $term instanceof WP_Term ) {
		return sprintf(
			/* translators: 1: result count, 2: search term, 3: catalogue size. */
			esc_html__( '%1$s for %2$s across all %3$d products.', 'lookdog' ),
			'<strong>' . esc_html( $results ) . '</strong>',
			'&ldquo;' . esc_html( $query ) . '&rdquo;',
			(int) $total
		);
	}

	$term_link = get_term_link( $term );
	$term_link = is_wp_error( $term_link ) ? home_url( '/' ) : $term_link;

	return sprintf(
		/* translators: 1: result count, 2: search term, 3: category name, 4: link to search everything, 5: link back to the category. */
		esc_html__( '%1$s for %2$s in %3$s. %4$s or %5$s.', 'lookdog' ),
		'<strong>' . esc_html( $results ) . '</strong>',
		'&ldquo;' . esc_html( $query ) . '&rdquo;',
		esc_html( $term->name ),
		'<a href="' . esc_url( lookdog_search_all_url( $query ) ) . '">' . esc_html__( 'Search every category', 'lookdog' ) . '</a>',
		'<a href="' . esc_url( $term_link ) . '">' . esc_html__( 'clear the search', 'lookdog' ) . '</a>'
	);
}

/**
 * Nothing matched. WooCommerce's own notice says only that, which leaves a
 * scoped search as a dead end, so add the two ways forward.
 *
 * @return void
 */
function lookdog_category_search_empty() {
	if ( ! is_search() ) {
		return;
	}
	$query = get_search_query();
	if ( '' === $query ) {
		return;
	}
	$scope = lookdog_search_scope();
	$term  = $scope['term'];
	?>
<p class="ld-search__empty">
	<?php if ( $term instanceof WP_Term ) : ?>
		<?php
		$term_link = get_term_link( $term );
		$term_link = is_wp_error( $term_link ) ? home_url( '/' ) : $term_link;
		printf(
			/* translators: 1: search term, 2: category name, 3: link to search everything, 4: link back to the category. */
			esc_html__( 'Nothing in %2$s matches %1$s. %3$s, or %4$s.', 'lookdog' ),
			'&ldquo;' . esc_html( $query ) . '&rdquo;',
			'<strong>' . esc_html( $term->name ) . '</strong>',
			'<a href="' . esc_url( lookdog_search_all_url( $query ) ) . '">' . esc_html__( 'Search every category', 'lookdog' ) . '</a>',
			'<a href="' . esc_url( $term_link ) . '">' . esc_html__( 'go back to the full category', 'lookdog' ) . '</a>'
		);
		?>
	<?php else : ?>
		<?php
		printf(
			/* translators: %s: search term. */
			esc_html__( 'Nothing matches %s. One plain word usually works better here than a phrase — try bed, harness, brush or tracker.', 'lookdog' ),
			'&ldquo;' . esc_html( $query ) . '&rdquo;'
		);
		?>
	<?php endif; ?>
</p>
	<?php
}
add_action( 'woocommerce_no_products_found', 'lookdog_category_search_empty', 20 );

/**
 * Styles. Printed inline with the box so they only ever load on shop views,
 * and only once per request.
 *
 * @return void
 */
function lookdog_category_search_styles() {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	?>
<style id="lookdog-search-css">
.ld-search{margin:22px 0 30px;}
.ld-search__form{
	display:flex;
	gap:10px;
	align-items:stretch;
	max-width:640px;
}
.ld-search__field{
	position:relative;
	display:flex;
	align-items:center;
	flex:1 1 auto;
	min-width:0;
}
.ld-search__icon{
	position:absolute;
	left:14px;
	color:#8A8F9B;
	pointer-events:none;
}
/*
 * Astra styles input[type=search] and input[type=search]:focus, which are more
 * specific than a bare class and therefore win no matter what order the sheets
 * load in. Without the element selector here Astra resets the padding to .75em
 * (putting the text under the magnifier), the radius to 2px, the text to #666
 * and the focus border to thin dotted.
 */
.ld-search input.ld-search__input{
	width:100%;
	margin:0;
	padding:12px 14px 12px 40px;
	height:auto;
	font-size:15px;
	line-height:1.4;
	color:#14213D;
	background:#FFFFFF;
	border:1px solid #D8D8D2;
	border-radius:6px;
	box-shadow:none;
	box-sizing:border-box;
	-webkit-appearance:none;
	appearance:none;
}
.ld-search input.ld-search__input::placeholder{color:#8A8F9B;opacity:1;}
.ld-search input.ld-search__input:focus{
	color:#14213D;
	background:#FFFFFF;
	border:1px solid #F97316;
	outline:none;
	box-shadow:0 0 0 3px rgba(249,115,22,.22);
}
.ld-search button.ld-search__btn{
	flex:0 0 auto;
	margin:0;
	padding:12px 26px;
	font-size:13px;
	font-weight:600;
	letter-spacing:.3px;
	line-height:1.4;
	color:#FFFFFF;
	background:#F97316;
	border:0;
	border-radius:6px;
	cursor:pointer;
}
.ld-search button.ld-search__btn:hover,
.ld-search button.ld-search__btn:focus{background:#EA670B;color:#FFFFFF;}
.ld-search button.ld-search__btn:focus-visible{outline:3px solid rgba(20,33,61,.35);outline-offset:2px;}
.ld-search__note,
.ld-search__empty{
	margin:10px 0 0;
	font-size:14px;
	line-height:1.55;
	color:#5A5F6B;
}
.ld-search__note strong{color:#14213D;}
.ld-search__empty{margin:0 0 24px;max-width:640px;}
.ld-search__note a,
.ld-search__empty a{color:#14213D;text-decoration:underline;}
.ld-search__note a:hover,
.ld-search__empty a:hover{color:#F97316;}
@media (max-width:600px){
	.ld-search__form{flex-wrap:wrap;}
	.ld-search__field{flex:1 1 100%;}
	.ld-search button.ld-search__btn{flex:1 1 100%;}
}
</style>
	<?php
}
