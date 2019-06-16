<?php

namespace satellitewp\po;

include_once( 'functions.php' );
include_once( __DIR__.'/../core/pos_info.php' );

use PHPUnit\Framework\TestCase;

/**
 * Tests the functions used by the class Pos_Info
 */
class Pos_Info_Test extends TestCase 
{
    /**
     * Initialize the tests
     */
    public function setUp()
    {
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
    }
    
    /**
     * case 100
     *
    public function test_case_100() 
    {
        $pi = new Pos_Info( 'fr-ca', 'fr' );
        $pi->set_url( 'https://wordpress.org/plugins/wordpress-seo/' );

        $vars = $pi->get_internal();

        
    }*/

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


        $this->assertTrue( Pos_Info::is_valid_url_type( $path_parts_1 ) );
        $this->assertTrue( Pos_Info::is_valid_url_type( $path_parts_2 ) );
        
        # Broken tests. See issue #12
        $this->assertTrue( Pos_Info::is_valid_url_type( $path_parts_3 ) );
        $this->assertTrue( Pos_Info::is_valid_url_type( $path_parts_4 ) );
        $this->assertTrue( Pos_Info::is_valid_url_type( $path_parts_5 ) );
        $this->assertTrue( Pos_Info::is_valid_url_type( $path_parts_6 ) );
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
        
        $this->assertFalse( Pos_Info::is_valid_url_type( $path_parts_1 ) );
        $this->assertFalse( Pos_Info::is_valid_url_type( $path_parts_2 ) );
        $this->assertFalse( Pos_Info::is_valid_url_type( $path_parts_3 ) );
        $this->assertFalse( Pos_Info::is_valid_url_type( $path_parts_4 ) );
        $this->assertFalse( Pos_Info::is_valid_url_type( $path_parts_5 ) );
        $this->assertFalse( Pos_Info::is_valid_url_type( $path_parts_6 ) );
        // End of the test.
    }

    /**
     * Tests the function that verifies if the slug is empty.
     */
    public function test_is_slug_empty() 
    {
        // Test the cases where the slug isn't empty.
        $valid_url_1 = 'https://wordpress.org/plugins/my-plugin/';
        $url_parts = parse_url( $valid_url_1 );
        $path_parts_1 = explode( '/', $url_parts['path'] );
        
        $valid_url_2 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-themes/nice-theme/';
        $url_parts = parse_url( $valid_url_2 );
        $path_parts_2 = explode( '/', $url_parts['path'] );

        $this->assertFalse( Pos_Info::is_slug_empty( $path_parts_1 ) );
        $this->assertFalse( Pos_Info::is_slug_empty( $path_parts_2 ) );
        // End of the test.

        // Test the cases where the slug is empty.
        $invalid_url_1 = 'https://wordpress.org/plugins//';
        $url_parts = parse_url( $invalid_url_1 );
        $path_parts_1 = explode( '/', $url_parts['path'] );
        
        $invalid_url_2 = 'https://translate.wordpress.org/locale/fr-ca/default/wp-themes//';
        $url_parts = parse_url( $invalid_url_2 );
        $path_parts_2 = explode( '/', $url_parts['path'] );

        $this->assertTrue( Pos_info::is_slug_empty( $path_parts_1 ) );
        $this->assertTrue( Pos_info::is_slug_empty( $path_parts_2 ) );
        // End of the test.
    }
}



