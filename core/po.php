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
     * Index of the 'msgid' string in the @see $msg_types array.
     */
    const MSGID = 0;

    /**
     * Index of the 'msgstr' string in the @see $msg_types array.
     */
    const MSGSTR = 1;

    /**
     * Index of the 'msgid_plural' string in the @see $msg_types array.
     */
    const MSGID_PLURAL = 2;
    
    /**
     * msg strings to verify while extracting and merging strings.
     */
    protected $msg_types = array( 'msgid', 'msgstr', 'msgid_plural' );

    /**
     * Gets the msg part of the string (msgid, msgid_plural, msgstr, msgstr[0], etc) and its content.
     * 
     * @param string $line String to extract data from.
     * 
     * @return array Message type (msg) and content (content)
     */
    public function get_msg_and_content( $line ) 
    {
        $result = array(
            'msg' => null,
            'content' => null,
        );

        // Splits string with quote (") separtor
        $parts = explode( '"', $line );
        
        if ( count( $parts ) > 1 ) 
        {
            // Get message type (and remove spaces)
            $type = str_replace( ' ', '', $parts[0] );

            // Checks if message type is one we handle (plural or singular)
            if ( preg_match( '/^(msgstr\[[0-9]\])$/', $type ) || in_array( $type, $this->msg_types ) ) 
            {
                $result['msg'] = $type;
                $result['content'] = $parts[1];
            }
        }
        
        return $result;
    }
}