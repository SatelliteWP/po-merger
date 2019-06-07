<?php

/**
 * WP PO Merger - Merger Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.1.0
 * @author SatelliteWP <info@satellitewp.com>
 */

namespace satellitewp\po;

require_once('pos_info.php');
require_once('po_merger.php');

/**
 * Downloads and merges two WordPress plugin/theme PO files.
 */
class Merger {
    
    /**
     * Parameters.
     */
    const BASE_LOCALE = 'base-locale';
    const COPY_LOCALE = 'copy-locale';
    const PO_SOURCE   = 'po-source';
    const FUZZY       = 'fuzzy';
    const MCAF        = 'mark-copy-as-fuzzy';
    const MCAF_SHORT  = 'mcaf';
    const STATUS      = 'status';
    const DIFF_ONLY   = 'diff-only';
    const USERNAME    = 'username';
    const ENV         = 'env';
    const TEST        = 'test';
    const DICTIONARY  = 'dictionary';

    /**
     * Status filters.
     */
    const CURRENT      = 'current';
    const UNTRANSLATED = 'untranslated';
    const FUZZY_FILTER = 'fuzzy';
    const WAITING      = 'waiting';
    const REJECTED     = 'rejected';
    const OLD          = 'old'; 

    /**
     * WordPress host.
     */
    const HOST = 'wordpress.org';
    
    /**
     * Release states.
     */
    const RELEASE_STABLE = 'stable';
    const RELEASE_DEV    = 'dev';

    /**
     * Folder in the root directory where the downloaded PO files will be temporarily saved.
     */
    const DOWNLOAD_FOLDER = 'downloads';

    /**
     * Folder where the files for local testing are located.
     */
    const TESTS_FOLDER = 'tests/local';
    
    /**
     * Delimiter in the string specified by the "status" parameter.
     */
    const FILTERS_DELIM = ',';

    /**
     * Valid parameters.
     */
    protected $valid_params = array( self::BASE_LOCALE, 
                                     self::COPY_LOCALE, 
                                     self::PO_SOURCE, 
                                     self::FUZZY, 
                                     self::MCAF, 
                                     self::MCAF_SHORT, 
                                     self::STATUS, 
                                     self::DIFF_ONLY, 
                                     self::USERNAME, 
                                     self::ENV, 
                                     self::TEST, 
                                     self::DICTIONARY
                                );

    /**
     * Valid filters for the download URL.
     */
    protected $valid_status_filters = array( self::CURRENT, 
                                             self::UNTRANSLATED, 
                                             self::FUZZY_FILTER, 
                                             self::WAITING, 
                                             self::REJECTED, 
                                             self::OLD 
                                    );

    /**
     * Received parameters.
     */
    protected $params = array();

    /**
     * POs information to process files
     */
    protected $pos_infos = array();

    /**
     * Filters for the download URL provided by the "status" parameter.
     */
    protected $status_filters = array();

    /**
     * Contents of the file specified by the "fuzzy" parameter.
     */
    protected $fuzzy_strings = array();

    /**
     * Major core version for the download URL (ex: 5.0.x).
     */
    protected $major_core_version = null;

    /**
     * Path to the download folder in the root directory of the package.
     */
    protected $download_folder_path = null;

    /**
     * Path to the tests folder in the root directory of the package.
     */
    protected $tests_folder_path = null;

    /**
     * Latest error that occured.
     */
    protected $error_message = null;
    
    /**
     * Condition(s) that require user's attention.
     */
    protected $warning_messages = null;

    /**
     * Indicates if the "mark-copy-as-fuzy" parameter is set.
     */
    protected $is_mcaf = false;

    /**
     * Specifies if the downloaded PO files should be kept in the "downloads" folder.
     */
    protected $keep_downloaded_pos = false;
    
    /**
     * Indicates if the source of the PO file is an URL.
     */
    protected $is_url = false;

    /**
     * Constructor. 
     * 
     * Sets the parameters, the paths to the download and local tests folders.
     */
    public function __construct( $args = array(), $assoc_args = array() )
    {
        $assoc_args['base-locale'] = ( isset( $args[0] ) ? $args[0] : null );
        $assoc_args['copy-locale'] = ( isset( $args[1] ) ? $args[1] : null );
        $assoc_args['po-source']   = ( isset( $args[2] ) ? $args[2] : null );
        
        $this->params = $assoc_args;
        $this->download_folder_path = dirname( __DIR__, 1 ) . '/'. self::DOWNLOAD_FOLDER . '/';
        $this->tests_folder_path    = dirname( __DIR__, 1 ) . '/'. self::TESTS_FOLDER . '/';
    }

    /**
     * Launches the merging process.
     */
    public function start() 
    {
        if  ( isset( $this->params[self::MCAF] ) || isset( $this->params[self::MCAF_SHORT] ) ) 
        {
            $this->is_mcaf = true;
        }

        if ( isset( $this->params[self::FUZZY] ) ) 
        {
            $this->fuzzy_strings = file( $this->params[self::FUZZY], FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES );
        }
        
        $this->merge();
    }

    /**
     * Verifies if the merging process can be started.
     * 
     * @return bool Indicates if the process can be started.
     */
    public function can_start() 
    {
        $result = false;
        
        if ( $this->is_url ) 
        {
            $result = $this->process_pos( $this->params[self::PO_SOURCE], $this->params[self::BASE_LOCALE], $this->params[self::COPY_LOCALE], null );
        }
        else 
        {
            $result = $this->process_pos( null, $this->params[self::BASE_LOCALE], $this->params[self::COPY_LOCALE], $this->major_core_version );
        }

        return $result;
    }

    /**
     * Extracts the translations from the copy locale PO file, merges them with the 
     * content from the base locale and saves the result in a new file.
     */
    public function merge() 
    {   
        $dictionary = isset( $this->params[self::DICTIONARY] ) ? $this->params[self::DICTIONARY] : null;
        $po_merger  = new Po_Merger( $this->fuzzy_strings, $this->is_mcaf );       
        
        foreach( $this->pos_infos as $pi )
        {
            $po_merger->initialize( $pi->get_base_filename(), $pi->get_copy_filename(), $dictionary );

            $filename = getcwd() . '/' . $pi->get_download_filename( 'merged' );
            $count = $po_merger->merge( $filename );

            if ( $count === 0 )
            {
                $this->warning_messages[] = $pi->get_project_name() . ' is fully translated (or contains only waiting translations). No file generated.';
            }
            else
            {
                $stats = $po_merger->get_stats();

                \WP_CLI::success( "Merge completed for PO file." );

                \WP_CLI::success(  "Stats for processed file:\n" .
                                "Total processed: {$stats['total']}\n" . 
                                "Contained fuzzy strings: {$stats['contained-fuzzy-strings']}\n" . 
                                ( $dictionary == null ? "" : "Copied from dictionary: {$stats['used-from-dictionary']}\n" ) . 
                                "Copied from copy locale: {$stats['used-from-copy']}\n");
            }
        }

        $this->delete_downloaded_pos();
    }

    /**
     * Verifies if the parameters are valid.
     * 
     * @return bool Indicates if the parameters are valid or not.
     */
    public function has_valid_parameters()
    {
        $result = false;
        
        // Verify if all the required parameters were specified. 
        if ( is_null( $this->params[self::BASE_LOCALE] ) || is_null( $this->params[self::COPY_LOCALE] ) || is_null( $this->params[self::PO_SOURCE] ) ) 
        {
            $this->error_message = 'Please specify all three required parameters: <base-locale> <copy-locale> <source>';
        }
        else 
        {
            // Verify each parameter.
            $result = $this->is_each_parameter_valid( $this->params );
        }

        return $result;
    }

     /**
     * Verifies each received parameter.
     * 
     * @param array $params Parameters to verify.
     * 
     * @return bool Indicates if all of the parameters are valid, or one of the parameters is invalid.
     */
    public function is_each_parameter_valid( $params = array() ) 
    {        
        $result = false;

        // Initialize the varibale with number of parameters. 
        $count_valid = count( $params );
       
        // Verify if the names of the parameters and their values are valid.
        foreach ( array_keys( $params ) as $param ) 
        {
            if ( in_array( $param, $this->valid_params ) ) 
            {
                switch ( $param ) 
                {
                    // Verify if the source is an URL or a core version.
                    case self::PO_SOURCE:
                        $source = $params[self::PO_SOURCE];
                        $this->is_url = $this->is_url( $source );
                
                        if ( $this->is_url ) 
                        {
                            if ( !$this->is_valid_url( $source ) ) 
                            {
                                --$count_valid;
                                break 2;
                            }
                        }
                        else 
                        {
                            // Attempt to get the major core version for the download URL.
                            $this->major_core_version = Pos_Info::get_major_core_version( $source );
                    
                            if ( is_null( $this->major_core_version) ) 
                            {
                                --$count_valid;
                                $this->error_message = 'The core version is invalid.';
                                break 2;
                            }
                        }
                        break; 
                    
                    case self::FUZZY:
                        
                        // Verify if the specified file exists.
                        if ( ! is_file( $params[self::FUZZY] ) ) 
                        {
                            --$count_valid;
                            $this->error_message = "Failed to open the file: " . $params[self::FUZZY] . " doesn't exist.";
                            break 2;
                        }
                        break;
                    
                    case self::DICTIONARY:
                        
                        // Verify if the specified file exists.
                        if ( ! is_file( $params[self::DICTIONARY] ) ) 
                        {
                            --$count_valid;
                            $this->error_message = "Failed to open the file: " . $params[self::DICTIONARY] . " doesn't exist.";
                            break 2;
                        }
                        break;

                    case self::STATUS:
                        
                        // Get the valid and the invalid filters from the specified string.
                        $received_filters = $this->get_filters( $params[self::STATUS] );

                        // Set the error message with each invalid filter.
                        if ( ! empty( $received_filters['invalid'] ) )
                        {
                            $invalid_filters = implode( ', ', $received_filters['invalid'] );
                            $this->error_message = 'The following filters are invalid: ' . $invalid_filters;
                            
                            --$count_valid;
                            $this->error_message .= '.';
                            break 2;
                        }
                        else 
                        {
                            $this->status_filters = $received_filters['valid'];
                        }
                        break;       
                }
            }
            else 
            {
                // Set the error message with the invalid parameter's name.
                $this->error_message = __( 'Invalid parameter: ' . $param . '.' );
                --$count_valid;
                break;
            }
        }

        // If the number of valid parameters corresponds to the number of received arguments.
        if ( $count_valid === count( $params ) ) 
        {
            $result = true;
        }
        
        return $result;
    }

    /**
     * Verifies if a string is an URL.
     * 
     * @param string $string String to verify.
     * 
     * @return bool True if valid URL. Otherwise, false.
     */
    public function is_url( $string ) 
    {
        $parsed_string = parse_url( strtolower( $string ) );

        return  filter_var( $string, FILTER_VALIDATE_URL ) && 
                in_array( $parsed_string['scheme'], array( 'http', 'https' ) );
    }
    
    /**
     * Verifies that an URL is a valid plugin/theme homepage URL/translation URL.
     * 
     * @param string $url URL to validate.
     * 
     * @return bool Indicates if the URL is valid or not.
     */
    public function is_valid_url( $url ) 
    {
        $result = true;

        $url = strtolower( $url );
        $url_parts = parse_url( $url );
        
        // Host validation.
        if ( !$this->ends_with( $url_parts['host'], self::HOST ) ) 
        {
            $this->error_message = __( 'The host (' . $url_parts['host'] . ') in the URL is invalid.' );
            $result = false;
        }
    
        // Path validation.
        if ( $result ) 
        {
           if ( isset( $url_parts['path'] ) ) 
           {
                $path_parts = explode( '/', $url_parts['path'] );
                
                if ( count( $path_parts ) < 2 ) 
                {
                    $result = false;
                    $this->error_message = 'The URL is invalid.';
                }
                else 
                {
                    if ( empty( $path_parts[1] ) ) 
                    {
                        $result = false;
                        $this->error_message = 'The URL is invalid.';
                    }
                }

                // Verify the type of the URL.
                if ( $result ) 
                {
                   if ( ! Pos_Info::is_valid_url_type( $path_parts ) ) 
                   {
                        $result = false;    
                        $this->error_message = 'The URL type could not be detected.';             
                   }
                }
                
                // Verify the slug.
                if ( $result )
                {
                    if ( Pos_Info::is_slug_empty( $path_parts ) ) 
                    {
                        $result = false;
                        $this->error_message = 'The slug in the URL cannot be empty.';
                    }
                }
            }
        }
        else 
        {
            $this->error_message = __( 'The URL is invalid.' );
            $result = false;
        }
        
        return $result;
    }

    /**
     * Build Pos_Info array according to parameters
     * 
     * @param string $url The homepage url of the plugin/theme.
     * @param string $base_locale Locale where the translations from the copy locale will be used.
     * @param string $copy_locale Locale used to get translations that are not present in the base locale.
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return array Array of Pos_Info
     */
    protected function build_pos_infos( $url, $base_locale, $copy_locale, $core )
    {
        $result = array();

        // Set the environment variable
        $env = self::RELEASE_STABLE;
        if ( isset( $this->params[self::ENV] ) ) 
        {
            $env = $this->params[self::ENV];
        }

        // Set the filters for the download url.
        $filters = array(
            self::STATUS    => $this->status_filters,
            self::DIFF_ONLY => ( isset( $this->params[self::DIFF_ONLY] ) ) ? $this->params[self::DIFF_ONLY] : null,
            self::USERNAME  => ( isset( $this->params[self::USERNAME] ) ) ? $this->params[self::USERNAME] : null
        );

        $pi = new Pos_Info( $base_locale, $copy_locale );
        $pi->set_env( $env );
        $pi->set_download_filters( $filters );

        $sub_projects = array();
        if ( $url != null ) 
        {
            $pi->set_url( $url );
        }
        else
        {
            $pi->set_core_version( $core );

            $sub_projects = $pi->get_core_sub_projects();
        }

        $result[] = $pi;
        $result = array_merge( $result, $sub_projects );

        return $result;
    }

    /**
     * Downloads the base locale, the copy locale PO files and attempts to read their contents.
     * 
     * @param string $url The homepage url of the plugin/theme.
     * @param string $base_locale Locale where the translations from the copy locale will be used.
     * @param string $copy_locale Locale used to get translations that are not present in the base locale.
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return bool True if the process was successful. Otherwise, false.
     */
    public function process_pos( $url, $base_locale, $copy_locale, $core ) 
    {
        $result = false;

        $this->pos_infos = $this->build_pos_infos( $url, $base_locale, $copy_locale, $core );

        // Create, if necessary, the folder where the downloaded PO files will be saved.
        if( ! is_dir( $this->download_folder_path ) ) 
        {
            mkdir( $this->download_folder_path, 0770 );
        }

        $this->download_pos_infos();

        return empty( $this->error_message );
    }

    /**
     * Deletes the downloaded PO files.
     */
    public function delete_downloaded_pos() 
    {
        foreach( $this->pos_infos as $pi )
        {
            $this->delete_file( $pi->get_base_filename() );

            $this->delete_file( $pi->get_copy_filename() );
        }
    }

    /**
     * Delete a file
     * 
     * @param string $filename File to delete
     */
    public function delete_file( $filename )
    {
        if ( is_file( $filename ) ) 
        {
            unlink( $filename );
        } 
    }

    /**
     * Downloads PO files and set paths in POS_Info objects.
     * 
     */
    public function download_pos_infos() 
    {   
        foreach($this->pos_infos as $pi)
        {
            if ( isset( $this->params[self::TEST] ) ) 
            {
                // TODO
            }
            else
            {
                $base_url = $pi->get_base_download_url();
                $base_filename = $this->download_file( $base_url, $pi->get_base_download_filename(), $this->download_folder_path );
                $pi->set_base_filename( $base_filename );
    
                $copy_url = $pi->get_copy_download_url();
                $copy_filename = $this->download_file( $copy_url, $pi->get_copy_download_filename(), $this->download_folder_path );
                $pi->set_copy_filename( $copy_filename );

                // If core download failed, try the dev project.
                if ( $pi->is_core() && $base_filename == null && $copy_filename == null ) 
                {
                    // Change project version to DEV
                    $pi->set_core_version( 'dev' );
    
                    $base_url = $pi->get_base_download_url();
                    $base_filename = $this->download_file( $base_url, $pi->get_base_download_filename(), $this->download_folder_path );
                    $pi->set_base_filename( $base_filename );
        
                    $copy_url = $pi->get_copy_download_url();
                    $copy_filename = $this->download_file( $copy_url, $pi->get_copy_download_filename(), $this->download_folder_path );
                    $pi->set_copy_filename( $copy_filename );
                }

                \WP_CLI::debug( "Base URL: " . $base_url . "\nFilename: " . $base_filename );
                \WP_CLI::debug( "\nCopy URL: " . $copy_url . "\nFilename: " . $copy_filename );

                if ( is_null( $base_filename ) && is_null( $copy_filename ) )
                {
                    $this->error_message = 'The parameters have errors.';
                }
                elseif ( is_null( $base_filename ) ) 
                {   
                    $this->error_message = 'The base locale is invalid.';
                }
                elseif ( is_null( $copy_filename ) ) 
                {
                    $this->error_message = 'The copy locale is invalid.';
                } 
            }
        }
    }

    /**
     * Downloads and saves a single PO file.
     * 
     * @param string $url URL of PO file to download.
     * @param string $path Path where to save the file
     * 
     * @return string Path of the downloaded PO file, or null if download failed.
     */
    public function download_file( $url, $filename, $path ) 
    {
        $result = null;

        // Verify if the file was downloaded successfully.
        $fh = @fopen( $url, 'r' );
        if ( $fh !== false )
        {
            if ( file_put_contents( $path . $filename, $fh ) !== false ) 
            {
                $result = $path . $filename;
            }
            else 
            {
                unlink( $path . $filename );
            }

            fclose( $fh );
        }

        return $result;
    }

    /**
     * Splits the string containing the filters into array based on the provided delimiter. 
     * Verifies if each filter is valid and puts it into the corresponding array.
     * 
     * @param string $filters_string String containing the filters separated by a delimiter.
     * 
     * @return array Array containing the valid and the invalid filters.
     */
    public function get_filters( $filters_string ) 
    {
        $valid_filters   = array();
        $invalid_filters = array();
        
        $filters = explode( self::FILTERS_DELIM, $filters_string );

        foreach ( $filters as $filter ) 
        {   
            if ( in_array( $filter, $this->valid_status_filters ) ) 
            {
                $valid_filters[] = $filter;
            }
            else
            {
                $invalid_filters[] = $filter;
            }
        }

        return array(
            'valid'   => $valid_filters,
            'invalid' => $invalid_filters
        );
    }

    /**
     * Checks if a string ends with a certain substring.
     * 
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * 
     * @return bool True if the string ends with the received needle. Otherwise, false.
     */
    function ends_with( $haystack, $needle )
    {
        $length = strlen( $needle );
        
        return ( $length == 0 ? true : substr( $haystack, -$length ) === $needle );
    }

    /**
     * Returns the latest error message.
     * 
     * @return string Error message.
     */
    public function get_error_message()
    {
        return $this->error_message;
    }

    /**
     * Returns the warning messages.
     * 
     * @return array Warning messages.
     */
    public function get_warning_messages()
    {
        return $this->warning_messages;
    }

    /**
     * Returns the path to the download folder in the root of the package.
     * 
     * @return string Folder path.
     */
    public function get_download_folder_path() 
    {
        return $this->download_folder_path;
    }
}