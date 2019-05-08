<?php

/**
 * PO Merger
 *
 * PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.0.0
 * @author SatelliteWP <info@satellitewp.com>
 */
namespace satellitewp\po;

if ( !defined( 'WP_CLI' ) ) return;

/**
 * WP-CLI PO command for satellitewp/po
 *
 *  ## EXAMPLES
 *
 *      # Merge for the the URL of a plugin or theme found on the WordPress repository.
 *      $ wp po merge fr-ca fr https://wordpress.org/plugins/wordpress-seo/
 *
 *      # Merge for the translation URL of a project in the WordPress repository (including meta and apps).
 *      $ wp po merge fr-ca fr https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/wordpress-seo/
 *
 *      # Merge for the core version of WordPress.
 *      $ wp po merge fr-ca fr 5.0
 */
class Po_Command extends \WP_CLI_Command {

	/**
	 * Merge of two PO files of similar languages.
	 *
	 * ## OPTIONS
	 *
	 * <base-locale>
	 * : Main locale used for the merge.
	 *
	 * <copy-locale>
	 * : Locale used to get translations that are not present in the base locale.
	 *
	 * <URL>
	 * : URL of the project.
	 *
	 * <version>
	 * : Core version.
	 *
	 * ## EXAMPLES
	 *
	 *      # Merge for the the URL of a plugin or theme found on the WordPress repository.
	 *      $ wp po merge fr-ca fr https://wordpress.org/plugins/wordpress-seo/
	 *
	 *      # Merge for the translation URL of a project in the WordPress repository (including meta and apps).
	 *      $ wp po merge fr-ca fr https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/wordpress-seo/
	 *
	 *      # Merge for the core version of WordPress.
	 *      $ wp po merge fr-ca fr 5.0
	 *
	 * @when before_wp_load
	 */
	function merge( $args = array(), $assoc_args = array(), $verbose = true )
	{
		$merger = new Merger( $args, $assoc_args );

		if ( $merger->has_valid_parameters() )
		{
			if ( $merger->can_start() )
			{
				$merger->start();
			}
			else
			{
				\WP_CLI::error( $merger->get_error_message() );
			}

			if ( !is_null( $merger->get_warning_messages() ) )
			{
				foreach( $merger->get_warning_messages() as $warning_message )
				{
					\WP_CLI::warning( $warning_message );
				}
			}
		}
		else
		{
			\WP_CLI::error( $merger->get_error_message() );
		}
	}
}

\WP_CLI::add_command( 'po',  __NAMESPACE__ . '\\Po_Command' );