<?php

namespace satellitewp\po;

include_once( 'functions.php' );
include_once( __DIR__.'/../core/merger.php' );

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
     * Path to the file containing "fuzzy" strings.
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
     * Tests the function that verifies if the string is an URL.
     */
    public function test_is_url() 
    {
        // Test valid strings.
        $valid_string_1 = 'https://wordpress.org/plugins/wordpress-seo/';
        $valid_string_2 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/wordpress-seo/';
        
        $this->assertTrue( $this->merger->is_url( $valid_string_1 ) );
        $this->assertTrue( $this->merger->is_url( $valid_string_2 ) );
        // End of the test.

        // Test invalid strings.
        $invalid_string_1 = 'wordpress.org/plugins/wordpress-seo/';
        $invalid_string_2 = 'http://wordpress.org';
        
        $this->assertFalse( $this->merger->is_url( $invalid_string_1 ) );
        $this->assertFalse( $this->merger->is_url( $invalid_string_2 ) );
        // End of the test.
    }
    
    /**
     * Tests the function that verifies if the string ends with a certain substring. 
     */
    public function test_ends_with() 
    {
        // Test valid case.
        $haystack = 'stringtarget-part';
        $needle   = 'target-part';

        $this->assertTrue( $this->merger->ends_with( $haystack, $needle ) );
        // End of the test.

        // Test invald valid case.
        $haystack = 'stringtarget-part';
        $needle   = 'target';

        $this->assertFalse( $this->merger->ends_with( $haystack, $needle ) );
        // End of the test.
    }
    
    /**
     * Test the function that verifies the type of the URL.
     */
    public function test_is_valid_url_type() 
    {
        // Test valid types.
        $valid_url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $url_parts = parse_url( $valid_url_1 );
        $path_parts_1 = explode( '/', $url_parts['path'] );

        $valid_url_2 = 'https://wordpress.org/themes/my-theme/';
        $url_parts = parse_url( $valid_url_2 );
        $path_parts_2 = explode( '/', $url_parts['path'] );
        
        $valid_url_3 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-themes/nice-theme/';
        $url_parts = parse_url( $valid_url_3 );
        $path_parts_3 = explode( '/', $url_parts['path'] );
        
        $valid_url_4 = 'https://translate.wordpress.org/locale/fr/default/wp-plugins/nice-plugin/';
        $url_parts = parse_url( $valid_url_4 );
        $path_parts_4 = explode( '/', $url_parts['path'] );

        $valid_url_5 = 'https://translate.wordpress.org/locale/fr-ca/default/apps/app/';
        $url_parts = parse_url( $valid_url_5 );
        $path_parts_5 = explode( '/', $url_parts['path'] );
        
        $valid_url_6 = 'https://translate.wordpress.org/locale/fr/default/meta/meta-project/';
        $url_parts = parse_url( $valid_url_6 );
        $path_parts_6 = explode( '/', $url_parts['path'] );


        $this->assertTrue( $this->merger->is_valid_url_type( $path_parts_1 ) );
        $this->assertTrue( $this->merger->is_valid_url_type( $path_parts_2 ) );
        $this->assertTrue( $this->merger->is_valid_url_type( $path_parts_3 ) );
        $this->assertTrue( $this->merger->is_valid_url_type( $path_parts_4 ) );
        $this->assertTrue( $this->merger->is_valid_url_type( $path_parts_5 ) );
        $this->assertTrue( $this->merger->is_valid_url_type( $path_parts_6 ) );
        // End of the test.

        // Test invalid types.
        $invalid_url_1 = 'https://wordpress.org/cats/my-cats/';
        $url_parts = parse_url( $invalid_url_1 );
        $path_parts_1 = explode( '/', $url_parts['path'] );
        
        $invalid_url_2 = 'https://wordpress.org/theme/my-theme/';
        $url_parts = parse_url( $invalid_url_2 );
        $path_parts_2 = explode( '/', $url_parts['path'] );
        
        $invalid_url_3 = 'https://translate.wordpress.org/locale/fr-ca/default/wp/nice-theme/';
        $url_parts = parse_url( $invalid_url_3 );
        $path_parts_3 = explode( '/', $url_parts['path'] );
        
        $invalid_url_4 = 'https://translate.wordpress.org/locale/fr/default/plugins/nice-plugin/';
        $url_parts = parse_url( $invalid_url_4 );
        $path_parts_4 = explode( '/', $url_parts['path'] );
        
        $invalid_url_5 = 'https://translate.wordpress.org/locale/fr-ca/default/app/an-app/';
        $url_parts = parse_url( $invalid_url_5 );
        $path_parts_5 = explode( '/', $url_parts['path'] );
        
        $invalid_url_6 = 'https://translate.wordpress.org/locale/fr/default/metas/meta-projects/';
        $url_parts = parse_url( $invalid_url_6 );
        $path_parts_6 = explode( '/', $url_parts['path'] );
        
        $this->assertFalse( $this->merger->is_valid_url_type( $path_parts_1 ) );
        $this->assertFalse( $this->merger->is_valid_url_type( $path_parts_2 ) );
        $this->assertFalse( $this->merger->is_valid_url_type( $path_parts_3 ) );
        $this->assertFalse( $this->merger->is_valid_url_type( $path_parts_4 ) );
        $this->assertFalse( $this->merger->is_valid_url_type( $path_parts_5 ) );
        $this->assertFalse( $this->merger->is_valid_url_type( $path_parts_6 ) );
        // End of the test.
    }
    
    
    /**
     * Tests the function that verifies if the slug is empty.
     */
    public function test_is_empty_slug() 
    {
        // Test the cases where the slug isn't empty.
        $valid_url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $url_parts = parse_url( $valid_url_1 );
        $path_parts_1 = explode( '/', $url_parts['path'] );
        
        $valid_url_2 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-themes/nice-theme/';
        $url_parts = parse_url( $valid_url_2 );
        $path_parts_2 = explode( '/', $url_parts['path'] );

        $this->assertFalse( $this->merger->is_empty_slug( $path_parts_1 ) );
        $this->assertFalse( $this->merger->is_empty_slug( $path_parts_2 ) );
        // End of the test.

        // Test the cases where the slug is empty.
        $invalid_url_1 = 'https://wordpress.org/plugins//';
        $url_parts = parse_url( $invalid_url_1 );
        $path_parts_1 = explode( '/', $url_parts['path'] );
        
        $invalid_url_2 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-themes//';
        $url_parts = parse_url( $invalid_url_2 );
        $path_parts_2 = explode( '/', $url_parts['path'] );

        $this->assertTrue( $this->merger->is_empty_slug( $path_parts_1 ) );
        $this->assertTrue( $this->merger->is_empty_slug( $path_parts_2 ) );
        // End of the test.
    }
    
    
    
    /**
     * Tests the function that verifies if the URL is a valid URL.
     */
    public function test_is_valid_url() 
    {
        // Test valid URL.
        $valid_url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $valid_url_2 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/wordpress-seo/';
        $valid_url_3 = 'https://wordpress.org/plugins/myplugin/';
        $valid_url_4 = 'https://fr-ca.wordpress.org/themes/mytheme/';
        $valid_url_5 = 'https://translate.wordpress.org/locale/fr/default/apps/android/';
        $valid_url_6 = 'https://translate.wordpress.org/locale/en-ca/default/meta/some-meta/';

        $this->assertTrue( $this->merger->is_valid_url( $valid_url_1 ) );
        $this->assertTrue( $this->merger->is_valid_url( $valid_url_2 ) );
        $this->assertTrue( $this->merger->is_valid_url( $valid_url_4 ) );
        $this->assertTrue( $this->merger->is_valid_url( $valid_url_5 ) );
        $this->assertTrue( $this->merger->is_valid_url( $valid_url_6 ) );

        // Test invalid URL.
        $invalid_url_1 = 'https://wordpres.org/plugins/wordpress-seo/';
        $invalid_url_2 = 'https://wordpress.com/themes/my-theme/';
        $invalid_url_3 = 'https://translate.wordpress.org/locale/fr-ca/default/wpplugins/wordpress-seo/';
        $invalid_url_4 = 'https://fr-ca.wordpress.org/';
        $invalid_url_5 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-plugins//';
        $invalid_url_6 = 'https://translate.wordpress.org/locale/en-ca/default/app/some-app/';

        $this->assertFalse( $this->merger->is_valid_url( $invalid_url_1 ) );
        $this->assertFalse( $this->merger->is_valid_url( $invalid_url_2 ) );
        $this->assertFalse( $this->merger->is_valid_url( $invalid_url_4 ) );
        $this->assertFalse( $this->merger->is_valid_url( $invalid_url_5 ) );
        $this->assertFalse( $this->merger->is_valid_url( $invalid_url_6 ) );
        // End of the test.
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
     * Tests the function that verifies each received parameter.
     */
     public function test_is_each_parameter_valid() 
    {
        $params = array();
        
        $valid_url        = 'https://wordpress.org/plugins/wordpress-seo/';
        $valid_core       = '4.7';
        $valid_fuzzy_file = $this->fuzzy_path;
        $valid_filters    = 'untranslated,fuzzy'; 

        $invalid_url        = 'https://wordpress.org/wordpress-seo/';
        $invalid_core       = '33.a';
        $invalid_fuzzy_file = 'wfdsn4hgf157.txt';
        $invalid_filters    = 'dsf432,sdftdd'; 
        
        // Test the case where all the parameters are valid.
        $params['fuzzy']     = $valid_fuzzy_file;
        $params['status']    = $valid_filters;
        $params['test']      = true;
        $params['mark-copy-as-fuzzy'] = true;
        
        $this->assertTrue( $this->merger->is_each_parameter_valid( $params ) );

        // Use the 'mark-copy-as-fuzzy' alias 'mcaf'
        unset( $params['mark-copy-as-fuzzy'] );
        $params['mcaf'] = true;
        $this->assertTrue( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.
        
        // Test with core.
        $params['po-source'] = '5.0';
        $result = $this->merger->is_each_parameter_valid( $params );
        $this->assertTrue( $result );
        // End of the test.

        // Test with an invalid URL.
        $params['po-source'] = $invalid_url; 
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.
        $params['po-source'] = $valid_url;

        // Test with an invalid fuzzy file.
        $params['fuzzy'] = $invalid_fuzzy_file;
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.
        $params['fuzzy'] = $valid_fuzzy_file;
        
        // Test with invalid filters.
        $params['status'] = $invalid_filters;
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.
        $params['status'] = $valid_filters;

        // Test with an unknown parameter.
        $params['unknown'] = true;
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.

        unset( $params['unknown'] );
        
        // Test with an invalid core.
        $params['po-source'] = $invalid_core;
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.

        $params['po-source'] = $valid_core;
        
        // Test the case where all of the parameters are invalid (URL as the *.po source).
        $params['po_source'] = $invalid_url;
        $params['fuzzy']     = $invalid_fuzzy_file;
        $params['status']    = $invalid_filters;
        $params['unknown']   = true;
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.

        // Test the case where all of the parameters are invalid (core as the *.po source).
        $params['po_source'] = $invalid_core;
        $this->assertFalse( $this->merger->is_each_parameter_valid( $params ) );
        // End of the test.

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
        $valid_core_5 = '10.500.199';

        //Test valid core versions.
        $this->assertEquals( '4.9', $this->merger->get_major_core_version( $valid_core_1 ) );
        $this->assertEquals( '5.0', $this->merger->get_major_core_version( $valid_core_2 ) );
        $this->assertEquals( '4.2', $this->merger->get_major_core_version( $valid_core_3 ) );
        $this->assertEquals( '1.5', $this->merger->get_major_core_version( $valid_core_4 ) );
        $this->assertEquals( '10.500', $this->merger->get_major_core_version( $valid_core_5 ) );

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
    public function test_create_core_version_for_url() 
    {
        //Test valid core versions.
        $valid_core_1 = '4.9';
        $valid_core_2 = '5.0.1';
        $valid_core_3 = '4.2.20';
        $valid_core_4 = '1.5.1.1';
        $valid_core_5 = '10.500.199';

        $this->assertEquals( '4.9.x', $this->merger->create_core_version_for_url( $valid_core_1 ) );
        $this->assertEquals( '5.0.x', $this->merger->create_core_version_for_url( $valid_core_2 ) );
        $this->assertEquals( '4.2.x', $this->merger->create_core_version_for_url( $valid_core_3 ) );
        $this->assertEquals( '1.5.x', $this->merger->create_core_version_for_url( $valid_core_4 ) );
        $this->assertEquals( '10.500.x', $this->merger->create_core_version_for_url( $valid_core_5 ) );
        // End of the test.

        // Test invalid core versions.
        $invalid_core_1 = '4';
        $invalid_core_2 = '5."0".1';
        $invalid_core_3 = '4220';
        $invalid_core_4 = '4.a';

        $this->assertEquals( null, $this->merger->create_core_version_for_url( $invalid_core_1 ) );
        $this->assertEquals( null, $this->merger->create_core_version_for_url( $invalid_core_2 ) );
        $this->assertEquals( null, $this->merger->create_core_version_for_url( $invalid_core_3 ) );
        $this->assertEquals( null, $this->merger->create_core_version_for_url( $invalid_core_4 ) );
        // End of the test.
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
        // Path parameters.
        $params = array(
            'locale' => 'fr-ca',
            'download_folder_path' => getcwd() . '/',
            'core' => null
        );

        // Test with plugin.
        $params['name'] = 'my-plugin';
        $params['type'] = 'wp-plugins';
        $expected = getcwd() . '/wp-plugins-my-plugin-fr-ca-temp.po';
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params )['single_po'] );
        // End of the test.

        // Test with theme.
        $params['name'] = 'my-theme';
        $params['type'] = 'wp-themes';
        $expected = getcwd() . '/wp-themes-my-theme-fr-ca-temp.po';
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params )['single_po'] );
        // End of the test.
        
        // Test with app.
        $params['name'] = 'ios';
        $params['type'] = 'apps';
        $expected = getcwd() . '/wp-apps-ios-fr-ca-temp.po';
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params )['single_po'] );
        // End of the test.

        // Test with meta.
        $params['name'] = 'meta-project';
        $params['type'] = 'meta';
        $expected = getcwd() . '/wp-meta-meta-project-fr-ca-temp.po';
        $this->assertEquals( $expected, $this->merger->set_temp_path( $params )['single_po'] );
        // End of the test.

        // Test with core.
        $params['core'] = '5.0.x';
        $expected_1 = getcwd() . '/wp-5.0.x-fr-ca-temp.po';
        $expected_2 = getcwd() . '/wp-5.0.x-cc-fr-ca-temp.po';
        $expected_3 = getcwd() . '/wp-5.0.x-admin-fr-ca-temp.po';
        $expected_4 = getcwd() . '/wp-5.0.x-admin-network-fr-ca-temp.po';
        $this->assertEquals( $expected_1, $this->merger->set_temp_path( $params )['core'][0] );
        $this->assertEquals( $expected_2, $this->merger->set_temp_path( $params )['core'][1] );
        $this->assertEquals( $expected_3, $this->merger->set_temp_path( $params )['core'][2] );
        $this->assertEquals( $expected_4, $this->merger->set_temp_path( $params )['core'][3] );
        // End of the test.
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
        // Set set the parameters.
        $params = array(
            'name' => 'my-project',
            'type' => 'wp-themes',
            'locale' => 'fr',
            'core' => null,
            'release' => 'stable',
            'download_folder_path' => getcwd() . '/',
            'filters' => array(
                'status' => array( 'fuzzy' ),
                'diff-only' => true,
                'username' => 'wp-user'
            )
        );

        // Test with "copy-locale" (no filters should be applied).
        $params['is_base'] = false;
        $expected = 'https://translate.wordpress.org/projects/wp-themes/my-project/fr/default/export-translations';
        $this->assertEquals( $expected, $this->merger->create_po_url( $params )['single_po'] );
        // End of the test.

        // Test with "base-locale" (filters should be applied).
        $params['is_base'] = true;
        $expected = 'https://translate.wordpress.org/projects/wp-themes/my-project/fr/default/export-translations?filters%5Bstatus%5D=fuzzy&filters%5Bstatus%5D=untranslated&filters%5Buser_login%5D=wp-user';
        $this->assertEquals( $expected, $this->merger->create_po_url( $params )['single_po'] );
        // End of the test.

        // Empty the filters
        $filters = array(
            'status' => array(),
            'diff-only' => null,
            'username' => null
        );
        $params['filters'] = $filters;

        // Test with type plugins.
        $params['type'] = 'wp-plugins';
        $expected = 'https://translate.wordpress.org/projects/wp-plugins/my-project/stable/fr/default/export-translations';
        $this->assertEquals( $expected, $this->merger->create_po_url( $params )['single_po'] );
        // End of the test.

        // Test with type apps.
        $params['type'] = 'apps';
        $expected = 'https://translate.wordpress.org/projects/apps/my-project/dev/fr/default/export-translations';
        $this->assertEquals( $expected, $this->merger->create_po_url( $params )['single_po'] );
        // End of the test.

        // Test with type meta.
        $params['type'] = 'meta';
        $expected = 'https://translate.wordpress.org/projects/meta/my-project/fr/default/export-translations';
        $this->assertEquals( $expected, $this->merger->create_po_url( $params )['single_po'] );
        // End of the test.
        
        // Test with core. 
        $params['core'] = '5.0.x';
        $expected_1 = 'https://translate.wordpress.org/projects/wp/5.0.x/fr/default/export-translations';
        $expected_2 = 'https://translate.wordpress.org/projects/wp/5.0.x/cc/fr/default/export-translations';
        $expected_3 = 'https://translate.wordpress.org/projects/wp/5.0.x/admin/fr/default/export-translations';
        $expected_4 = 'https://translate.wordpress.org/projects/wp/5.0.x/admin/network/fr/default/export-translations';
        $this->assertEquals( $expected_1, $this->merger->create_po_url( $params )['core'][0] );
        $this->assertEquals( $expected_2, $this->merger->create_po_url( $params )['core'][1] );
        $this->assertEquals( $expected_3, $this->merger->create_po_url( $params )['core'][2] );
        $this->assertEquals( $expected_4, $this->merger->create_po_url( $params )['core'][3] );
        // End of the test.   
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
    public function test_download_pos() 
    {
        $params = array(
            'name'        => 'classic-editor',
            'type'        => 'wp-plugins',
            'locale'      => 'fr-ca',
            'core'        => null,
            'release'     => 'stable',
            'is_base'     => true,
            'filters'     => array(),
            'download_folder_path' => $this->download_folder_path
        );

        // Test with valid parameters.
        $expected = $this->download_folder_path . 'wp-plugins-classic-editor-fr-ca-temp.po';
        $this->assertEquals( $expected, $this->merger->download_pos( $params )['single_po'] );
        // End of the test.

        if ( is_file( $expected ) ) 
        {
            unlink( $expected );
        }

        // Test the case where the stable release doesn't exist (at the moment of the test's creation), but the dev release does exist.
        $params['name'] = 'disable-comments';
        $expected = $this->download_folder_path . 'wp-plugins-disable-comments-fr-ca-temp.po';
        $this->assertEquals( $expected, $this->merger->download_pos( $params )['single_po'] );
        // End of the test.

        if ( is_file( $expected ) ) 
        {
            unlink( $expected );
        }
        
        // Test with core.
        $params['core'] = '5.0.x';
        $expected_1 = $this->download_folder_path . 'wp-5.0.x-fr-ca-temp.po';
        $expected_2 = $this->download_folder_path . 'wp-5.0.x-cc-fr-ca-temp.po';
        $expected_3 = $this->download_folder_path . 'wp-5.0.x-admin-fr-ca-temp.po';;
        $expected_4 = $this->download_folder_path . 'wp-5.0.x-admin-network-fr-ca-temp.po';
        
        $result = $this->merger->download_pos( $params )['core'];
        
        $this->assertEquals( $expected_1, $result[0] );
        $this->assertEquals( $expected_2, $result[1] );
        $this->assertEquals( $expected_3, $result[2] );
        $this->assertEquals( $expected_4, $result[3] );
        // End of the test.
        
        foreach ( $result as $path ) 
        {
            if ( is_file( $path ) ) 
            {
                unlink( $path );
            }
        }
        
        // Test with invalid parameters.
        $params['type'] = 'wp-plugin';
        $params['core'] = null;
        $this->assertEquals( null, $this->merger->download_pos( $params )['single_po'] );
        // End of the test.
    }

    
    /**
     * Test the function that attempts to read the contetns of the downloaded *.po files.
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
    }

    /**
     * Tests the function that verifies if the parameters are valid.
     */
    public function test_has_valid_parameters() 
    {
        // Test with the valid parameters set while initializing the object.
        $this->assertTrue( $this->merger->has_valid_parameters() );
        // End of the test.

        // Test with a core.
        $this->args[2] = '5.0';
        $this->merger = new Merger( $this->args, $this->assoc_args );
        $this->assertTrue( true, $this->merger->has_valid_parameters());
        // End of the test.
        
        $this->assoc_args['mcaff'] = true;
        $this->merger = new Merger( $this->args, $this->assoc_args );

        $this->assertEquals( false, $this->merger->has_valid_parameters() );
    }

    /**
     * Tests the function that creates the result file.
     */
    public function test_create_result_file() 
    {
        $content = array( 'msgid "id"', "\n", 'msgstr "translation"' );
        $result_name = 'result.po';

        $this->merger->create_result_file( $content, $result_name );

        // Test the file creation.
        $this->assertTrue( is_file( $result_name ) );
        
        // Test the content.
        $this->assertEquals( 'msgid "id"' . "\n", file( $result_name )[0] );
        $this->assertEquals( 'msgstr "translation"', file( $result_name )[1] );

        if ( is_file( $result_name ) ) 
        {
            unlink( $result_name );
        }
    }
}