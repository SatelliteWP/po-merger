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
 * A superclass containing the variables and functions used in the
 * inherited classes.
 */
class Po 
{
    /**
     * Index of the 'msgid' string in the @see $msg_strings array.
     */
    const MSGID = 0;

    /**
     * Index of the 'msgstr' string in the @see $msg_strings array.
     */
    const MSGSTR = 1;

    /**
     * Index of the 'msgid_plural' string in the @see $msg_strings array.
     */
    const MSGID_PLURAL = 2;
    
    /**
     * msg strings to verify while extracting and merging strings.
     */
    protected $msg_strings = array( 'msgid', 'msgstr', 'msgid_plural' );

    /**
     * Gets the msg part of the string (msgid, msgid_plural, msgstr, msgstr[0], etc) and its content.
     * 
     * @param $msg_string
     * 
     * @return array Associative array with the obtained information.
     */
    public function get_msg_and_content( $msg_string ) 
    {
        $msg = null;
        $content = null;

        $string_parts = explode( '"', $msg_string );
        
        if ( count( $string_parts ) > 1 ) 
        {
            $string = str_replace( ' ', '', $string_parts[0] );

            if ( preg_match( '/^(msgstr\[[0-9]\])$/', $string ) || in_array( $string, $this->msg_strings ) ) 
            {
                $msg = $string;
                $content = $string_parts[1];
            }
        }
        return array(
            'msg' => $msg,
            'content' => $content,
        );
    }
}