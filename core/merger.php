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
     * Parameter constants.
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
     * Status filter constants.
     */
    const CURRENT      = 'current';
    const UNTRANSLATED = 'untranslated';
    const FUZZY_FILTER = 'fuzzy';
    const WAITNING     = 'waiting';
    const REJECTED     = 'rejected';
    const OLD          = 'old'; 

    /**
     * Default parts of a *.po download URL.
     */
    const URL_BASE = 'https://translate.wordpress.org/projects/';
    const URL_END  = '/default/export-translations';

    /**
     * Plugin releases.
     */
    const RELEASE_STABLE = 'stable';
    const RELEASE_DEV    = 'dev';

    /**
     * Type defined in the homepage URL of a plugin/theme.
     */
    const URL_PLUGINS    = 'plugins';
    const URL_THEMES     = 'themes';
    
    /**
     * Type defined in the translation URL of a plugin/theme.
     */
    const TRANS_URL_PLUGINS = 'wp-plugins';
    const TRANS_URL_THEMES  = 'wp-themes';

    /**
     * Folder in the root directory where the downloaded base locale and the copy locale *.po files will be saved.
     */
    const DOWNLOAD_FOLDER = 'downloads';

    /**
     * Folder where the files for local testing are located.
     */
    const TESTS_FOLDER = 'tests/local';
    
    /**
     * Delimiter in the string specified by the "status" optional parameter.
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
     * Filters for the *.po download URL provided by the "status" parameter.
     */
    protected $status_filters = array();

    /**
     * Contents of the file specified by the "fuzzy" parameter.
     */
    protected $fuzzy_strings = array();

    /**
     * Major core version for the download URL (ex: 5.0.x).
     */
    protected $major_core = null;

    /**
     * Path to the download folder in the root directory of the package.
     */
    protected $download_folder_path = null;

    /**
     * The path to the tests folder in the root directory of the package.
     */
    protected $tests_folder_path = null;

    /**
     * Latest error that occured.
     */
    protected $error_message = null;
    
    /**
     * Name of the result file.
     */
    protected $result_name = null;

    /**
     * Indicates if the "mark-copy-as-fuzy" parameter is set.
     */
    protected $is_mcaf = false;

    /**
     * Specifies if the downloaded *.po files should be kept in the "downloads" folder.
     */
    protected $keep_downloaded_pos = false;

    /**
     * Indicates if the domain is translate.wordpress.org.
     */
    protected $is_translate_host = false;
    
    /**
     * Indicates if the source of the *.po file is an URL
     */
    protected $is_url;
    
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
     * Extracts the translations from the copy locale *.po file, merges them with the 
     * content from the base locale and saves the result in a new file.
     */
    public function merge() 
    {    
        $po_extractor  = new Po_Extractor( $this->copy_content );

        $po_merger = new Po_Merger( $this->base_content, $po_extractor->extract_msgs(), 
                                    $this->fuzzy_strings, $this->is_mcaf );
        
        $this->create_result_file( $po_merger->merge_po(), $this->result_name );
    }

    /**
     * Verifies if the parameters are valid, then attempts to download the *.po files 
     * and to extract information from them, thus verifying that the downloaded files are also valid.
     * 
     * @return boolean True if valid. Otherwise, false.
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
            // Verify the parameters.
            $result = $this->verify_params( $this->params );
    
            // If the parameters are valid, attempt to download and read the *.po files
            if ( $result ) 
            {
                if ( $this->is_url ) 
                {
                    $result = $this->process_pos( $this->params[self::PO_SOURCE], $this->params[self::BASE_LOCALE], $this->params[self::COPY_LOCALE], null );
                }
                else 
                {
                    $result = $this->process_pos( null, $this->params[self::BASE_LOCALE], $this->params[self::COPY_LOCALE], $this->major_core );
                }
            }
        }

        return $result;
    }

    /**
     * Verifies if the paramaters is an URL
     * 
     * @param $param Parameter to verify
     * 
     * @return boolean True if valid. Otherwise, false.
     */
    public function is_url( $param ) 
    {
        $result = false;
        
        $param = strtolower( $param );

        $parsed_param = parse_url( $param );

        if ( filter_var( $param, FILTER_VALIDATE_URL ) && ( $parsed_param['scheme']  == 'https' || $parsed_param['scheme']  == 'http' ) ) 
        {
            $result = true;
        }
        
        return $result;
    }


     /**
     * Verifies the parameters.
     * 
     * @param array $params Parameters to verify.
     * 
     * @return boolean True if valid. Otherwise, false.
     */
    public function verify_params( $params = array() ) 
    {        
        $result = false;

        // Count how many parameters are valid.
        $count_valid = 0;
       
        // Verify if the names of the parameters and their values are valid.
        foreach ( array_keys( $params ) as $param ) 
        {
            if ( in_array( $param, $this->valid_params ) ) 
            {
                ++$count_valid;

                switch ( $param ) 
                {
                    case $param == self::PO_SOURCE:
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
                            // Attempt to get the major WordPress core version for the download URL.
                            $this->major_core = $this->create_core_version_for_url( $source );
                    
                            if ( is_null( $this->major_core) ) 
                            {
                                --$count_valid;
                                $this->error_message = __( 'The core version is invalid.' );
                                break 2;
                            }
                        }
                        break; 
                    
                    case $param == self::FUZZY:
                        if ( !is_file( $params[self::FUZZY] ) ) 
                        {
                            --$count_valid;
                            $this->error_message = __( "Failed to open the file: " . $params[self::FUZZY] . " doesn't exist." );
                            break 2;
                        }
                        break;
                    
                    case $param == self::STATUS:
                        $received_filters = $this->get_filters( $params[self::STATUS] );

                        // Set the error message with each invalid filter.
                        if ( !empty( $received_filters['invalid'] ) ) 
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
                // Set the error message with the invalid optional parameter's name.
                $this->error_message = __( 'Invalid parameter: ' . $param . '.' );
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
     * Returns the latest error message.
     * 
     * @return string Error message.
     */
    public function get_error_message()
    {
        return $this->error_message;
    }

    /**
     * Returns the path of the download folder in the root of the package.
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
     * @return string $result Major core version for the download url, 
     * or null if the argument was invalid.
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
     * Verifies that an URL is a valid plugin/theme homepage URL/translation URL.
     * 
     * @param string $url URL to validate.
     * 
     * @return boolean True if valid. Otherwise, false.
     */
    public function is_valid_url( $url ) 
    {
        $result = true;

        $url = strtolower( $url );
        $url_parts = parse_url( $url );
        
        // Host validation.
        if ( !$this->endsWith( $url_parts['host'], "wordpress.org" ) ) 
        {
            $this->error_message = __( 'The host (' . $url_parts['host'] . ') in the URL is invalid.' );
            $result = false;
        }
        else
        {
            if ( $url_parts['host'] == 'translate.wordpress.org' ) 
            {
                $this->is_translate_host = true;
            }
        }

        // Path validation.
        if ( isset( $url_parts['path'] ) ) 
        {
            $path_parts = explode( '/', $url_parts['path'] );

            if ( count( $path_parts ) < 2 ) 
            {
                $this->error_message = __( 'The theme/plugin URL is invalid.' );
                $result = false;
            }

            if ( !in_array( $path_parts[1], array( self::URL_THEMES, self::URL_PLUGINS ) ) && $path_parts[2] != self::TRANS_URL_PLUGINS ) 
            {
                $is_trans_url_themes = false;
                
                if ( isset( $path_parts[4] ) ) 
                {
                    if ( $path_parts[4] == self::TRANS_URL_THEMES ) 
                    {
                        $is_trans_url_themes = true;
                    }
                }
                
                if ( !$is_trans_url_themes ) 
                {
                    $this->error_message = __( 'The URL type (plugins or themes) could not be detected.' );
                    $result = false;
                }
            }

            if ( empty( $path_parts[2] ) && empty( $path_parts[3] ) )
            {
                $this->error_message = __( 'The theme/plugin slug in the URL cannot be empty.' );
                $result = false;
            }
        }
        else 
        {
            $this->error_message = __( 'The theme/plugin URL is invalid.' );
            $result = false;
        }

        return $result;
    }

    /**
     * Validate if a string ends with a certain substring.
     * 
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * 
     * @return boolean True if it ends with the needle. Otherwise, false.
     */
    function endsWith( $haystack, $needle )
    {
        $length = strlen( $needle );
        
        if ( $length == 0 ) 
        {
            return true;
        }
    
        return ( substr( $haystack, -$length ) === $needle );
    }

    /**
     * Downloads the base locale, the copy locale *.po files and attempts to read their contents.
     * 
     * @param string $url The homepage url of the plugin/theme.
     * @param string $base_locale Locale where the translations from the copy locale will be used.
     * @param string $copy_locale Locale used to get translations that are not present in the base locale.
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return boolean True if the process was successful. Otherwise, false.
     */
    public function process_pos( $url, $base_locale, $copy_locale, $core ) 
    {
        $result = false;
        
        // Download the base and copy locale *.po files.
        $downloaded_po_paths = $this->download_pos( $url, $base_locale, $copy_locale, $core );

        // If the *.po files were downloaded successfully, extract the contents of the downloaded *.po files into arrays.
        if ( !is_null( $downloaded_po_paths['path_base'] ) && !is_null( $downloaded_po_paths['path_copy'] ) ) 
        {   
            // Verify if an empty *.po file was downloaded (ex: the filter "untranslated" is applied to a project without untranslated strings).
            $content_as_string = file_get_contents( $downloaded_po_paths['path_base'] );
            $content_as_string = rtrim( $content_as_string );
            $parts = explode( PHP_EOL, $content_as_string );
            
            if ( count( $parts ) > 12 ) 
            {
                $this->base_content = file( $downloaded_po_paths['path_base'] );
                $this->copy_content = file( $downloaded_po_paths['path_copy'] );
                
                $result = true;
            }
            else 
            {
                $this->error_message = __( 'The query could not generate any strings to merge. Please check if the project is already fully translated.' );
            }
        }
        else
        {
            if ( is_null( $downloaded_po_paths['path_base'] ) && !is_null( $downloaded_po_paths['path_copy'] ) ) 
            {   
                $this->error_message = __( 'The base locale is invalid.' );
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
            if ( is_file( $downloaded_po_paths['path_base'] ) ) 
            {
                unlink( $downloaded_po_paths['path_base'] );
            }

            if ( is_file( $downloaded_po_paths['path_copy'] ) ) 
            {
                unlink( $downloaded_po_paths['path_copy'] );
            }  
        }
        
        return $result;
    }

    /**
     * Downloads and saves the base locale, the copy locale *po files.
     * 
     * @param string $url The homepage URL/translation URL of the plugin/theme.
     * @param string $base_locale Locale where the translations from the copy locale will be used.
     * @param string $copy_locale Locale used to get the translations that are not present in the base locale.
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return array Paths of the downloaded *.po files.
     */
    public function download_pos( $url, $base_locale, $copy_locale, $core ) 
    {
        $name = null;
        $type = null;
        
        // Create, if necessary, the folder where the downloaded *.po files will be saved.
        if( !is_dir( $this->download_folder_path ) ) 
        {
            mkdir( $this->download_folder_path, 0770 );
        }
        
        // If the process is not on a core.
        if ( is_null( $core ) ) 
        {
            // Get the name and the type from the plugin's/theme's homepage URL/translation URL.
            $name = $this->get_name_from_url( $url );
            $type = $this->get_type_from_url( $url );
        }

        // Set the name under which the result file will be saved.
        $this->result_name = $this->set_result_name( $name, $type, $base_locale, $core );
    
        // Download and save the *.po files.
        $path_base = $this->download_po( $name, $type, $base_locale, $core, true );
        $path_copy = $this->download_po( $name, $type, $copy_locale, $core, false );

        return array(
            'path_base' => $path_base,
            'path_copy' => $path_copy
        );
    }

    /**
     * Downloads and saves a *.po file.
     * 
     * @param string $name Name of the plugin/theme.
     * @param string $type Type (plugin/theme).
     * @param string $locale
     * @param string $core Major core version, or null if the "core" parameter is not set.
     * @param boolean $is_base Indicates if it's a base locale.
     * 
     * @return string $result Save path of the downloaded *.po file.
     */
    public function download_po( $name, $type, $locale, $core, $is_base ) 
    {
        $result = null;
        
        // For local testing, the file from the "tests" folder will be read.
        if ( isset( $this->params[self::TEST] ) ) 
        {
            $result = $this->tests_folder_path . $locale . '/' . $name . '-' . $locale . '.po';
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

            // Verify if the environment was specified
            if ( isset( $this->params[self::ENV] ) ) 
            {
                $env = $this->params[self::ENV];
            }
            else 
            {
                $env = self::RELEASE_STABLE;
            }
            
            // Parametres for the path of the file and the download URL.
            $params = array(
                'name'    => $name,
                'type'    => $type,
                'locale'  => $locale,
                'core'    => $core,
                'is_base' => $is_base,
                'filters' => $filters,
                'plugin_release'       => $env,
                'download_folder_path' => $this->download_folder_path
            );
            
            $download_url = $this->create_po_url( $params );
            $save_path = $this->set_temp_path( $params );
                
            // Verify if the file was downloaded successfully.
            if ( file_put_contents( $save_path, @fopen( $download_url, 'r' ) ) !== 0 ) 
            {
                $result = $save_path;
            }
            else
            {
                // If there was an error and it's a plugin, try to download the dev release. 
                if ( !isset( $this->params[self::ENV] ) ) 
                {
                    if ( $type == self::URL_PLUGINS ) 
                    {
                        $params['plugin_release'] = self::RELEASE_DEV;

                        $download_url = $this->create_po_url( $params );
                    
                        if ( file_put_contents( $save_path, @fopen( $download_url, 'r' ) ) !== 0 ) 
                        {
                            $result = $save_path;
                        }
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * Creates the download URL of a *.po file.
     * 
     * @param array $url_params Array with the URL parameters.
     * 
     * @return string $result Download URL.
     */
    public function create_po_url( $url_params = array() ) 
    {
        $result = null;

        $url_middle = 'wp-' . $url_params['type'] . '/'. $url_params['name'] . '/';
        
        if ( !is_null( $url_params['core'] ) ) 
        {
            $url_middle = 'wp' . '/' . $url_params['core'] . '/';
        }
        else 
        {
            if ( $url_params['type'] == self::URL_PLUGINS ) 
            {
                $url_middle .= $url_params['plugin_release'] . '/';
            }
        }
        
        $result = self::URL_BASE . $url_middle . $url_params['locale'] . self::URL_END;

        // If it's the base locale, set the filters. 
        if ( $url_params['is_base'] && !empty( array_filter( $url_params['filters'] ) ) )
        {
            $result = $this->set_filters( $result, $url_params['filters'] );
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
     * @return string $result Download URL of a *.po file with filters.
     */
    public function set_filters( $download_url, $filters = array() ) 
    {
        $result = null;
        
        $filter_base = 'filters%5B';
        $filter_set  = '%5D=';
        $filter_end  = '&';
        
        $status_filter = $filter_base . 'status' . $filter_set;
        $user_filter   = $filter_base . 'user_login' . $filter_set;
        
        $download_url .= '?';

        if ( !empty( $filters[self::STATUS] ) ) 
        {
            foreach ( $filters[self::STATUS] as $filter ) 
            {
                $download_url .= $status_filter .  $filter . $filter_end;
            }
        }
        
        if ( !is_null( $filters[self::DIFF_ONLY] ) ) 
        {
            $download_url .= $status_filter . self::UNTRANSLATED . $filter_end;
        }

        if ( !is_null( $filters[self::USERNAME] ) ) 
        {
            $download_url .= $user_filter . urlencode( $filters[self::USERNAME] );
        }
        
        $result = $download_url;
        
        return $result;
    }
    
    /**
     * Gets the name of a plugin/theme from its homepage URL/translation URL.
     * 
     * @param string $url Homepage URL of a plugin/theme.
     * 
     * @return string $result Name of the plugin/theme.
     */
    public function get_name_from_url( $url ) 
    {
        $result = null;
        $parts = explode( '/', parse_url( $url )['path'] );
        $size = count( $parts );

        if ( $size  >= 3 ) 
        {
            if ( !$this->is_translate_host ) 
            {
                $result = $parts[2];
            }
            else
            {
                if ( $size <= 5 ) 
                {
                    $result = $parts[3];
                }
                elseif ( $size <= 7 ) 
                {
                    $result = $parts[5];
                }
            }
        }

        return $result;
    }

    /**
     * Gets the type (plugin/theme) from the plugin's/theme's homepage URL/translation URL.
     * 
     * @param string $url Homepage URL of a plugin/theme.
     * 
     * @return string $result Type (plugin/theme).
     */
    public function get_type_from_url( $url ) 
    {
        $result = null;
        
        $parts = explode( '/', parse_url( $url )['path'] );
        
        if ( count( $parts ) >= 2 ) 
        {
            if ( !$this->is_translate_host ) 
            {
                $result = $parts[1];
            }
            else
            {
                if ( $parts[2] == self::TRANS_URL_PLUGINS ) 
                {
                    $result = self::URL_PLUGINS;
                }
                elseif ( $parts[4] == self::TRANS_URL_THEMES ) 
                {
                    $result = self::URL_THEMES;
                }
            }
        }
        
        return $result;
    }

    /**
     * Gets the major core version, so it can be used
     * in the download URL.
     * 
     * @param string $core Core version.
     * 
     * @return string $result Major core version, or null if
     * the received argument is invalid.
     */
    public function get_major_core_version( $core ) 
    {
        $result = null;
        $parts  = explode( '.', $core );
        
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
                $major_core = $parts[0] . '.' . $parts[1];

                // Validate the format and the minimal core version.
                if ( preg_match( '/^([0-9]{1,2}\.[0-9]{1,3})$/', $major_core ) && (double)$major_core >= 0.70 ) 
                {
                    $result = $major_core;
                }
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
            'valid_filters' => $valid_filters,
            'invalid_filters' => $invalid_filters
        );
    }

    /**
     * Sets the name of the result file.
     * 
     * @param string $name Name of the plugin/theme.
     * @param string $type Type (plugin/theme).
     * @param string $locale
     * @param string $core Major core version, or null if the parameter is not set.
     * 
     * @return string $result The name of the result file.
     */
    public function set_result_name( $name, $type, $locale, $core ) 
    {
        $result = null;
        
        if ( !is_null( $core ) ) 
        {
            $result = 'wp-' . $core . '-' . $locale . '-merged.po';
        }
        else 
        {
            $result = 'wp-' . $type . '-' . $name . '-' . $locale . '-merged.po';
        }
        
        return $result;
    }
    
    /**
     * Sets the temporary saving path for the downloaded *.po file.
     * 
     * @param array $url_params Array with the file parameters.
     * 
     * @return string $result Temporary save path of the downloaded file.
     */
    public function set_temp_path( $file_params = array() ) 
    {
        $result = null;

        $path_base = $result = $file_params['download_folder_path'] . 'wp-';

        if ( !is_null( $file_params['core'] ) ) 
        {
            $result = $path_base . $file_params['core'] . '-' . 
                $file_params['locale'] . '-temp.po';
        }
        else 
        {
            $result = $path_base . $file_params['type'] . '-' . 
                $file_params['name'] . '-' . $file_params['locale'] . '-temp.po';
        }
        
        return $result;
    }

    /**
     * Creates the result file.
     * 
     * @param array $merged_content Content with the merged translations.
     * @param string $result_name Name of the file.
     */
    public function create_result_file( $merged_content = array(), $result_name ) 
    {
        $content_as_string = null;

        // Transform the array's contents into string.
        foreach ( $merged_content as $string ) 
        {
            $content_as_string .= $string;
        }

        // Set the file name path based on where the command "wp po merge" was executed.
        $filename = getcwd() . '/' . $result_name;
        
        file_put_contents( $filename, rtrim ( $content_as_string ) );
    }
}
function __( $text ) {return $text;}