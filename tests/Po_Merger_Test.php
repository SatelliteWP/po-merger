<?php

namespace satellitewp\po;

include_once( 'functions.php' );
include_once( __DIR__.'/../core/po_merger.php' );

use PHPUnit\Framework\TestCase;

/**
 * Tests the functions used by the class Po_Extractor.
 */
class Po_Merger_Test extends TestCase 
{
     /**
     * Instance of the class.
     */
    private $po_merger = null;

    /**
     * Content from the base locale.
     */
    private $base_content = null;

    /**
     * Extracted msg strings from the copy locale.
     */
    private $extracted_msgs = array();

    /**
     * Indicates if the msgid of a translation found in the copy locale and copied to the base locale
     * should be marked with the fuzzy tag.
     */
    private $is_mcaf = false;

    /**
     * Initialize the class and read the valid *.po file.
     */
    public function setUp()
    {
        $this->base_content = file( __DIR__.'/files/po_base.po' );
        $this->copy_content = file( __DIR__.'/files/po_copy.po' );

        // See $copy_content for indexes
        $this->extracted_msgs = array(
            'msgids' => array( $this->copy_content[2], $this->copy_content[14], $this->copy_content[18] ),
            'msgstrs' => array( $this->copy_content[3], $this->copy_content[15], $this->copy_content[19] ),
            'msgids_plural' => array( $this->copy_content[28] ),
            'msgstrs_plural' => array( $this->copy_content[29], $this->copy_content[30] ),
            'plural_forms' => array( 'msgstr[0]', 'msgstr[1]' )
        );
    }

    /**
     * Reinitialize the variables at the end of the test.
     */
    public function tearDown()
    {
        $this->base_content = null;
        $this->extracted_msgs = null;
        $this->po_merger = null;
    }

    /**
     * Tests the function that verifies if in the base locale a given translation doesn't exist.
     * If it's the case, searches for the translation in the extracted msg
     * strings from the copy locale.
     * 
     */
    public function test_merge_po()
    {
        $this->po_merger = new Po_Merger( array(), false );
        $this->po_merger->initialize( $this->base_content, $this->extracted_msgs );
        
        $result = $this->po_merger->merge_po();
        
        $expected = file( __DIR__.'/files/po_expected.po' );

        $this->assertEquals( $expected, $result );
    }
}