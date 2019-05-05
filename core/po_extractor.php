<?php

/**
 * WP PO Merger - Po_Extractor Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.0.0
 * @author SatelliteWP <info@satellitewp.com>
 */
namespace satellitewp\po;

require_once('po.php');

/**
 * Extracts the msg strings into arrays.
 */
class Po_Extractor extends Po {

    /**
     * Content of a *.po file.
     */
    protected $po_content = array(); 
    
    /**
     * Extracted msgid strings.
     */
    protected $msgids = array();
    
    /**
     * Extracted msgid_plural strings.
     */
    protected $msgids_plural = array();
    
    /**
     * Extracted msgstr strings.
     */
    protected $msgstrs = array();
    
    /**
     * Extracted plural msgstr strings.
     */
    protected $msgstrs_plural = array();
    
    /**
     * mgstr plural forms (ex: msgstr[0], msgstr[1]...).
     */
    protected $plural_forms = array();

    /**
     * Constructor.
     */
    public function __construct(){}

    /**
     * Sets the parameter.
     *
     * @param array $po_content Content of a *po file.
     */
    public function initialize( $po_content ) 
    {
        $this->po_content = $po_content;
    }
    
    
    /**
     * Extracts the msg strings into their corresponding arrays.
     * 
     * The singular msgid and its msgstr will have the same index in their 
     * corresponing arrays.
     * 
     * For the plural cases, the msgstr will have the index of:
     * (msgid_plural index * number of plural forms) + its number.
     * Example: index of the msgid_plural = 1, plural forms = 2, searching msgstr[0]
     * Index of msgstr[0]: (1 * 2) + 0
     * Index of msgstr[1]: (1 * 2) + 1
     * Etc... 
     *
     * @return array Multidimensional array of the extracted msg strings.
     */
    public function extract_msgs() 
    {
        // Number of plural forms.
        $nplurals = null;
        
        for ( $i = 0; $i < count( $this->po_content ); ++$i ) 
        {
            $current = $this->po_content[$i];

            // Get the number of plural forms.
            if ( strpos( $current, 'nplurals') !== false ) 
            {
                $nplurals = $this->get_nplurals( $current );
            }
            
            // If it's an msg string.
            if ( strpos( $current, $this->msg_strings[self::MSGID] ) !== false ||
                 strpos( $current, $this->msg_strings[self::MSGSTR] ) !== false ) 
            {
                $msg = $this->get_msg_and_content( $current )['msg'];

                switch ( $msg ) 
                {
                    // If it's not a plural case, add the msgid into the $msgids array.
                    case $msg == $this->msg_strings[self::MSGID]:
                        $this->process_msgid( $current, $this->po_content[$i + 1] );
                        break;

                    case $msg == $this->msg_strings[self::MSGSTR]:
                        $this->msgstrs[] = $current;
                        break;

                    case $msg == $this->msg_strings[self::MSGID_PLURAL]:
                        $this->msgids_plural[] = $current;
                        break;

                    // Otherwise, it's a plural msgstr string.
                    default:
                        $this->msgstrs_plural[] = $current;
                        
                        // Add the plural form to the $plural_forms array.
                        $this->add_plural_form( $msg, $nplurals );
                        break;
                }
            }
        }
        
        return array(
            'msgids'         => $this->msgids,
            'msgids_plural'  => $this->msgids_plural, 
            'msgstrs'        => $this->msgstrs,
            'msgstrs_plural' => $this->msgstrs_plural, 
            'plural_forms'   => $this->plural_forms
        );
    }

    /**
     * Verifies if the string after the msgid is not an msgid_plural,
     * since the plural strings are extracted into their corresponing arrays,
     * and in a plural case the msgid is redundant for searching purposes.
     * Furthermore, adding the msgid of a plural case will break the index
     * egality between singular msgid and its msgstr string.
     * 
     * @param string $msgid msgid string
     * @param string $next_string
     */
    public function process_msgid( $msgid, $next_string ) 
    {
        // Verify the next string.
        $next_msg = $this->get_msg_and_content( $next_string )['msg'];
        
        // If it's not an msgid_plural, extract the msgid into the $msgids array.
        if ( $next_msg != $this->msg_strings[self::MSGID_PLURAL] ) 
        {
            $this->msgids[] = $msgid;
        }
    }

    /**
     * Adds a plural form (msgstr[0], msgstr[1], etc) to the corresponding array,
     * if the array doesn't contain it already.
     * 
     * @param string $msgstr_pural Plural form.
     * @param string $nplurals Number of plural forms.
     */
    public function add_plural_form( $msgstr_pural, $nplurals ) 
    {
        for ( $i = 0; $i < $nplurals; ++$i ) 
        {
            // If the plural is in the defined range
            if ( $msgstr_pural == 'msgstr['.$i.']' ) 
            {
                if ( !in_array( $msgstr_pural, $this->plural_forms ) ) 
                {
                    $this->plural_forms[] = $msgstr_pural;
                }
            }
        }    
    }

    /**
     * Gets the number of plural forms.
     * 
     * @param string $nplurals_string String containing the number of plural forms.
     * 
     * @return string|null Number of plural forms, or null if the process has failed.
     */
    public function get_nplurals( $nplurals_string ) 
    {
        $result = null;

        $string_parts = explode( ' ', $nplurals_string );
        
        if ( count( $string_parts ) >= 2 ) 
        {
            $string = $string_parts[1];
            $nplurals_parts = explode( '=', $string );
            
            if ( count( $nplurals_parts ) === 2 )
            {
                $nplurals = str_replace( ';', '', $nplurals_parts[1] ); 
                
                if ( $nplurals_parts[0] == 'nplurals' && 
                    ( filter_var( $nplurals, FILTER_VALIDATE_INT ) === 0 || filter_var( $nplurals, FILTER_VALIDATE_INT ) ) ) 
                {
                    $result = $nplurals;
                }
            }
        }

        return $result;
    }
}