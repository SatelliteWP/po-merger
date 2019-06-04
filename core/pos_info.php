<?php

/**
 * WP PO Merger - Po Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.0.0
 * @author SatelliteWP <info@satellitewp.com>
 */

namespace satellitewp\po;

/**
 * POs Informations to merge data
 */
class Pos_Info
{
    /**
     * Types defined in the URL.
     */
    const TYPE_URL_PLUGINS = 'plugins';
    const TYPE_URL_THEMES  = 'themes';
    const TYPE_URL_META    = 'meta';
    const TYPE_URL_APPS    = 'apps';

    const TYPE_URL_TRANSLATION_PLUGINS = 'wp-plugins';
    const TYPE_URL_TRANSLATION_THEMES  = 'wp-themes';

    /**
     * Release states.
     */
    const RELEASE_STABLE         = 'stable';
    const RELEASE_STABLE_README  = 'stable';
    const RELEASE_DEV            = 'dev';
    const RELEASE_DEV_README     = 'dev-readme';
    const RELEASE_NOTES          = 'release-notes';

    
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
     * Default parts of a PO file download URL.
     */
    const URL_BASE = 'https://translate.wordpress.org/projects/wp';
    const URL_END  = '/default/export-translations/?format=po';

    /**
     * Types defined in a translate URL.
     */
    public static $url_translate_types = array( self::TYPE_URL_TRANSLATION_PLUGINS, 
                                            self::TYPE_URL_TRANSLATION_THEMES, 
                                            self::TYPE_URL_META, 
                                            self::TYPE_URL_APPS
                                        ); 

    protected $base_locale = null;

    protected $copy_locale = null;

    protected $base_filename = null;

    protected $copy_filename = null;

    protected $core_version = null;
    
    protected $url_version = null;

    protected $url = null;

    protected $slug = null;

    protected $is_core = false;

    protected $env = null;

    protected $filters = array();


    /**
     * Core type (if WordPress core only)
     * 
     * Possible values: 'main', 'cc', 'admin', 'admin-network'
     */
    protected $core_sub_project_type = 'main';

    /**
     * Constructor
     */
    public function __construct( $base_locale, $copy_locale ) 
    {
        $this->base_locale = $base_locale;
        $this->copy_locale = $copy_locale;
    }

    public function get_internal()
    {
        return array(
            'base_locale' => $this->base_locale,
            'copy_locale' => $this->copy_locale,
            'url' => $this->url,
            'core_version' => $this->core_version,
            'is_core' => $this->is_core,
            'slug' => $this->slug,
            'env' => $this->env,
            'filters' => $this->filters
        );
    }

    public function set_url( $url ) 
    {
        $this->url = $url;
    }

    public function set_base_filename( $filename )
    {
        $this->base_filename = $filename;
    }

    public function get_base_filename()
    {
        return $this->base_filename;
    }

    public function set_copy_filename( $filename )
    {
        $this->copy_filename = $filename;
    }

    public function get_copy_filename()
    {
        return $this->copy_filename;
    }

    public function set_core_version( $version ) 
    {
        $this->is_core = true;
        $this->core_version = $version;

        $this->set_url_core_version( $version );
    }

    public function is_core()
    {
        return $this->is_core;
    }

    public function get_base_download_filename( $suffix = '' )
    {
        return $this->get_download_filename( $this->base_locale, $suffix );
    }

    public function get_copy_download_filename( $suffix = '' )
    {
        return $this->get_download_filename( $this->copy_locale, $suffix );
    }

    /**
     * Returns the name of the result file.
     *
     * @return string Name of the result file.
     */
    public function get_download_filename( $locale, $suffix = '' )
    {
        $result = null;
        
        if ( $this->is_core ) 
        {   
            $sub_project = '';
            if ( $this->core_sub_project_type != 'main' )
            {
                $sub_project = $this->core_sub_project_type . '-';
            }

            $result = 'wp-' . $this->url_version . '-' . $sub_project . $locale . $suffix . '.po';
        }
        else 
        {
            $type = $this->get_project_type();
            $result = $type;

            // Set the "wp-" prefix if the type lacks it.
            if ( substr( $type, 0, 3 ) != 'wp-' )
            {
                $result = 'wp-' . $result;
            }
            
            $result .=  '-' . $this->get_project_name() . '-' . $locale . $suffix . '.po';
        }
        
        return $result;
    }

    /**
     * Gets the name of a project from the URL (main page/translate).
     * 
     * @return string Name of the project, or null if the URL is invalid.
     */
    public function get_project_name() 
    {
        $result = null;

        if ( $this->is_core ) 
        {
            $result = $this->get_core_project_name();
        }
        else
        {
            $url_parts = parse_url( $this->url );
            $parts = explode( '/', $url_parts['path'] );
            $count = count( $parts );
    
            if ( $count >= 6 && $url_parts['host'] == self::TRANSLATION_HOST ) 
            {
                $result = $parts[5];
            }
            elseif ( $count >= 3 )
            {
                $result = $parts[2];
            }
        }

        return $result;
    }

    /**
     * Gets the type of a project from the URL (main page/translate).
     * 
     * Values can be: 'plugins' or 'themes'
     * 
     * @return string Type of the project, or null if the URL is invalid.
     */
    public function get_project_type() 
    {
        $result = null;

        if ( $this->is_core ) return $result;
        
        $url_parts = parse_url( $this->url );
        $parts = explode( '/', $url_parts['path'] );
        $count = count( $parts );

        $type = null;
        if ( $count >= 5 && $url_parts['host'] == self::TRANSLATION_HOST ) 
        {
            $type = $parts[4];
        }
        elseif ( $count >= 2 )
        {
            $type = $parts[1];
        }
    
        switch( $type )
        {
            case self::TYPE_URL_PLUGINS:
            case self::TYPE_URL_TRANSLATION_PLUGINS:
                $result = self::TYPE_URL_PLUGINS;
                break;
            
            case self::TYPE_URL_THEMES:
            case self::TYPE_URL_TRANSLATION_THEMES:
                $result = self::TYPE_URL_THEMES;
                break;
            
            default:
                $result = $type;
        }
        
        return $result;
    }

    /**
     * Gets the major core version, so it can be used in the download URL.
     * 
     * @return string Major core version, or null if the received argument is invalid.
     */
    public static function get_major_core_version( $version ) 
    {
        $result = null;
        $parts  = explode( '.', $version );
        
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

    public function set_sub_project_type( $type )
    {
        $this->core_sub_project_type = $type;
    }

    public function get_sub_project_type()
    {
        return $this->core_sub_project_type;
    }

    public function get_core_project_name()
    {
        if ( $this->core_sub_project_type == 'main' )
        {
            return 'Core';
        }

        return $this->get_core_sub_projects_infos()[$this->core_sub_project_type];
    }

    public function get_core_sub_projects_infos()
    {
        return array(
            'cc' => 'Continents & Cities',
            'admin' => 'Administration',
            'admin-network' => 'Network Admin'
        );
    }

    /**
     * Create sub projects related to the WordPress core
     * 
     * @return array Sub projects  
     */
    public function get_core_sub_projects()
    {
        $result = array();

        if ( $this->is_core && $this->core_sub_project_type == 'main' )
        {
            foreach( $this->get_core_sub_projects_infos() as $type => $description )
            {
                $pi = new Pos_Info( $this->base_locale, $this->copy_locale );
                $pi->set_core_version( $this->core_version );
                $pi->set_sub_project_type( $type );
                $pi->set_env( $this->env );
                $pi->set_download_filters( $this->filters );
                $result[] = $pi;
            }
        }
        return $result;
    }

    public function set_env( $env )
    {
        $this->env = $env;
    }

    public function set_download_filters( $filters )
    {
        $this->filters = $filters;
    }

    public function get_base_download_url() 
    {
        return $this->get_download_url ( $this->base_locale );
    }

    public function get_copy_download_url() 
    {
        return $this->get_download_url ( $this->copy_locale );
    }


    /*
        core           https://translate.wordpress.org/projects/wp/5.1.x/fr-ca/default/export-translations/?format=po
        core-dev       https://translate.wordpress.org/projects/wp/dev/fr-ca/default/export-translations/?format=po
        admin:         https://translate.wordpress.org/projects/wp/dev/admin/fr-ca/default/export-translations/?format=po
        admin/network: https://translate.wordpress.org/projects/wp/dev/admin/network/fr-ca/default/export-translations/?format=po

        plugin:        https://translate.wordpress.org/projects/wp-plugins/events-manager/stable/fr-ca/default/export-translations/?format=po
        theme :        https://translate.wordpress.org/projects/wp-themes/weaver-xtreme/fr-ca/default/export-translations/?format=po

        meta:          https://translate.wordpress.org/projects/meta/wordcamp/fr-ca/default/export-translations/?format=po
        app:           https://translate.wordpress.org/projects/apps/ios/release-notes/fr-ca/default/export-translations/
    */
    public function get_download_url( $locale ) 
    {
        $result = self::URL_BASE;

        if ( $this->is_core ) 
        {
            $sub_project = '';
            if ( $this->core_sub_project_type != 'main' )
            {
                $sub_project = str_replace( '-', '/' , $this->core_sub_project_type ) . '/';
            }
            $result .= '/' . $this->url_version . '/' . $sub_project;   
        }
        else 
        {
            $type = $this->get_project_type();
            $result .= '-' . $type . '/'. $this->get_project_name() . '/';

            if ( $type == self::TYPE_URL_PLUGINS ) 
            {
                $result .= ($this->env == null ? 'stable' : $this->env ) . '/';
            }
            elseif ( $type == self::TYPE_URL_APPS ) 
            {
                $result .= self::RELEASE_DEV . '/';
            }
        }

        $result .= $locale . self::URL_END;

        if ($this->is_core || $locale == $this->base_locale )
        {
            $result = $this->get_filtered_url( $result );
        }
 
        return $result;
    }


    /**
     * Returns the filtered URL to a base PO file.
     * 
     * Note: applying the filters to the copy locale doesn't make sense in the context, since we want to obtain 
     * the current, approved translations from the copy locale.
     * 
     * @param string $url URL to add filter
     * 
     * @return string Url with filters
     */
    public function get_filtered_url( $url ) 
    {
        $result = "";
        $status_filter = self::FILTER_BASE . 'status' . self::FILTER_SETTER;
        $user_filter   = self::FILTER_BASE . 'user_login' . self::FILTER_SETTER;

        if ( ! empty( $this->filters['status'] ) ) 
        {
            foreach ( $this->filters['status'] as $filter ) 
            {
                $result .= $status_filter .  $filter . self::FILTER_SEPARATOR;
            }
        }
        
        if ( isset( $this->filters['diff-only'] ) ) 
        {
            $result .= $status_filter . 'untranslated' . self::FILTER_SEPARATOR;
        }

        if ( isset( $this->filters['username'] ) ) 
        {
            $result .= $user_filter . urlencode( $this->filters['username'] );
        }

        $result = $url . ($result == "" ? "" : self::FILTER_SEPARATOR . $result);
        
        return $result;
    }

    /**
     * Sets the major core version for the download URL. Example: 5.0.x
     * 
     * @param string $core Core version.
     *
     */
    public function set_url_core_version( $version ) 
    {
        if ( $version == 'dev' )
        {
            $this->url_version = 'dev';
        }
        else
        {
            $url_version = self::get_major_core_version( $version );
        
            if ( ! is_null( $url_version ) ) 
            {
                $this->url_version .= $url_version . '.x';
            }
        }
    }

    /**
     * Verifies the URL type (main page of a plugin/theme, apps, meta, or translate URL).
     * 
     * @param array $path_parts Path part of an URL as an array.
     * 
     * @return bool True if type is valid. Otherwise, false.
     */
    public static function is_valid_url_type( $path_parts ) 
    {
        $result = false;
        
        \WP_CLI::line( var_export( $path_parts ) );

        // Verify if it's the main page of a plugin/theme.
        if ( in_array( $path_parts[1], array( self::TYPE_URL_THEMES, self::TYPE_URL_PLUGINS ) ) ) 
        {
            $result = true;
        }
        else 
        {
            // Verify if it's a translate URL.
            if ( count( $path_parts ) >= 5 ) 
            {
                if ( in_array( $path_parts[4], self::$url_translate_types ) ) 
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
     * @return bool True if empty. Otherwise, false.
     */
    public static function is_slug_empty( $path_parts ) 
    {
        $result = false;
        $count = count( $path_parts );

        // If it's a main page of a plugin/theme.
        if ( $count >= 3 && in_array( $path_parts[1], array( self::TYPE_URL_PLUGINS, self::TYPE_URL_THEMES ) ) && empty( $path_parts[2] ) )    
        {
            $result = true;
        }
        // If it's a translate URL.
        elseif ( $count >= 6 ) 
        {
            if ( in_array( $path_parts[4], self::$url_translate_types ) && empty( $path_parts[5] ) ) 
            {
                $result = true;
            }  
        }
        
        return $result;
    }
}