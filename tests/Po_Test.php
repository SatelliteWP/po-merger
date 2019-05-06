<?php

namespace satellitewp\po;

include_once( 'functions.php' );
include_once( __DIR__.'/../core/po.php' );

use PHPUnit\Framework\TestCase;

/**
 * Tests the functions used by the class Po_Extractor.
 */
class Po_Test extends TestCase 
{
    /**
     * Instance of the class.
     */
    private $po = null;

    /**
     * Initialize the class and read the valid *.po file.
     */
    public function setUp()
    {
        $this->po = new Po();
    }

    /**
     * Reinitialize the variables at the end of the test.
     */
    public function tearDown()
    {
        $this->po = null;
    }
    
    /**
     * Tests the function that gets the msg ("msgid", "msgstr", etc) part of
     * a msg string and its content.
     */
    public function test_get_msg_and_content() 
    {
        // Test valid msg strings.
        $msgid = 'msgid "Translation Id"';
        $msgstr = 'msgstr "This is a translation"';
        $msgid_plural = 'msgid_plural "Plural translations Id"';
        $msgstr_plural_1 = 'msgstr[0] "This is a singular form"';
        $msgstr_plural_2 = 'msgstr[1] "This is a plural form"';  
        
        $this->assertEquals( 'msgid', $this->po->get_msg_and_content( $msgid )['msg'] );
        $this->assertEquals( 'Translation Id', $this->po->get_msg_and_content( $msgid )['content'] );
        $this->assertEquals( 'msgstr', $this->po->get_msg_and_content( $msgstr )['msg'] );
        $this->assertEquals( 'This is a translation', $this->po->get_msg_and_content( $msgstr )['content'] );
        $this->assertEquals( 'msgid_plural', $this->po->get_msg_and_content( $msgid_plural )['msg'] );
        $this->assertEquals( 'Plural translations Id', $this->po->get_msg_and_content( $msgid_plural )['content'] );
        $this->assertEquals( 'msgstr[0]', $this->po->get_msg_and_content( $msgstr_plural_1 )['msg'] );
        $this->assertEquals( 'This is a singular form', $this->po->get_msg_and_content( $msgstr_plural_1 )['content'] );
        $this->assertEquals( 'msgstr[1]', $this->po->get_msg_and_content( $msgstr_plural_2 )['msg'] );
        $this->assertEquals( 'This is a plural form', $this->po->get_msg_and_content( $msgstr_plural_2 )['content'] );

        // Test invalid msg strings.
        $msgid_invalid = '"msgid Translation Id"';
        $msgstr_invalid = 'msgstrs "This is a translation"';
        $msgid_plural_invalid = 'msgid_plural Plural translations Id"';
        $msgstr_plural_invalid_1 = 'msgstr[One] "This is a singular form"';
        $msgstr_plural_invalid_2 = 'msgstr[1 "This is a plural form"';
        $non_msg = 'A non-msg string';
        
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgid_invalid )['msg'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgid_invalid )['content'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgstr_invalid )['msg'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgstr_invalid )['content'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgid_plural_invalid )['msg'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgid_plural_invalid )['content'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgstr_plural_invalid_1 )['msg'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgstr_plural_invalid_1 )['content'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgstr_plural_invalid_2 )['msg'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $msgstr_plural_invalid_2 )['content'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $non_msg )['msg'] );
        $this->assertEquals( null, $this->po->get_msg_and_content( $non_msg)['content'] );  
    }
}



