<?php

/**
 * PO Merger
 *
 * PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.1.0
 * @author SatelliteWP <info@satellitewp.com>
 */
namespace satellitewp\po;

require_once('core/po_file_merger.php');

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
	public function merge( $args = array(), $assoc_args = array(), $verbose = true )
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

	/**
	 * Merges two PO files
	 * 
	 * ## OPTIONS
	 *
	 *
	 * <base-file>
	 * : Base filename that will receive the translations.
	 * 
	 * <copy-file>
	 * : Source where translations will be extracted.
	 * 
	 * 
	 * [--max-length=<length>]
	 * : Maximum string length to consider
	 * default: 75
	 * 
	 * [--mark-copy-as-fuzzy]
	 * : Mark copy as fuzzy in the base file
	 * default: true
	 * 
	 * @subcommand merge-file
	 * @when before_wp_load
	 */
	public function merge_file( $args = array(), $assoc_args = null )
	{
		if ( count( $args ) == 2 )
		{
			if ( ! is_readable( $args[0] ) )
			{
				\WP_CLI::error( 'The base PO file specified is not a readable file.' );
			}
			elseif ( ! is_readable( $args[1] ) )
			{
				\WP_CLI::error( 'The copy PO file specified is not a readable file.' );
			}
			else
			{
				$pfm = new Po_File_Merger( $args[0], $args[1] );

				try
				{
					$pfm->load_files();
				}
				catch( InvalidArgumentException $ex ) 
				{
					\WP_CLI::error( 'PO files could not be loaded properly. Make sure they both are valid.' );
				}

				// Max length
				if ( isset( $assoc_args['max-length'] ) )
				{
					$max = (int) $assoc_args['max-length'];
	
					$pfm->set_max_length( $max );

					\WP_CLI::debug( 'Length: ' . $max );
				}
	
				// Mark copy as fuzzy?
				if ( isset( $assoc_args['mark-copy-as-fuzzy'] ) )
				{
					$value = ( $assoc_args['mark-copy-as-fuzzy'] === 'true' ? true : false );
	
					$pfm->set_mark_copy_as_fuzzy( $value );

					\WP_CLI::debug( 'Mark copy as fuzzy?: ' . ($value ? 'true' : 'false' ) );
				}

				$pfm->merge();
			}
		}
		else {
			\WP_CLI::error( 'You must specify both the base PO file path and the copy PO file path.' );
		}
	}

	/**
	 * Get the difference two PO files
	 * 
	 * ## OPTIONS
	 *
	 *
	 * <base-file>
	 * : Base filename to validate.
	 * 
	 * <copy-file>
	 * : Source where translations will be extracted.
	 * 
	 * 
	 * [--max-length=<length>]
	 * : Maximum string length to consider
	 * default: 75
	 * 
	 * @subcommand diff
	 * @when before_wp_load
	 */
	public function diff_file( $args = array(), $assoc_args = null )
	{
		if ( count( $args ) == 2 )
		{
			if ( ! is_readable( $args[0] ) )
			{
				\WP_CLI::error( 'The base PO file specified is not a readable file.' );
			}
			elseif ( ! is_readable( $args[1] ) )
			{
				\WP_CLI::error( 'The copy PO file specified is not a readable file.' );
			}
			else
			{
				$pfm = new Po_File_Merger( $args[0], $args[1] );

				try
				{
					$pfm->load_files();
				}
				catch( InvalidArgumentException $ex ) 
				{
					\WP_CLI::error( 'PO files could not be loaded properly. Make sure they both are valid.' );
				}
				if ( isset( $assoc_args['max-length'] ) )
				{
					$max = (int) $assoc_args['max-length'];
	
					$pfm->set_max_length( $max );
				}
	
				$pfm->diff();
			}
		}
		else {
			\WP_CLI::error( 'You must specify both the base PO file path and the copy PO file path.' );
		}
	}
}

// Temporary hack
function __( $text ) { return $text; }

\WP_CLI::add_command( 'po',  __NAMESPACE__ . '\\Po_Command' );
