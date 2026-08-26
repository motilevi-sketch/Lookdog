<?php
/**
 * LookDog - social profile identity.
 *
 * Adds sameAs to the Organization schema so Google can associate lookdog.club
 * with the brand's social profiles, and emits the matching Open Graph profile
 * tags. SureRank renders the Organization node but leaves sameAs empty unless
 * its onboarding flow was completed.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-social-schema.php
 * Add further profiles to lookdog_social_profiles() as accounts are created.
 */

/**
 * The canonical list of social profiles for this brand.
 *
 * @return string[]
 */
function lookdog_social_profiles() {
	$profiles = array(
		'instagram' => 'https://www.instagram.com/lookdog435/',
		// 'facebook' => '',
		// 'twitter'  => '',
		// 'linkedin' => '',
	);

	/**
	 * Filter the brand's social profile URLs.
	 *
	 * @param string[] $profiles Keyed by network.
	 */
	$profiles = apply_filters( 'lookdog_social_profiles', $profiles );

	return array_values( array_filter( array_map( 'trim', $profiles ) ) );
}

/**
 * SureRank builds its Organization node from the schema definitions returned by
 * Utils::get_default_schemas(), where sameAs ships empty. Setting it there is
 * what actually reaches the rendered JSON-LD; surerank_set_schema receives an
 * empty array and is not the output filter it looks like.
 */
add_filter(
	'surerank_default_schemas',
	static function( $schemas ) {
		$same_as = lookdog_social_profiles();
		if ( empty( $same_as ) || ! is_array( $schemas ) ) {
			return $schemas;
		}

		foreach ( $schemas as $id => $schema ) {
			if ( ! is_array( $schema ) || 'Organization' !== ( $schema['type'] ?? '' ) ) {
				continue;
			}
			$schemas[ $id ]['fields']['sameAs'] = count( $same_as ) === 1 ? $same_as[0] : $same_as;
		}

		return $schemas;
	},
	20
);

/**
 * Open Graph profile association. og:see_also is understood by several
 * crawlers as a related-profile signal; the schema sameAs above is what
 * Google actually reads.
 */
add_action(
	'wp_head',
	static function() {
		if ( ! is_front_page() ) {
			return;
		}
		foreach ( lookdog_social_profiles() as $url ) {
			echo '<meta property="og:see_also" content="' . esc_url( $url ) . '">' . PHP_EOL;
		}
	},
	5
);
