<?php
/**
 * Keep the XML sitemap index honest.
 *
 * SureRank caches the sitemap index as a JSON file and rebuilds it when content
 * changes. A plugin upgrade is not a content change, so an index written by an
 * older version can survive into a newer one and quietly keep serving the older
 * version's idea of what belongs in it. That is exactly what happened here: the
 * cached index listed pages and posts only, and 258 product pages had no sitemap
 * entry at all for as long as it stood.
 *
 * This rebuilds the index from live provider counts on a weekly schedule and
 * immediately after any plugin or theme update. It never writes URLs of its own
 * and never decides what is indexable; it only asks SureRank to answer that
 * question again with current data.
 *
 * @package lookdog
 */

defined( 'ABSPATH' ) || exit;

const LOOKDOG_SITEMAP_EVENT = 'lookdog_sitemap_reindex';

/**
 * The registry that assembles the index, when SureRank is active and current.
 *
 * @return object|null
 */
function lookdog_sitemap_registry() {
	$class = 'SureRank\\Inc\\Sitemap\\Providers\\Registry';

	if ( ! class_exists( $class ) || ! method_exists( $class, 'get_instance' ) ) {
		return null;
	}

	$registry = call_user_func( array( $class, 'get_instance' ) );

	return method_exists( $registry, 'build_index' ) ? $registry : null;
}

/**
 * Rebuild the cached sitemap index.
 *
 * @return void
 */
function lookdog_sitemap_reindex() {
	$registry = lookdog_sitemap_registry();

	if ( ! $registry ) {
		return;
	}

	try {
		$registry->build_index();
		update_option( 'lookdog_sitemap_reindexed', time(), false );
	} catch ( Throwable $e ) {
		// A failed rebuild leaves the previous index in place, which is the
		// safe outcome: a stale sitemap still serves, an empty one does not.
		return;
	}
}
add_action( LOOKDOG_SITEMAP_EVENT, 'lookdog_sitemap_reindex' );

/**
 * Schedule the weekly rebuild.
 *
 * @return void
 */
function lookdog_sitemap_schedule() {
	if ( ! wp_next_scheduled( LOOKDOG_SITEMAP_EVENT ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', LOOKDOG_SITEMAP_EVENT );
	}
}
add_action( 'init', 'lookdog_sitemap_schedule' );

/**
 * Rebuild straight after an update, which is the case the weekly run is too
 * slow for: an upgrade can change what belongs in the index the moment it lands.
 *
 * @return void
 */
function lookdog_sitemap_after_upgrade() {
	wp_schedule_single_event( time() + 30, LOOKDOG_SITEMAP_EVENT );
}
add_action( 'upgrader_process_complete', 'lookdog_sitemap_after_upgrade', 20 );
