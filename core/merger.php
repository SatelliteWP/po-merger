<?php

/**
 * WP PO Merger - Merger Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.0.0
 * @author SatelliteWP <info@satellitewp.com>
 */

namespace satellitewp\po;

require_once('po_extractor.php');
require_once('po_merger.php');

/**
 * Downloads and merges two WordPress plugin/theme *.po files.
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

    /**
     * Status filters.
     */
    const CURRENT      = 'current';
    const UNTRANSLATED = 'untranslated';
    const FUZZY_FILTER = 'fuzzy';
    const WAITNING     = 'waiting';
    const REJECTED     = 'rejected';
    const OLD          = 'old'; 

    /**
     * In the URL, idendifier of a status filter.
     */
    const STATUS_FILTER = 'status';
    
    /**
     * In the URL, idendifier of a user filter.
     */
    const USER_FILTER = 'user_login';

    /**
     * Filter parts.
     */
    const FILTER_BASE      = 'filters%5B';
    const FILTER_SETTER    = '%5D=';
    const FILTER_SEPARATOR = '&';

    /**
     * Default parts of a *.po file download URL.
     */
    const URL_BASE = 'https://translate.wordpress.org/projects/';
    const URL_END  = '/default/export-translations';

    /**
     * WordPress host.
     */
    const HOST = 'wordpress.org';
    
    /**
     * WordPress translation host.
     */
    const TRANSLATION_HOST = 'translate.wordpress.org';
    
    /**
     * Release states.
     */
    const RELEASE_STABLE = 'stable';
    const RELEASE_DEV    = 'dev';

    /**
     * Types defined in the URL.
     */
    const TYPE_PLUGINS = 'plugins';
    const TYPE_THEMES  = 'themes';
    const TYPE_META    = 'meta';
    const TYPE_APPS    = 'apps';

    const TYPE_TRANSLATION_PLUGINS = 'wp-plugins';
    const TYPE_TRANSLATION_THEMES  = 'wp-themes';

    /**
     * Core sub-projects defined in the download URL of a *.po file.
     */
    const CORE_CC        = 'cc';
    const CORE_ADMIN     = 'admin';
    const CORE_ADMIN_NET = 'admin/network';

    /**
     * Full names of a core sub-projects.
     */
    const CORE_CC_NAME        = 'Continents & Cities';
    const CORE_ADMIN_NAME     = 'Administration';
    const CORE_ADMIN_NET_NAME = 'Network Admin';

    /**
     * Total number of the *.po files in a core project (core plus sub-projects).
     */
    const CORE_PROJECTS = 4;

    /**
     * Length of the *.po file header.
     */
    const PO_HEADER_LENGTH = 12; 

    /**
     * Folder in the root directory where the downloaded *.po files will be temporarily saved.
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
    protected $valid_params = array( self::BASE_LOCALE, self::COPY_LOCALE, self::PO_SOURCE, self::FUZZY, self::MCAF, self::MCAF_SHORT, self::STATUS, self::DIFF_ONLY, self::USERNAME, self::ENV, self::TEST );

    /**
     * Valid filters for the download URL.
     */
    protected $valid_status_filters = array( self::CURRENT, self::UNTRANSLATED, self::FUZZY_FILTER, self::WAITNING, self::REJECTED, self::OLD );

    /**
     * Types defined in a translate URL.
     */
    protected $url_translate_types = array( self::TYPE_TRANSLATION_PLUGINS, self::TYPE_TRANSLATION_THEMES, self::TYPE_META, self::TYPE_APPS, ); 
    
    /**
     * Types that require @see self::RELEASE_DEV in the URL.
     */
    protected $dev_url_types = array( self::TYPE_APPS );
    
    /**
     * Core sub-projects defined in the download URL of a *.po file.
     */
    protected $core_sub_projects = array( self::CORE_CC, self::CORE_ADMIN, self::CORE_ADMIN_NET );
    
    /**
     * Full names of the core sub-projects.
     */
    protected $core_sub_projects_names = array( self::CORE_CC_NAME, self::CORE_ADMIN_NAME, self::CORE_ADMIN_NET_NAME );

    /**
     * Received parameters.
     */
    protected $params = array();

    /**
     * Content of the base locale *.po file.
     */
    protected $base_content = array();

    /**
     * Content of the copy locale *.po file.
     */
    protected $copy_content = array();

    /**
     * Filters for the download URL provided by the "status" parameter.
     */
    protected $status_filters = array();

    /**
     * Contents of the file specified by the "fuzzy" parameter.
     */
    protected $fuzzy_strings = array();

    /**
     * Parameters of the result file(s).
     */
    protected $result_params = array();

    /**
     * Name of the result file(s).
     */
    protected $result_name = array();

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
     * Specifies if the downloaded *.po files should be kept in the "downloads" folder.
     */
    protected $keep_downloaded_pos = false;

    /**
     * Indicates if the host is "translate.wordpress.org".
     */
    protected $is_translate_host = false;
    
    /**
     * Indicates if the source of the *.po file is an URL.
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
     * Extracts the translations from the copy locale *.po file, merges them with the 
     * content from the base locale and saves the result in a new file.
     */
    public function merge() 
    {   
        $po_extractor  = new Po_Extractor();
        $po_merger     = new Po_Merger( $this->fuzzy_strings, $this->is_mcaf );
        
        $merged_content = null;
        
        // If the process is on a core (the core project contains multiple files).
        if ( is_array( $this->copy_content[0] ) ) 
        {
            $merged_content = array();
            
            // For each project, merge the contents.
            for ( $i = 0; $i < count ( $this->copy_content ); ++$i ) 
            {
                $po_extractor->initialize( $this->copy_content[$i] );
                $po_merger->initialize( $this->base_content[$i], $po_extractor->extract_msgs() );

                $merged_content[] = $po_merger->merge_po();
            }
        }
        else 
        {
            $po_extractor->initialize( $this->copy_content );
            $po_merger->initialize( $this->base_content, $po_extractor->extract_msgs() );

            $merged_content = $po_merger->merge_po();
        }
        
        $this->create_result_file( $merged_content, $this->result_name );
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
            $this->error_message = __( 'Please specify all three required parameters: <base-locale> <copy-locale> <po-source>' );
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
                            $this->major_core_version = $this->create_core_version_for_url( $source );
                    
                            if ( is_null( $this->major_core_version) ) 
                            {
                                --$count_valid;
                                $this->error_message = __( 'The core version is invalid.' );
                                break 2;
                            }
                        }
                        break; 
                    
                    case self::FUZZY:
                        
                        // Verify if the specified file exists.
                        if ( !is_file( $params[self::FUZZY] ) ) 
                        {
                            --$count_valid;
                            $this->error_message = __( "Failed to open the file: " . $params[self::FUZZY] . " doesn't exist." );
                            break 2;
                        }
                        break;
                    
                    case self::STATUS:
                        
                        // Get the valid and the invalid filters from the specified string.
                        $received_filters = $this->get_filters( $params[self::STATUS] );

                        // Set the error message with each invalid filter.
                        if ( !empty( $received_filters['invalid_filters'] ) ) 
                        {
                            $this->error_message = __( 'The following filters are invalid: ' );
                            
                            foreach ( $received_filters['invalid_filters'] as $invalid_filter ) 
                            {
                                $this->error_message .= $invalid_filter . ' ';
                            }
                            
                            --$count_valid;
                            $this->error_message .= '.';
                            break 2;
                        }
                        else 
                        {
                            $this->status_filters = $received_filters['valid_filters'];
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
     * @return bool Indicates if the string is an URL or not.
     */
    public function is_url( $string ) 
    {
        $result = false;
        
        $string = strtolower( $string );

        $parsed_string = parse_url( $string );

        if ( filter_var( $string, FILTER_VALIDATE_URL ) && ( $parsed_string['scheme']  == 'https' ) ) 
        {
            $result = true;
        }
        
        return $result;
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
                    $this->error_message = __( 'The URL is invalid.' );
                }
                else 
                {
                    if ( empty( $path_parts[1] ) ) 
                    {
                        $result = false;
                        $this->error_message = __( 'The URL is invalid.' );
                    }
                }

                // Verify the type of the URL.
                if ( $result ) 
                {
                   if ( !$this->is_valid_url_type( $path_parts ) ) 
                   {
                        $result = false;    
                        $this->error_message = __( 'The URL type could not be detected.' );             
                   }
                }
                
                // Verify the slug.
                if ( $result )
                {
                    if ( $this->is_empty_slug( $path_parts ) ) 
                    {
                        $result = false;
                        $this->error_message = __( 'The slug in the URL cannot be empty.' );
                    }
                }
            }
        }
        else 
        {
            $this->error_message = __( 'The URL is invalid.' );
            $result = false;
        }
    
        if ( $result ) 
        {
            $this->is_translate_host( $url_parts['host'] );
        }
        
        return $result;
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
     * @return string Warning message(s).
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

    /**
     * Creates the major core version for the download url. Exemple: 5.0.x
     * 
     * @param string $core Core version.
     * 
     * @return string|null Major core version for the download url, 
     * or null if the version is invalid.
     */
    public function create_core_version_for_url( $core ) 
    {
        $result = $this->get_major_core_version( $core );
        
        if ( !is_null( $result ) ) 
        {
            $result .= '.x';
        }
        
        return $result;
    }

    /**
     * Veifies the URL type (main page of a plugin/theme, apps, meta, or translate URL).
     * 
     * @param array $path_parts Path part of an URL as an array.
     * 
     * @return bool Indiciates if the type is valid or not.
     */
    public function is_valid_url_type( $path_parts ) 
    {
        $result = false;
        
        // Verify if it's the main page of a plugin/theme.
        if ( in_array( $path_parts[1], array( self::TYPE_THEMES, self::TYPE_PLUGINS ) ) ) 
        {
            $result = true;
        }
        else 
        {
            // Verify if it's a translate URL.
            if ( count( $path_parts ) >=5 ) 
            {
                if ( in_array( $path_parts[4], $this->url_translate_types ) ) 
                {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Verifies if the URL slug is empty.
     * 
     * @param array $path_parts Path part of an URL as an array.
     * 
     * @return bool Indicates if the slug is empty or not.
     */
    public function is_empty_slug( $path_parts ) 
    {
        $result = false;
        
        // If it's a main page of a plugin/theme.
        if ( ( $path_parts[1] == self::TYPE_PLUGINS || $path_parts[1] == self::TYPE_THEMES ) && empty( $path_parts[2] ) )    
        {
            $result = true;
        }
        // If it's a translate URL.
        elseif ( count( $path_parts ) >=5 ) 
        {
            if ( in_array( $path_parts[4], $this->url_translate_types ) && empty( $path_parts[5] ) ) 
            {
                $result = true;
            }  
        }
        
        return $result;
    }

    /**
     * VErifies if a string ends with a certain substring.
     * 
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * 
     * @return bool Indicates if the string ends with the received parameter or not.
     */
    function ends_with( $haystack, $needle )
    {
        $length = strlen( $needle );
        
        if ( $length == 0 ) 
        {
            return true;
        }
    
        return ( substr( $haystack, -$length ) === $needle );
    }

    /**
     * Verifies if the domain is @see self::TRANSLATION_HOST
     * 
     * @param string $domain String representing the domain.
     */
    public function is_translate_host( $host ) 
    {
        if ( $host == self::TRANSLATION_HOST ) 
        {
            $this->is_translate_host = true;
        }
    }

    /**
     * Downloads the base locale, the copy locale *.po files and attempts to read their contents.
     * 
     * @param string $url The homepage url of the plugin/theme.
     * @param string $base_locale Locale where the translations from the copy locale will be used.
     * @param string $copy_locale Locale used to get translations that are not present in the base locale.
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return bool Indicates if the process was successful or not.
     */
    public function process_pos( $url, $base_locale, $copy_locale, $core ) 
    {
        $result = false;
        
        // Download the base and copy locale *.po files.
        $downloaded_po_paths = $this->start_pos_download( $url, $base_locale, $copy_locale, $core );
        
        // If the *.po files were downloaded successfully, extract the contents of the downloaded *.po files into arrays.
        if ( !is_null( $downloaded_po_paths['path_base'] ) && !is_null( $downloaded_po_paths['path_copy'] ) ) 
        {   
            // Verify if an empty *.po file was downloaded.
            if ( !is_null( $core ) ) 
            {
                $count_empty = 0;
                
                // Temporary variables for the *.po paths and core project names.
                $temp_path_base = array();
                $temp_path_copy = array();
                
                $temp_core_sub_projects       = $this->core_sub_projects;
                $temp_core_sub_projects_names = $this->core_sub_projects_names;

                // Reinitialize the variables in order to contain only the valid values at the end of the process.
                $this->core_sub_projects       = array();
                $this->core_sub_projects_names = array();
                
                for ( $i = 0; $i < count( $downloaded_po_paths['path_base'] ); ++$i ) 
                {
                    if ( $this->has_only_header( $downloaded_po_paths['path_base'][$i] ) ) 
                    {
                        // If the downloaded files shouldn't be kept, delete the empty *.po file (since the copy locale isn't needed anymore, delete it as well). 
                        if ( !$this->keep_downloaded_pos && !isset( $this->params[self::TEST] ) ) 
                        {
                            unlink( $downloaded_po_paths['path_base'][$i] );
                            unlink( $downloaded_po_paths['path_copy'][$i] );
                        }

                        // Set the warninng messages for the empty *.po files.
                        if ( $i !== 0 ) 
                        {
                            $this->warning_messages[] = __( '"' . $temp_core_sub_projects_names[$i - 1] . '" is fully translated or contains only waiting translations. ' .  
                                                           'No file will be generated.' );
                        }
                        else 
                        {
                            $this->warning_messages[] = __( 'The core is fully translated or contains only waiting translations. No file will be generated.' );
                        }

                        ++$count_empty;
                    }
                    else 
                    {
                        $temp_path_base[] = $downloaded_po_paths['path_base'][$i];
                        $temp_path_copy[] = $downloaded_po_paths['path_copy'][$i];

                        if ( $i !== 0 ) 
                        {
                            $this->core_sub_projects[] = $temp_core_sub_projects[$i - 1];
                            $this->core_sub_projects_names[] = $temp_core_sub_projects_names[$i - 1];
                        }
                    }
                }

                // If not all *.po files of the base locale are empty.
                if ( $count_empty < self::CORE_PROJECTS )
                {   
                    for ( $i = 0; $i < count( $temp_path_base ); ++$i ) 
                    {
                        $this->base_content[] = file( $temp_path_base[$i] );
                        $this->copy_content[] = file( $temp_path_copy[$i] );
                    }
                    
                    $result = true;
                }
            }
            else 
            {
                if ( !$this->has_only_header( $downloaded_po_paths['path_base'] ) ) 
                {
                    $this->base_content = file( $downloaded_po_paths['path_base'] );
                    $this->copy_content = file( $downloaded_po_paths['path_copy'] );

                    $result = true;
                }
            }
            // End of the verification.

            // Set the name of the result file(s).
            $this->result_name = $this->set_result_name();
        }
        
        // Set the error messages.
        else
        {
            if ( is_null( $downloaded_po_paths['path_base'] ) && !is_null( $downloaded_po_paths['path_copy'] ) ) 
            {   
                $this->error_message = __( "The base locale is invalid." );
            }
            elseif ( !is_null( $downloaded_po_paths['path_base'] ) && is_null( $downloaded_po_paths['path_copy'] ) ) 
            {
                $this->error_message = __( 'The copy locale is invalid.' );
            }
            elseif ( is_null( $downloaded_po_paths['path_base'] ) && is_null( $downloaded_po_paths['path_copy'] ) )
            {
                $this->error_message = __( 'The parameters have errors.' );
            }
        }

        // If the downloaded files shouldn't be kept, delete them. 
        if ( !$this->keep_downloaded_pos && !isset( $this->params[self::TEST] ) ) 
        {
            $this->delete_downloaded_pos( $downloaded_po_paths );
        }
        
        return $result;
    }

    /**
     * Deletes the downloaded *.po files.
     * 
     * @param array $downloaded_po_paths Downloaded *.po files paths.
     */
    public function delete_downloaded_pos( $downloaded_po_paths = array() ) 
    {
        if ( is_array( $downloaded_po_paths['path_base'] ) && is_array( $downloaded_po_paths['path_copy'] ) )
        {
            for ( $i = 0; $i < count( $downloaded_po_paths['path_base'] ); ++$i ) 
            {
                if ( is_file( $downloaded_po_paths['path_base'][$i] ) ) 
                {
                    unlink( $downloaded_po_paths['path_base'][$i] );
                }

                if ( is_file( $downloaded_po_paths['path_copy'][$i] ) ) 
                {
                    unlink( $downloaded_po_paths['path_copy'][$i] );
                }
            }
        }
        else 
        {
            if ( is_file( $downloaded_po_paths['path_base'] ) ) 
            {
                unlink( $downloaded_po_paths['path_base'] );
            }

            if ( is_file( $downloaded_po_paths['path_copy'] ) ) 
            {
                unlink( $downloaded_po_paths['path_copy'] );
            }  
        }
    }

    /**
     * Verifies if the a *.po file contains only the header (i.e: the filter "untranslated" is applied to a project 
     * without untranslated strings).
     * 
     * @param string $po_file_path Path of the *.po file.
     * 
     * @return bool Indicates if the *.po file contains only the header. 
     */
    public function has_only_header( $po_file_path ) 
    {
        $result = false;

        $content_as_string = file_get_contents( $po_file_path );
        $content_as_string = rtrim( $content_as_string );
        
        $parts = explode( PHP_EOL, $content_as_string );

        if ( count( $parts ) === self::PO_HEADER_LENGTH ) 
        {
            $this->error_message = __( 'The query could not generate any strings to merge. Please check if the project is ' . 
                                       'already fully translated or contains only waiting translations.' );
            $result = true;
        }

        return $result;
    }
    
    
    /**
     * Downloads the *po files and returns their save paths.
     * 
     * @param string $url Homepage URL/translation URL of the plugin/theme.
     * @param string $base_locale Locale where the translations from the copy locale will be used.
     * @param string $copy_locale Locale used to get the translations that are not present in the base locale.
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return array|null Paths of the downloaded *.po files, or null if the parameters have errors.
     */
    public function start_pos_download( $url, $base_locale, $copy_locale, $core ) 
    {
        $result = null;
        $name = null;
        $type = null;
        
        // If the process is not on a core.
        if ( is_null( $core ) ) 
        {
            // Get the name and the type from the plugin's/theme's homepage URL/translation URL.
            $name = $this->get_name_from_url( $url );
            $type = $this->get_type_from_url( $url );
        }

        // Set the parameters of the result file(s).
        $this->result_params = array(
            'name' => $name,
            'type' => $type,
            'core' => $core,
            'locale' => $base_locale
        );
        
        $path_base = null;
        $path_copy = null;
        
        $params = array(
            'name'        => $name,
            'type'        => $type,
            'base_locale' => $base_locale,
            'copy_locale' => $copy_locale,
            'core'        => $core
        );

        // Download and save the *.po files.
        $result = $this->download_locales_pos( $params );

        return $result;
    }

    /**
     * Downloads and saves the base locale, the copy locale *po files and returns their save paths.
     * 
     * @param array *.po file parameters.
     * 
     * @return array|null Paths of the downloaded *.po files, or null if the parameters have errors.
     */
    public function download_locales_pos( $params = array() ) 
    {
        $path_base = null;
        $path_copy = null;
        
        $base_locale = $params['base_locale'];
        $copy_locale = $params['copy_locale'];
        
        unset( $params['base_locale'] );
        unset( $params['copy_locale'] );
        
        // Create, if necessary, the folder where the downloaded *.po files will be saved.
        if( !is_dir( $this->download_folder_path ) ) 
        {
            mkdir( $this->download_folder_path, 0770 );
        }

        $params['locale']  = $base_locale;
        $params['is_base'] = true;
        $path_base = $this->download_pos( $params );
        
        $params['locale']  = $copy_locale;
        $params['is_base'] = false;
        $path_copy = $this->download_pos( $params );
        
        if ( !is_null( $params['core'] ) ) 
        {
            $path_base = $path_base['core'];
            $path_copy = $path_copy['core'];
        }
        else 
        {
            $path_base = $path_base['single_po'];
            $path_copy = $path_copy['single_po'];
        }

        return array(
            'path_base' => $path_base,
            'path_copy' => $path_copy
        );
    }

    /**
     * Downloads and saves *po files.
     * 
     * @param array $params *.po file parameters.

     * @return array Associative array with the save paths of the downloaded *.po files, or with null values if
     * the parameters have errors.
     */
    public function download_pos( $po_params = array() ) 
    {
        $result = array(
            'single_po' => null,
            'core'      => null,
        );
        
        // For local testing, the file from the "tests" folder will be read.
        if ( isset( $this->params[self::TEST] ) ) 
        {
            $result = $this->tests_folder_path . $po_params['locale'] . '/' . $po_params['name'] . '-' . $po_params['locale'] . '.po';
        }
        else 
        {
            // Set the filters for the download url.
            $filters = array(
                self::STATUS    => $this->status_filters,
                self::DIFF_ONLY => ( isset( $this->params[self::DIFF_ONLY] ) ) ? $this->params[self::DIFF_ONLY] : null,
                self::USERNAME  => ( isset( $this->params[self::USERNAME] ) ) ? $this->params[self::USERNAME] : null
            );
            
            $env = null;

            // Verify if the release state was specified.
            if ( isset( $this->params[self::ENV] ) ) 
            {
                $env = $this->params[self::ENV];
            }
            else 
            {
                $env = self::RELEASE_STABLE;
            }

            $po_params['filters'] = $filters;
            $po_params['release'] = $env;
            $download_urls = $this->create_po_url( $po_params );
            $po_params['download_folder_path'] = $this->download_folder_path;
            $save_paths = $this->set_temp_path( $po_params );
                
            // Download all core projects. 
            if ( !is_null( $po_params['core'] ) ) 
            {
                $result['core'] = $this->download_multiple_pos( $save_paths['core'], $download_urls['core'] );

                // If download failed, try the dev project.
                if ( is_null( $result['core'] ) ) 
                {
                    $this->result_params['core'] = self::RELEASE_DEV;
                    $po_params['release'] = self::RELEASE_DEV;
                    $po_params['core'] = self::RELEASE_DEV;
                    $download_urls = $this->create_po_url( $po_params );
                    $save_paths = $this->set_temp_path( $po_params );
                    $result['core'] = $this->download_multiple_pos( $save_paths['core'], $download_urls['core'] );
                }
            }
            else 
            {
                // Download a single *.po file.
                $po_params['download_url'] = $download_urls['single_po'];
                $po_params['save_path']    = $save_paths['single_po'];
                
                $result['single_po'] = $this->download_single_po( $po_params );
                
                // If the download failed and the release was not explicitly specified, try the "dev" release.
                if ( is_null( $result['single_po'] ) ) 
                {
                    if ( !isset( $this->params[self::ENV] ) ) 
                    {
                        // If it's a plugin.
                        if ( $po_params['type'] == self::TYPE_TRANSLATION_PLUGINS ) 
                        {
                            $po_params['release'] = self::RELEASE_DEV;
                            $po_params['download_url'] = $this->create_po_url( $po_params )['single_po'];
                            $result['single_po'] = $this->download_single_po( $po_params );
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Downloads and saves a single *.po file.
     * 
     * @param array *.po file parameters.
     * 
     * @return string|null Path of the downloaded *.po file, or null if download failed.
     */
    public function download_single_po( $params = array() ) 
    {
        $result = null;
        
        // Verify if the file was downloaded successfully.
        if ( file_put_contents( $params['save_path'], @fopen( $params['download_url'], 'r' ) ) !== 0 ) 
        {
            $result = $params['save_path'];
        }
        else 
        {
            unlink( $params['save_path'] );
        }

        return $result;
    }
    
    /**
     * Downloads and saves multiple *.po files.
     * 
     * @param array $pos_save_paths Paths where the downloaded *.po files should be saved.
     * @param array $pos_download_urls Download URL of the *.po files.
     * 
     * @return array|null Paths of the downloaded *.po files, or null if the download failed.
     */
    public function download_multiple_pos( $pos_save_paths = array(), $pos_download_urls = array() ) 
    {
        $result = null;
        
        for ( $i = 0; $i < count( $pos_save_paths ); ++$i ) 
        {
            // Verify if the file was downloaded and saved successfully.
            if ( file_put_contents( $pos_save_paths[$i], @fopen( $pos_download_urls[$i], 'r' ) ) !== 0 ) 
            {
                $result[] = $pos_save_paths[$i];
            }
            else 
            {
                unlink( $pos_save_paths[$i] );
            }
        }

        return $result;
    } 


    /**
     * Creates the download URL for the *.po file(s).
     * 
     * @param array $url_params Array with the URL parameters.
     * 
     * @return string Download URL.
     */
    public function create_po_url( $url_params = array() ) 
    {
        $result = array(
            'single_po' => null,
            'core'      => null
        );

        $url_middle = $url_params['type'] . '/'. $url_params['name'] . '/';
        
        if ( !is_null( $url_params['core'] ) ) 
        {
            if ( $url_params['release'] == self::RELEASE_DEV) 
            {
                $url_middle = 'wp' . '/'. $url_params['release'] .'/';
            }
            else 
            {
                $url_middle = 'wp' . '/' . $url_params['core'] . '/';
            }
        }
        else 
        {
            if ( $url_params['type'] == self::TYPE_TRANSLATION_PLUGINS ) 
            {
                $url_middle .= $url_params['release'] . '/';
            }
            elseif ( in_array( $url_params['type'], $this->dev_url_types ) ) 
            {
                $url_middle .= self::RELEASE_DEV . '/';
            }
        }
        
        // If the process is on a core, create the download URL for all core projects. 
        if ( !is_null( $url_params['core'] ) )
        {
            $core_urls = array();
            
            $core_urls[] = self::URL_BASE . $url_middle . $url_params['locale'] . self::URL_END;
            
            foreach ( $this->core_sub_projects as $core_sub_project ) 
            {
                $core_sub_project .= '/';
                
                $core_urls[] = self::URL_BASE . $url_middle . $core_sub_project . $url_params['locale'] . self::URL_END;
            }
            $result['core'] = $core_urls;
        }
        else 
        {
            $result['single_po'] = self::URL_BASE . $url_middle . $url_params['locale'] . self::URL_END;
        }
        // If it's the base locale, set the filters. 
        if ( $url_params['is_base'] && !empty( array_filter( $url_params['filters'] ) ) )
        {
            // If the process is on a core, set the filters for all of the download URL.
            if ( !is_null( $url_params['core'] ) )
            {
                for ( $i = 0; $i < count( $result['core'] ); ++$i ) 
                {
                    $result['core'][$i] = $this->set_filters( $result['core'][$i], $url_params['filters'] ); 
                }
            }
            else 
            {
                $result['single_po'] = $this->set_filters(  $result['single_po'], $url_params['filters'] );
            }
            
        }
 
        return $result;
    }

    /**
     * Sets the filters to the download URL of a *.po file.
     * 
     * Note: applying the filters to the copy locale doesn't make sense in the context, since we want to obtain 
     * the current, aprouved translations from the copy locale.
     * 
     * @param string $download_url Download URL of a *.po file.
     * @param array $filters Filters to set.
     * 
     * @return string Download URL with applied filters.
     */
    public function set_filters( $download_url, $filters = array() ) 
    {
        $status_filter = self::FILTER_BASE . self::STATUS_FILTER . self::FILTER_SETTER;
        $user_filter   = self::FILTER_BASE . self::USER_FILTER . self::FILTER_SETTER;
        
        $download_url .= '?';

        if ( !empty( $filters[self::STATUS] ) ) 
        {
            foreach ( $filters[self::STATUS] as $filter ) 
            {
                $download_url .= $status_filter .  $filter . self::FILTER_SEPARATOR;
            }
        }
        
        if ( !is_null( $filters[self::DIFF_ONLY] ) ) 
        {
            $download_url .= $status_filter . self::UNTRANSLATED . self::FILTER_SEPARATOR;
        }

        if ( !is_null( $filters[self::USERNAME] ) ) 
        {
            $download_url .= $user_filter . urlencode( $filters[self::USERNAME] );
        }
        
        return $download_url;
    }
    
    /**
     * Gets the name of a project from the URL (main page/translate).
     * 
     * @param string $url URL of a project.
     * 
     * @return string|null Name of the project, or null if the URL is invalid.
     */
    public function get_name_from_url( $url ) 
    {
        $result = null;
        $parts = explode( '/', parse_url( $url )['path'] );

        if ( count( $parts )  >= 3 ) 
        {
            if ( $this->is_translate_host ) 
            {
                $result = $parts[5];
            }
            else
            {
                $result = $parts[2];
                
            }
        }

        return $result;
    }

    /**
     * Gets the type of a project from the URL (main page/translate).
     * 
     * @param string $url URL of the project.
     * 
     * @return string|null Type of the project, or null if the URL is invalid.
     */
    public function get_type_from_url( $url ) 
    {
        $result = null;
        
        $parts = explode( '/', parse_url( $url )['path'] );
        
        if ( count( $parts ) >= 2 ) 
        {
            if ( $this->is_translate_host ) 
            {
                $result = $parts[4];
            }
            else
            {
                $result = $parts[1];
            }
        }
    
        // Set the type to match the format in the download URL.
        if ( $result == self::TYPE_PLUGINS ) 
        {
            $result = self::TYPE_TRANSLATION_PLUGINS;
        }
        elseif ( $result == self::TYPE_THEMES ) 
        {
            $result = self::TYPE_TRANSLATION_THEMES;
        }
        
        return $result;
    }

    /**
     * Gets the major core version, so it can be used
     * in the download URL.
     * 
     * @param string $core_version Core version.
     * 
     * @return string|null Major core version, or null if
     * the received argument is invalid.
     */
    public function get_major_core_version( $core_version ) 
    {
        $result = null;
        $parts  = explode( '.', $core_version );
        
        if ( count( $parts ) >= 2 ) 
        {
            $is_int = true;

            foreach( $parts as $part ) 
            {
                // Verify if the part is an integer.
                if ( filter_var( $part, FILTER_VALIDATE_INT ) === false ) 
                {
                    $is_int = false;
                    break;
                }   
            }

            if ( $is_int ) 
            {
                $result = $parts[0] . '.' . $parts[1];
            }
        }
   
        return $result;
    }

    /**
     * Splits the string containing the filters into array based on the provided delimiter. 
     * Verifies if each filter is valid and puts it into the corresponding array.
     * 
     * @param string $filters_string String containing the filters separated by a delimiter.
     * 
     * @return array Multidimensional associative array containing the valid and the invalid filters.
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
            'valid_filters'   => $valid_filters,
            'invalid_filters' => $invalid_filters
        );
    }

    /**
     * Sets the name of the result file.
     *
     * @return string Name of the result file.
     */
    public function set_result_name() 
    {
        $result = null;
        
        if ( !is_null( $this->result_params['core'] ) ) 
        {
            $result = array();
            
            $result[] = 'wp-' . $this->result_params['core'] . '-' . $this->result_params['locale'] . '-merged.po';

            foreach ( $this->core_sub_projects as $sub_project ) 
            {
                if ( $sub_project == self::CORE_ADMIN_NET ) 
                {
                    $sub_project = 'admin-network';
                }
                
                $result[] = 'wp-' . $this->result_params['core']. '-' . $sub_project . '-' . $this->result_params['locale'] . '-merged.po';
            }
        }
        else 
        {
            // Set the "wp-" prefix if the type lacks it.
            if ( strncasecmp( 'wp-', $this->result_params['type'], 3 ) !== 0 ) 
            {
                $this->result_params['type'] = 'wp-'. $this->result_params['type'];
            }
            
            $result = $this->result_params['type'] . '-' . $this->result_params['name'] . '-' . $this->result_params['locale'] . '-merged.po';
        }
        
        return $result;
    }
    
    /**
     * Sets the temporary saving path for the downloaded *.po file.
     * 
     * @param array $params Array with the file parameters.
     * 
     * @return array Multidimensional associative array with the temporary save paths of the downloaded *.po files, 
     * or with null values if the parameters have errors.
     */
    public function set_temp_path( $params = array() ) 
    {
        $result = array(
            'single_po' => null,
            'core'      => null
        );

        $path_base = $params['download_folder_path'];

        // Set the "wp-" prefix if the type lacks it.
        if ( strncasecmp( 'wp-', $params['type'], 3 ) !== 0 ) 
        {
            $params['type'] = 'wp-'. $params['type'];
        }

        if ( !is_null( $params['core'] ) ) 
        {
            $core_paths = array();
            $core_paths[] = $path_base . 'wp-' . $params['core'] . '-' . $params['locale'] . '-temp.po';
            
            foreach( $this->core_sub_projects as $core_sub_project ) 
            {
                if ( $core_sub_project == self::CORE_ADMIN_NET ) 
                {   
                    $core_sub_project = 'admin-network';
                }
                
                $core_paths[] = $path_base . 'wp-' . $params['core'] . '-' . $core_sub_project . '-' . $params['locale'] . '-temp.po';
            }

            $result['core'] = $core_paths; 
        }
        else 
        {
            $result['single_po'] = $path_base . $params['type'] . '-' . $params['name'] . '-' . $params['locale'] . '-temp.po';
        }

        return $result;
    }

    /**
     * Creates the result file(s).
     * 
     * @param array $merged_content Content with the merged translations.
     * @param string $result_name Name(s) of the file(s).
     */
    public function create_result_file( $merged_content = array(), $result_name ) 
    {
        $content_as_string = null;

        // Transform the array's contents into string.
        if ( is_array( $merged_content[0] ) ) 
        {
            for ( $i = 0; $i < count( $merged_content ); ++$i ) 
            {
                $content_as_string = implode( '', $merged_content[$i] );
                $filename = getcwd() . '/' . $result_name[$i];
                file_put_contents( $filename, rtrim ( $content_as_string ) );
            }
        }
        else 
        {
            $content_as_string = implode( '', $merged_content );
            $filename = getcwd() . '/' . $result_name;
            file_put_contents( $filename, rtrim ( $content_as_string ) );
        }
    }
}
