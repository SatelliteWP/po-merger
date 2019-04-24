<?php

namespace satellitewp\po;

include_once(__DIR__.'/../core/merger.php');

use PHPUnit\Framework\TestCase;

/**
 * Tests the functions used by the class Merger.
 */
class Merger_Test extends TestCase 
{
    /**
     * Parameters.
     */
    private $args = null;
    private $assoc_args = null;
    /**
     * Instance of the class.
     */
    private $merger = null;

    /**
     * Path to the download folder in te root of the package
     */
    private $download_folder_path = null;
    
    /**
     * Path to the file containing fuzzy strings.
     */
    private $fuzzy_path = null;

    /**
     * Initialize the class and the parameters.
     */
    public function setUp()
    {
        $this->args = array();
        $this->assoc_args = array();
        
        $this->args[0] = 'fr-ca';
        $this->args[1] = 'fr';
        $this->args[2] = 'https://wordpress.org/plugins/wordpress-seo/';

        // Path to the files used to test the "fuzzy" parameter.
        $this->fuzzy_path = __DIR__.'/files/fuzzy.txt';
        
        $this->merger = new Merger( $this->args, $this->assoc_args );

        // Path to the download folder.
        $this->download_folder_path = $this->merger->get_download_folder_path();
    }

    /**
     * Reinitialize the variables at the end of the test.
     */
    public function tearDown()
    {
        $this->args       = null;
        $this->assoc_args = null;
        $this->fuzzy_path = null;
        $this->merger     = null;
        
        $this->download_folder_path = null;
    }

    /**
     * Tests the function that verifies if a strings contains valid parts.
     */
    public function test_get_filters() 
    {
        // Test valid filters.
        $filters_string = 'untranslated,fuzzy';
        $result = $this->merger->get_filters( $filters_string );
        
        $this->assertEquals( 2, count( $result['valid_filters'] ) );
        $this->assertEquals( 'untranslated', $result['valid_filters'][0] );
        $this->assertEquals( 'fuzzy', $result['valid_filters'][1] );
        // End of the test.

        // Test the case where one of the filters is invalid.
        $filters_string = 'untranslated,filter,fuzzy';
        $result = $this->merger->get_filters( $filters_string );
        
        $this->assertEquals( 2, count( $result['valid_filters'] ) );
        $this->assertEquals( 1, count( $result['invalid_filters'] ) );

        $this->assertEquals( 'untranslated', $result['valid_filters'][0] );
        $this->assertEquals( 'fuzzy', $result['valid_filters'][1] );
        $this->assertEquals( 'filter', $result['invalid_filters'][0] );
        // End of the test.

        // Test the case where all of the filters are invalid.
        $filters_string = 'filterone,filtertwo,filterthree';
        $result = $this->merger->get_filters( $filters_string );
        
        $this->assertEquals( 3, count( $result['invalid_filters'] ) );
        $this->assertEquals( 'filterone', $result['invalid_filters'][0] );
        $this->assertEquals( 'filtertwo', $result['invalid_filters'][1] );
        $this->assertEquals( 'filterthree', $result['invalid_filters'][2] );
        // End of the test.
    }
    
    /**
     * Tests the function  that verifies the received parameters.
     */
     public function test_verify_params() 
    {
        $params = array();
        
        // All parameters are valid.
        $params['fuzzy']     = $this->fuzzy_path;
        $params['status']    = 'untranslated,fuzzy';
        $params['username']  = 'wp_user';
        $params['test']      = true;
       
        $params['mark-copy-as-fuzzy'] = true;
        
        $result = $this->merger->verify_params( $params );
       
        // Test the case where all the parameters are valid.
        $this->assertEquals( true, $result );

        // Test with core
        $params['po-source'] = '5.0';

        // Use the 'mark-copy-as-fuzzy' alias 'mcaf'
        unset( $params['mark-copy-as-fuzzy'] );
        $params['mcaf'] = true;
        
        $result = $this->merger->verify_params( $params );
        
        $this->assertEquals( true, $result );

        // Set invalid parameters.
        $params['po-source'] = 'https://wordpress.org/wordpress-seo/';
        $params['fuzzy'] = 'wfdsn4hgf157.txt';
        $params['status'] = 'dsf432, sdftdd';
    
        $result = $this->merger->verify_params( $params );
        
        // Test the case where the parameters are invalid.
        $this->assertEquals( false, $result );

        $params['po-source'] = '33.a';

        $result = $this->merger->verify_params( $params );

        $this->assertEquals( false, $result );
    }

    /**
     * Tests the function that returns the major core version.
     */
    public function test_get_major_core_version() 
    {
        // Valid core versions.
        $valid_core_1 = '4.9';
        $valid_core_2 = '5.0.1';
        $valid_core_3 = '4.2.20';
        $valid_core_4 = '1.5.1.1';

        //Test valid core versions.
        $this->assertEquals( '4.9', $this->merger->get_major_core_version( $valid_core_1 ) );
        $this->assertEquals( '5.0', $this->merger->get_major_core_version( $valid_core_2 ) );
        $this->assertEquals( '4.2', $this->merger->get_major_core_version( $valid_core_3 ) );
        $this->assertEquals( '1.5', $this->merger->get_major_core_version( $valid_core_4 ) );

        // Invalid core versions.
        $invalid_core_1 = '4';
        $invalid_core_2 = '5."0".1';
        $invalid_core_3 = '4220';

        //Test invalid core versions.
        $this->assertEquals( null, $this->merger->get_major_core_version( $invalid_core_1 ) );
        $this->assertEquals( null, $this->merger->get_major_core_version( $invalid_core_2 ) );
        $this->assertEquals( null, $this->merger->get_major_core_version( $invalid_core_3 ) );
    }

    /**
     * Tests the function that creates the major core version for download URL. 
     */
    public function test_get_core_version_for_url() 
    {
        //Test valid core versions.
        $valid_core_1 = '4.9';
        $valid_core_2 = '5.0.1';
        $valid_core_3 = '4.2.20';
        $valid_core_4 = '1.5.1.1';

        $this->assertEquals( '4.9.x', $this->merger->create_core_for_url( $valid_core_1 ) );
        $this->assertEquals( '5.0.x', $this->merger->create_core_for_url( $valid_core_2 ) );
        $this->assertEquals( '4.2.x', $this->merger->create_core_for_url( $valid_core_3 ) );
        $this->assertEquals( '1.5.x', $this->merger->create_core_for_url( $valid_core_4 ) );
        // End of the test.

        // Test invalid core versions.
        $invalid_core_1 = '4';
        $invalid_core_2 = '5."0".1';
        $invalid_core_3 = '4220';
        $invalid_core_4 = '4.a';

        $this->assertEquals( null, $this->merger->create_core_for_url( $invalid_core_1 ) );
        $this->assertEquals( null, $this->merger->create_core_for_url( $invalid_core_2 ) );
        $this->assertEquals( null, $this->merger->create_core_for_url( $invalid_core_3 ) );
        $this->assertEquals( null, $this->merger->create_core_for_url( $invalid_core_4 ) );
        // End of the test.
    }

    
    /**
     *  Tests the function that verifies that an URL is a valid plugin/theme homepage URL.
     */
    public function test_is_valid_url() 
    {
        // Valid URL.
        $valid_url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $valid_url_2 = 'https://wordpress.org/themes/my-theme/';
        $valid_url_3 = 'https://wordpress.org/plugins/myplugin/';
        $valid_url_4 = 'https://fr-ca.wordpress.org/themes/mytheme/';

        //Test valid URL.
        $this->assertEquals( true, $this->merger->is_valid_url( $valid_url_1 ) );
        $this->assertEquals( true, $this->merger->is_valid_url( $valid_url_2 ) );
        $this->assertEquals( true, $this->merger->is_valid_url( $valid_url_4 ) );

        // Invalid URL.
        $invalid_url_1 = 'https://wordpres.org/plugins/wordpress-seo/';
        $invalid_url_2 = 'https://wordpress.com/themes/my-theme/';
        $invalid_url_3 = 'http://wordpress.org/plugins/myplugin/';
        $invalid_url_4 = 'https://fr-ca.wordpress.org/mytheme/';

        //Test invalid URL.
        $this->assertEquals( false, $this->merger->is_valid_url( $invalid_url_1 ) );
        $this->assertEquals( false, $this->merger->is_valid_url( $invalid_url_2 ) );
        $this->assertEquals( false, $this->merger->is_valid_url( $invalid_url_4 ) );

    }

    /**
     * Tests the function that gets the name of the project from the URL.
     */
    public function test_get_name_from_url() 
    {
        $url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $url_2 = 'https://en-ca.wordpress.org/themes/my-theme/';
        $url_3 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/woocommerce-jetpack/';
        $url_4 = 'https://translate.wordpress.org/locale/fr-ca/default/meta/wordcamp/';
        $url_5 = 'https://translate.wordpress.org/locale/fr-ca/default/apps/ios/';

        $name_1 = $this->merger->get_name_from_url( $url_1 );
        $name_2 = $this->merger->get_name_from_url( $url_2 );
        
        // Test with translate domain.
        $this->merger->is_translate_host( 'translate.wordpress.org' );
        
        $name_3 = $this->merger->get_name_from_url( $url_3 );
        $name_4 = $this->merger->get_name_from_url( $url_4 );
        $name_5 = $this->merger->get_name_from_url( $url_5 );

        $this->assertEquals( 'my-plugin', $name_1 );
        $this->assertEquals( 'my-theme', $name_2 );
        $this->assertEquals( 'woocommerce-jetpack', $name_3 );
        $this->assertEquals( 'wordcamp', $name_4 );
        $this->assertEquals( 'ios', $name_5 );
    }

    /**
     * Tests the function that gets the type from the URL.
     */
    public function test_get_type_from_url() 
    {
        $url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $url_2 = 'https://en-ca.wordpress.org/themes/my-theme/';
        $url_3 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/woocommerce-jetpack/';
        $url_4 = 'https://translate.wordpress.org/locale/fr-ca/default/meta/wordcamp/';
        $url_5 = 'https://translate.wordpress.org/locale/fr-ca/default/apps/ios/';

        $name_1 = $this->merger->get_type_from_url( $url_1 );
        $name_2 = $this->merger->get_type_from_url( $url_2 );
        
        // Test with translate domain.
        $this->merger->is_translate_host( 'translate.wordpress.org' );
        
        $name_3 = $this->merger->get_type_from_url( $url_3 );
        $name_4 = $this->merger->get_type_from_url( $url_4 );
        $name_5 = $this->merger->get_type_from_url( $url_5 );

        $this->assertEquals( 'wp-plugins', $name_1 );
        $this->assertEquals( 'wp-themes', $name_2 );
        $this->assertEquals( 'wp-plugins', $name_3 );
        $this->assertEquals( 'meta', $name_4 );
        $this->assertEquals( 'apps', $name_5 );
    }

    
    /**
     *  Tests the functon that sets the temporary saving path for the downloaded *.po file.
     */
    public function test_set_temp_path() 
    {
        // Path parameters
        $params = array(
            'locale' => 'fr-ca',
            'download_folder_path' => getcwd() . '/',
            'core' => null
        );

        // Set parameters for a plugin
        $params['name'] = 'my-plugin';
        $params['type'] = 'plugins';
        $expected = getcwd() . '/wp-plugins-my-plugin-fr-ca-temp.po';
        
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params ) );

        // Set parameters for a theme
        $params['name'] = 'my-theme';
        $params['type'] = 'themes';
        $expected = getcwd() . '/wp-themes-my-theme-fr-ca-temp.po';
        
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params ) );

        // Set parameters for a core
        $params['core'] = '5.0.x';
        $expected = getcwd() . '/wp-5.0.x-fr-ca-temp.po';
        
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params ) );
    }

    /**
     * 
     */
    public function test_set_filters() 
    {
        $url = 'https://translate.wordpress.org/projects/wp-plugins/my-plugin/stable/fr-ca/default/export-translations';
        
        // Set status filters
        $filters = array(
            'status' => array( 'untranslated', 'fuzzy' ),
            'diff-only' => null,
            'username' => null
        );
        
        $expected = 'https://translate.wordpress.org/projects/wp-plugins/my-plugin/stable/fr-ca/default/export-translations?filters%5Bstatus%5D=untranslated&filters%5Bstatus%5D=fuzzy&';

        $this->assertEquals( $expected, $this->merger->set_filters( $url, $filters ) );

        // Set the "diff-only" filter.
        $filters = array(
            'status' => array(),
            'diff-only' => true,
            'username' => null
        );

        $expected = 'https://translate.wordpress.org/projects/wp-plugins/my-plugin/stable/fr-ca/default/export-translations?filters%5Bstatus%5D=untranslated&';

        $this->assertEquals( $expected, $this->merger->set_filters( $url, $filters) );

        // Set the "username" filter.
        $filters = array(
            'status' => array(),
            'diff-only' => null,
            'username' => 'wp-user'
        );

        $expected = 'https://translate.wordpress.org/projects/wp-plugins/my-plugin/stable/fr-ca/default/export-translations?filters%5Buser_login%5D=wp-user';

        $this->assertEquals( $expected, $this->merger->set_filters( $url, $filters ) );

        // Set all tree types of filters.
        $filters = array(
            'status' => array('fuzzy'),
            'diff-only' => true,
            'username' => 'wp-user'
        );

        $expected = 'https://translate.wordpress.org/projects/wp-plugins/my-plugin/stable/fr-ca/default/export-translations?filters%5Bstatus%5D=fuzzy&filters%5Bstatus%5D=untranslated&filters%5Buser_login%5D=wp-user';

        $this->assertEquals( $expected, $this->merger->set_filters( $url, $filters) );
    }
    
    /**
     * Tests the function that creates the download URL of a *.po file.
     */
    public function test_create_po_url() 
    {
        // Set set the parameters for the plugin/theme download URL.
        $params = array(
            'name' => 'my-theme',
            'type' => 'themes',
            'locale' => 'fr',
            'core' => null,
            'plugin_release' => 'stable',
            'download_folder_path' => getcwd() . '/',
            'filters' => array(
                'status' => array( 'fuzzy' ),
                'diff-only' => true,
                'username' => 'wp-user'
            )
        );

        // Set as a copy locale.
        $params['is_base'] = false;

        $expected = 'https://translate.wordpress.org/projects/wp-themes/my-theme/stable/fr/default/export-translations';

        $this->assertEquals( $expected, $this->merger->create_po_url( $params ) );

        // Set as a base locale.
        $params['is_base'] = true;
        
        $expected = 'https://translate.wordpress.org/projects/wp-themes/my-theme/stable/fr/default/export-translations?filters%5Bstatus%5D=fuzzy&filters%5Bstatus%5D=untranslated&filters%5Buser_login%5D=wp-user';

        $this->assertEquals( $expected, $this->merger->create_po_url( $params ) );

        // Empty the filters
        $filters = array(
            'status' => array(),
            'diff-only' => null,
            'username' => null
        );

        // Set a core 
        $params['core'] = '5.0.x';
        $params['filters'] = $filters;

        $expected = 'https://translate.wordpress.org/projects/wp/5.0.x/fr/default/export-translations';

        $this->assertEquals( $expected, $this->merger->create_po_url( $params ) );
    }

    
    /**
     * Tests the function that downloads a single *.po file.
     */
    public function test_download_single_po() 
    {
        // Test valid *.po file parameters.
        $params = array(
            'save_path'    => $this->download_folder_path . 'wp-plugins-classic-editor-fr-ca-temp.po',
            'download_url' => 'https://translate.wordpress.org/projects/wp-plugins/classic-editor/stable/fr-ca/default/export-translations/',
        );

        $expected = $this->download_folder_path . 'wp-plugins-classic-editor-fr-ca-temp.po';

        $this->assertEquals( $expected, $this->merger->download_single_po( $params ) );
        // End of the test.

        if ( is_file( $expected ) ) 
        {
            unlink( $expected );
        }

        // Test the case where the stable release doesn't exist (at the moment of test creation), but the dev release does exist.
        $params = array(
            'name'      => 'disable-comments',
            'type'      => 'wp-plugins',
            'locale'    => 'fr-ca',
            'is_base'   => true,
            'save_path' => $this->download_folder_path . 'wp-plugins-disable-comments-fr-ca-temp.po',
            'core'      => null,
            'release'   => 'stable',
            'filters'   => array(),
            'download_url' => 'https://translate.wordpress.org/projects/wp-plugins/disable-comments/stable/fr-ca/default/export-translations/',
        );

        $expected = $this->download_folder_path . 'wp-plugins-disable-comments-fr-ca-temp.po';

        $this->assertEquals( $expected, $this->merger->download_single_po( $params ) );
        // End of the test.

        if ( is_file( $expected ) ) 
        {
            unlink( $expected );
        }

        // Test invalid parameters.
        $params = null;
        
        $params = array(
            'type'         => 'wp-themes',
            'save_path'    => 'wp-themes-a-theme-fr-ca-temp.po',
            'download_url' => 'http://press.org/projects/wp-themes/a-theme/fr-ca/default/export-translations/',
        );

        $this->assertEquals( null, $this->merger->download_single_po( $params ) );
    }
    
    
    /**
     * Tests the function that downloads and saves a *.po file.
     */
    public function test_download_po() 
    {
        // Test a non core *.po file
        $expected = $this->download_folder_path . 'wp-plugins-classic-editor-fr-ca-temp.po';
        
        $this->assertEquals( $expected, $this->merger->download_po( 'classic-editor', 'plugins', 'fr-ca', null, true ) );
        $this->assertEquals( true, is_file( $expected ) );

        if ( is_file( $expected ) ) 
        {
            unlink( $expected );
        }

        // Test a core *.po file
        $expected = $this->download_folder_path . 'wp-4.5.x-fr-ca-temp.po';
        
        $this->assertEquals( $expected, $this->merger->download_po( null, null, 'fr-ca', '4.5.x', true ) );
        $this->assertEquals( true, is_file( $expected ) );

        if ( is_file( $expected ) ) 
        {
            unlink( $expected );
        }
    }

    /**
     * Tests the function that downloads and saves the base locale, the copy locale *po files.
     */
    public function test_download_pos() 
    {
        // Get the path to the download folder
        $this->download_folder_path = $this->merger->get_download_folder_path();
        
        $url_plugin = 'https://en-ca.wordpress.org/plugins/classic-editor/';

        // Test the downloaded files
        $expected_base = $this->download_folder_path . 'wp-plugins-classic-editor-fr-ca-temp.po';
        $expected_copy = $this->download_folder_path . 'wp-plugins-classic-editor-fr-temp.po';
        $expected_core_base = $this->download_folder_path . 'wp-4.1.x-hr-temp.po';
        $expected_core_copy = $this->download_folder_path . 'wp-4.1.x-sr-temp.po';

        $path_base = $this->merger->download_pos( $url_plugin, 'fr-ca', 'fr', null, true )['path_base'];
        $path_copy = $this->merger->download_pos( $url_plugin, 'fr-ca', 'fr', null, false )['path_copy'];
        $path_core_base = $this->merger->download_pos( null, 'hr', 'sr', '4.1.x', true )['path_base'];
        $path_core_copy = $this->merger->download_pos( null, 'hr', 'sr', '4.1.x', false )['path_copy'];

        $this->assertEquals( $expected_base, $path_base );
        $this->assertEquals( $expected_copy, $path_copy );
        $this->assertEquals( $expected_core_base, $path_core_base );
        $this->assertEquals( $expected_core_copy, $path_core_copy );

        $this->assertEquals( true, is_file( $expected_base ) );
        $this->assertEquals( true, is_file( $expected_copy ) );
        $this->assertEquals( true, is_file( $expected_core_base ) );
        $this->assertEquals( true, is_file( $expected_core_copy ) );

        $files = array( $expected_base, $expected_copy, $expected_core_base, $expected_core_copy );

        foreach ( $files as $file ) 
        {
            if ( is_file( $file ) ) 
            {
                unlink( $file );
            }
        }
    }
    
    /**
     * Test the function that downloads the base locale, the copy locale *.po files and attempts to read their contents.
     */
    public function test_process_pos() 
    {
        // Test valid parameters.
        $url_ok      = 'https://wordpress.org/plugins/jetpack/';
        $base_locale = 'fr-ca';
        $copy_locale = 'fr';

        $result = $this->merger->process_pos( $url_ok, $base_locale, $copy_locale, null );

        $this->assertEquals( true, $result );

        // Test core.
        $core   = '4.7.x';
        $result = $this->merger->process_pos( null, $base_locale, $copy_locale, $core );

        $this->assertEquals( true, $result );
        // End of the test.

        // Test an invalid URL (the project doesn't exist).
        $invalid_url = 'https://wordpress.com/plugins/some-non-existing-plugin/';
        $result      = $this->merger->process_pos( $invalid_url, $base_locale, $copy_locale, null );

        $this->assertEquals( false, $result );
        // End of the test.
        
        // Test an invalid core.
        $invalid_core = '4.a';
        $result       = $this->merger->process_pos( null, $base_locale, $copy_locale, $invalid_core );

        $this->assertEquals( false, $result );
        // End of the test.
        
        // Test an invalid locale. 
        $invalid_copy_locale = 'frrr';
        
        $result = $this->merger->process_pos( $url_ok, $base_locale, $invalid_copy_locale , null );

        $this->assertEquals( false, $result );
        // End of the test.
        
        // Test the case where both of the locales are invalid.
        $invalid_base_locale = 'frca';

        $result = $this->merger->process_pos( $url_ok, $invalid_base_locale, $invalid_copy_locale, null );

        $this->assertEquals( false, $result );
        // End of the test.

        // Test the case where all of the parameters are invalid (URL is set).
        $result = $this->merger->process_pos( $invalid_url, $invalid_base_locale, $invalid_copy_locale, null );

        $this->assertEquals( false, $result );
        // End of the test.

        // Test the case where all of the parameters are invalid (core is set).
        $result = $this->merger->process_pos( null, $invalid_base_locale, $invalid_copy_locale, $invalid_core );

        $this->assertEquals( false, $result );
        // End of the test.

    }

    /**
     * Tests the function that verifies if the parameters are valid, then
     * attempts to download the *.po files and extract information from them,
     * thus verifying that the download urls are also valid.
     */
    public function test_has_valid_parameters() 
    {
        // Test with the valid parameters set while initializing the object.
        $this->assertEquals( true, $this->merger->has_valid_parameters());

        // Test with a core.
        $this->args[2] = '5.0';
        $this->merger = new Merger( $this->args, $this->assoc_args );

        $this->assertEquals( true, $this->merger->has_valid_parameters());

        // Set invalid parameters.
        $this->args[0] = 'frca';
        $this->merger = new Merger( $this->args, $this->assoc_args );

        $this->assertEquals( false, $this->merger->has_valid_parameters() );

        $this->assoc_args['mcaff'] = true;
        $this->merger = new Merger( $this->args, $this->assoc_args );

        $this->assertEquals( false, $this->merger->has_valid_parameters() );
    }

    /**
     * Tests the function that sets the name of the result file.
     */
    public function test_set_result_name() 
    {
        $name = 'awesome-theme';
        $type = 'themes';
        $locale = 'fr-ca';
        
        $expected = 'wp-themes-awesome-theme-fr-ca-merged.po';
        $result =  $this->merger->set_result_name( $name, $type, $locale, null );

        $this->assertEquals( $expected, $result );

        $name = 'super-plugin';
        $type = 'plugins';
        $locale = 'en-ca';
        
        $expected = 'wp-plugins-super-plugin-en-ca-merged.po';
        $result =  $this->merger->set_result_name( $name, $type, $locale, null );

        $this->assertEquals( $expected, $result );

        $expected = 'wp-4.9.x-en-ca-merged.po';
        $result = $this->merger->set_result_name( null, null, $locale, '4.9.x' );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Tests the function that creates the result file.
     */
    public function test_create_result_file() 
    {
        $content = array( 'msgid "id"', "\n", 'msgstr "translation"' );
        $result_name = 'result.po';

        $this->merger->create_result_file( $content, $result_name );

        $this->assertEquals( true, is_file( $result_name ) );
        $this->assertEquals( 'msgid "id"' . "\n", file( $result_name )[0] );
        $this->assertEquals( 'msgstr "translation"', file( $result_name )[1] );

        if ( is_file( $result_name ) ) 
        {
            unlink( $result_name );
        }
    }
}