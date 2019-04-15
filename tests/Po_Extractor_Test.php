<?php

namespace satellitewp\po;

include_once(__DIR__.'/../core/po_extractor.php');

use PHPUnit\Framework\TestCase;

/**
 * Tests the functions used by the class Po_Extractor.
 */
class Po_Extractor_Test extends TestCase 
{
    /**
     * Content of a *.po file.
     */
    private $po_content = null;
    
    /**
     * Instance of the class.
     */
    private $po_extractor = null;

    /**
     * Initialize the class and read the valid *.po file.
     */
    public function setUp()
    {
        $this->po_content = file( __DIR__.'/files/po_copy.po' );
        $this->po_extractor = new Po_Extractor( $this->po_content );
    }

    /**
     * Reinitialize the variables at the end of the test.
     */
    public function tearDown()
    {
        $this->po_content = null;
        $this->po_extractor = null;
    }
    
    /**
     * Tests the function that gets the number of plural form
     * a valid *.po file.
     */
    public function test_get_nplurals() 
    {
        // Test valid nplural strings.
        $npplurals_2 = "Plural-Forms: nplurals=2; plural=n > 1;\n";
        $npplurals_6 = "Plural-Forms: nplurals=6; plural=n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5;\n";
        $this->assertEquals( 2, $this->po_extractor->get_nplurals( $npplurals_2 ) );
        $this->assertEquals( 6, $this->po_extractor->get_nplurals( $npplurals_6 ) );
        
        // Test invalid strings.
        $npplurals_invalid_1 = "Plural-Forms: plural=n > 1;\n";
        $npplurals_invalid_2 = "Plural-Forms: nplurals=two; plural=n > 1;\n";;
        $this->assertEquals( null, $this->po_extractor->get_nplurals( $npplurals_invalid_1 ) );
        $this->assertEquals( null, $this->po_extractor->get_nplurals( $npplurals_invalid_2 ) );
    }
    
    /**
     * Tests the function that extracts all the msg strings from a
     * valid *.po file.
     */
    public function test_extract_msgs() 
    {
        // Test the extraction from a valid *.po file.
        $extracted_msgs =  $this->po_extractor->extract_msgs();
        
        $this->assertEquals( 3, count(  $extracted_msgs['msgids'] ) );
        $this->assertEquals( 1, count(  $extracted_msgs['msgids_plural'] ) );
        $this->assertEquals( 3, count(  $extracted_msgs['msgstrs'] ) );
        $this->assertEquals( 2, count(  $extracted_msgs['msgstrs_plural'] ) );
        $this->assertEquals( 2, count(  $extracted_msgs['plural_forms'] ) );

        // Test the extraction on a non *.po file.
        $content = array( 'A mollis at nulla', 'libero mollis amet', 'duis eget proin' );
        $this->po_extractor = new Po_Extractor( $content );
        $extracted_msgs =  $this->po_extractor->extract_msgs();
        
        $this->assertEquals( 0, count(  $extracted_msgs['msgids'] ) );
        $this->assertEquals( 0, count(  $extracted_msgs['msgids_plural'] ) );
        $this->assertEquals( 0, count(  $extracted_msgs['msgstrs'] ) );
        $this->assertEquals( 0, count(  $extracted_msgs['msgstrs_plural'] ) );
        $this->assertEquals( 0, count(  $extracted_msgs['plural_forms'] ) );
    }
}