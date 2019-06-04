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
     */
    public function test_case_100() 
    {
        $pi = new Pos_Info( 'fr-ca', 'fr' );
        $pi->set_url( 'https://wordpress.org/plugins/wordpress-seo/' );

        $vars = $pi->get_internal();

        
    }
}



