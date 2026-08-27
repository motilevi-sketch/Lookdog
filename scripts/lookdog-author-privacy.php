<?php
/**
 * LookDog - keep the owner's email address off the public site.
 *
 * The WordPress account was created with the owner's email as its login, and
 * WordPress used that address as the display name too. That put it in the byline
 * of every article and in the "View all posts by" title attribute, and the author
 * archive lived at /author/motilevigmail-com/ - a thin disguise.
 *
 * The account is fixed at source: display_name and nickname are "LookDog" and the
 * nicename is "lookdog". user_login is deliberately left alone; it is the login
 * credential and is not rendered anywhere public.
 *
 * SureRank is the remaining leak, and the worst kind. It builds a Person node for
 * the JSON-LD from the WP_User object directly, so it bypasses the
 * get_the_author_* filters, and it publishes the address as machine-readable
 * structured data - which is easier for a harvester to read than a byline.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-author-privacy.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip the email from the author and user records SureRank assembles for schema.
 *
 * Only the schema payload is touched. Nothing here affects wp_mail, the admin,
 * comment notifications or password resets, all of which read the user record
 * rather than this filtered array.
 */
add_filter( 'surerank_schema_data', static function ( $data ) {
	foreach ( array( 'author', 'user' ) as $key ) {
		if ( isset( $data[ $key ]['email'] ) ) {
			$data[ $key ]['email'] = '';
		}
	}
	return $data;
}, 20 );

/**
 * Belt and braces for anything else that renders the author's address on the
 * front end. Frontend only, so admin screens keep showing the real value.
 */
foreach ( array( 'get_the_author_user_email', 'get_the_author_email' ) as $lookdog_author_hook ) {
	add_filter( $lookdog_author_hook, static function ( $email ) {
		return is_admin() ? $email : '';
	}, 20 );
}
unset( $lookdog_author_hook );

/** A login address is not a display name, whatever the account was set up with. */
add_filter( 'the_author', static function ( $name ) {
	return is_email( $name ) ? 'LookDog' : $name;
}, 20 );
