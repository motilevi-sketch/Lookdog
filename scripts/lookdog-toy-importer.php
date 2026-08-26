<?php
/**
 * LookDog - Dog Toys importer.
 * Creates WooCommerce external (affiliate) products from cached AliExpress
 * detail data stored in the `lookdog_toy_details` option.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-toy-importer.php
 * Requires:    wp-content/mu-plugins/lookdog-aliexpress.php (API client)
 */

function lookdog_toy_import_images( $urls, $post_id = 0, $alt = '' ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$ids = array();
	foreach ( (array) $urls as $i => $url ) {
		if ( ! $url ) {
			continue;
		}
		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'https:' . $url;
		}
		$ext = ( false !== stripos( parse_url( $url, PHP_URL_PATH ), '.png' ) ) ? 'png' : 'jpg';
		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			continue;
		}
		$file = array(
			'name'     => 'lookdog-' . ( $post_id ? $post_id . '-' : '' ) . ( $i + 1 ) . '.' . $ext,
			'tmp_name' => $tmp,
		);
		$aid = media_handle_sideload( $file, $post_id );
		if ( is_wp_error( $aid ) ) {
			@unlink( $tmp );
			continue;
		}
		if ( $alt ) {
			update_post_meta( $aid, '_wp_attachment_image_alt', $alt );
		}
		$ids[] = $aid;
	}
	return $ids;
}

function lookdog_toy_find_existing( $ae_id ) {
	$q = get_posts( array(
		'post_type'   => 'product',
		'post_status' => 'any',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => '_lookdog_ae_id',
		'meta_value'  => (string) $ae_id,
	) );
	return $q ? $q[0] : 0;
}

/**
 * $d expects: ae_id, title, slug, short, intro, benefits[], best_for[], how,
 * pros, cons, consider[], seo_title, seo_desc.
 * Optional: cat_id (product_cat term id; defaults to 68 = Dog Toys).
 */
function lookdog_toy_create( $d ) {
	$details = get_option( 'lookdog_toy_details', array() );
	$ae_id   = (string) $d['ae_id'];
	if ( empty( $details[ $ae_id ] ) ) {
		return array( 'ae_id' => $ae_id, 'status' => 'error', 'msg' => 'no cached detail' );
	}
	$src = $details[ $ae_id ];
	if ( empty( $src['promo'] ) ) {
		return array( 'ae_id' => $ae_id, 'status' => 'error', 'msg' => 'no affiliate link' );
	}
	$existing = lookdog_toy_find_existing( $ae_id );
	if ( $existing ) {
		return array( 'ae_id' => $ae_id, 'status' => 'skipped', 'post_id' => $existing, 'msg' => 'already imported' );
	}

	$notice = '<p><em>Affiliate notice: LookDog may earn a commission if you purchase through this link, at no additional cost to you.</em></p>';

	$li = function( $items ) {
		$o = '';
		foreach ( $items as $i ) {
			$o .= '<li>' . $i . '</li>';
		}
		return $o;
	};

	$consider   = $d['consider'];
	$consider[] = 'Prices, availability and shipping times are set by the AliExpress seller and can change';

	$content  = '<p>' . $d['intro'] . '</p>';
	$content .= '<h2>Key Benefits</h2><ul>' . $li( $d['benefits'] ) . '</ul>';
	$content .= '<h2>Best For</h2><ul>' . $li( $d['best_for'] ) . '</ul>';
	$content .= '<h2>How It Works</h2><p>' . $d['how'] . '</p>';
	$content .= '<h2>Pros and Cons</h2><p><strong>Pros:</strong> ' . $d['pros'] . '</p>';
	$content .= '<p><strong>Cons:</strong> ' . $d['cons'] . '</p>';
	$content .= '<h2>Things to Consider</h2><ul>' . $li( $consider ) . '</ul>';
	$content .= $notice;

	$excerpt = '<p>' . $d['short'] . '</p>' . $notice;

	$post_id = wp_insert_post( array(
		'post_type'    => 'product',
		'post_status'  => 'publish',
		'post_title'   => $d['title'],
		'post_name'    => $d['slug'],
		'post_content' => $content,
		'post_excerpt' => $excerpt,
	), true );

	if ( is_wp_error( $post_id ) ) {
		return array( 'ae_id' => $ae_id, 'status' => 'error', 'msg' => $post_id->get_error_message() );
	}

	$cat_id = isset( $d['cat_id'] ) ? (int) $d['cat_id'] : 68;
	wp_set_object_terms( $post_id, array( $cat_id ), 'product_cat' );
	wp_set_object_terms( $post_id, 'external', 'product_type' );

	update_post_meta( $post_id, '_product_url', $src['promo'] );
	update_post_meta( $post_id, '_button_text', 'Check Price on AliExpress' );
	update_post_meta( $post_id, '_virtual', 'yes' );
	update_post_meta( $post_id, '_manage_stock', 'no' );
	update_post_meta( $post_id, '_stock_status', 'instock' );
	update_post_meta( $post_id, '_lookdog_ae_id', $ae_id );
	// SureRank reads per-post SEO from ONE serialized array under this key.
	// Writing separate surerank_settings_page_title / _page_description keys does
	// nothing: SureRank never reads them and every page silently falls back to the
	// default "%title% - %site_name%" template.
	update_post_meta(
		$post_id,
		'surerank_settings_general',
		array(
			'page_title'                => $d['seo_title'],
			'page_description'          => $d['seo_desc'],
			'auto_generate_description' => false,
		)
	);

	$urls = array();
	if ( ! empty( $src['imgs'] ) ) {
		$urls = array_slice( (array) $src['imgs'], 0, 6 );
	} elseif ( ! empty( $src['main'] ) ) {
		$urls = array( $src['main'] );
	}
	$att = lookdog_toy_import_images( $urls, $post_id, $d['title'] );
	if ( $att ) {
		set_post_thumbnail( $post_id, $att[0] );
		if ( count( $att ) > 1 ) {
			update_post_meta( $post_id, '_product_image_gallery', implode( ',', array_slice( $att, 1 ) ) );
		}
	}

	return array(
		'ae_id'   => $ae_id,
		'status'  => 'created',
		'post_id' => $post_id,
		'images'  => count( $att ),
		'url'     => get_permalink( $post_id ),
	);
}
