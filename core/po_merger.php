<?php

/**
 * WP PO Merger - Po_Merger Class
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
 * Creates a merged content, using the content of the base locale and the
 * existing translations from the copy locale.
 * 
 * @see Po
 */
class Po_Merger extends Po
{
    /**
     * Tag to mark the translations that require revision.
     */
    const FUZZY_TAG = '#, fuzzy';
    
    /**
     * Content of the base locale *.po file with the merged translations from
     * copy locale *.po file.
     */
    protected $merged_content = array();

    /**
     * Msgid strings of the copy locale *.po file.
     */
    protected $msgids_copy = array();

    /**
     * Msgstr strings of the copy locale *.po file.
     */
    protected $msgstrs_copy = array();
    
    /**
     * Plural msgid strings of the copy locale *.po file.
     */
    protected $msgids_plural_copy = array();
    
    /**
     * Plural msgstr strings of the copy locale *.po file.
     */
    protected $msgstrs_plural_copy = array();

    /**
     * Plural forms of a translation (ie: msgstr[0], msgstr[1]...),
     * based on the number of the plurals forms obtained from the *.po file.
     */
    protected $plural_forms = array();
    
    /**
     * Strings (words, expressions, etc) to search in a translation obtained from
     * the copy locale. If found, the msgid of the translation will be marked with the
     * @see self::FUZZY tag.
     */
    protected $fuzzy_strings = null;

    /**
     * Indicates if the msgid of a translation found in the copy locale and copied to the base locale
     * should be marked with the @see self::FUZZY tag.
     */
    protected $is_mcaf = false;

    /**
     * Constructor.
     * 
     * @param array $fuzzy_string Strings (words, expressions, etc) that need to be revised in a translation.
     * @param boolean $is_mcaf Is "marked copy as fuzzy" activated?
     */
    public function __construct( $fuzzy_strings, $is_mcaf )
    {
        $this->fuzzy_strings = $fuzzy_strings;
        $this->is_mcaf       = $is_mcaf;
    }

    /**
     * Sets the parameters.
     * 
     * @param array $base_content Content of the base locale PO. 
     * @param array $copy_content Content of the copy locale PO.
     */
    public function initialize( $base_content, $copy_content ) 
    {
        // Copy the initial content from the base locale.
        $this->merged_content = $base_content;
        
        $this->msgids_copy         = $copy_content['msgids'];
        $this->msgids_plural_copy  = $copy_content['msgids_plural'];
        $this->msgstrs_copy        = $copy_content['msgstrs'];
        $this->msgstrs_plural_copy = $copy_content['msgstrs_plural'];
        $this->plural_forms        = $copy_content['plural_forms'];
    }

     /**
     * Verifies if in the base locale a given translation doesn't exist.
     * If it's the case, searches for the translation in the extracted msg
     * strings from the copy locale.
     * 
     * @return array Content of the base locale with the merged translations from the copy locale.
     */
    public function merge_po() 
    {        
        // Index of an msgid in the base locale.
        $msgid_index = 0;

        for ( $i = 0; $i < count( $this->merged_content ); ++$i ) 
        {
            $current = $this->merged_content[$i];
            
            // If it's an msg string.
            if ( strpos( $current, $this->msg_types[self::MSGID] ) !== false  || 
                 strpos( $current, $this->msg_types[self::MSGSTR] ) !== false ) 
            {
                // Extract the msg part of the string.
                $msg = $this->get_msg_and_content( $current )['msg'];
                
                // If it's not a plural msgstr, since they are a special case.
                if ( !in_array( $msg, $this->plural_forms ) ) 
                {
                    switch ( $msg ) 
                    {
                        // If it's an msgid string, save its index.
                        case $this->msg_types[self::MSGID]:
                            $msgid_index = $i;
                            break;

                        // Verify if the translation doesn't exist in the base locale and search for
                        // it in the copy locale.
                        case $this->msg_types[self::MSGSTR]:
                            $this->process_singular_msgstr( $msgid_index, $i, $current );
                            break;

                        // Verify if the plural translations don't exist in the base locale and search 
                        // for them in the copy locale.
                        case $this->msg_types[self::MSGID_PLURAL]:
                            $this->process_plural_msgstrs( $msgid_index, $i, $current );
                            break;
                    }
                }
            }
        }
        return $this->merged_content;
    }

    /**
     * Processes a singular msgstr: if in the base locale a translation
     * doesn't exist, search for it in the msgstr strings extracted from the copy locale.
     * If the translation has been found, copy it to the base locale.
     * 
     * The singular msgid and its msgstr will have the same index in their 
     * corresponing arrays.
     * 
     * @param int $msgid_index Index of an msgid in the base locale.
     * @param int $msgstr_index Index of an msgstr in the base locale.
     * @param string $msgstr msgstr of the base locale.
     */
    public function process_singular_msgstr( $msgid_index, $msgstr_index, $msgstr ) 
    {   
        // Get the msgid in the base locale.
        $msgid = $this->merged_content[$msgid_index];

        // Get the content of the msgstr.
        $content_msgstr = $this->get_msg_and_content( $msgstr )['content'];
        
        // If the translation doesn't exist in the base locale.
        if ( empty( $content_msgstr ) ) 
        {
            // Get the index of the msgid extracted from the copy locale.
            $msgid_index_copy = array_search( $msgid, $this->msgids_copy );
            
            // Get the msgstr extracted from the copy locale.
            $msgstr_copy = $this->msgstrs_copy[$msgid_index_copy];
            $content_msgstr_copy = $this->get_msg_and_content( $msgstr_copy )['content'];
            
            if ( !empty( $content_msgstr_copy ) ) 
            {
                // Apply the "#, fuzzy" tag to the line preceding the msgid, if required.
                if ( $this->is_mcaf && $msgid != 'msgid ""' ) 
                {
                    $this->merged_content[$msgid_index - 1] .= self::FUZZY_TAG . "\n";
                }
                
                // Verify if a translation contains the strings that require revison and apply the "#, fuzzy" tag.
                if ( !is_null( $this->fuzzy_strings ) ) 
                {
                    $this->process_fuzzy_strings( $msgid_index, $content_msgstr_copy );
                }
                
                // Copy the found translation to the base locale.
                $this->merged_content[$msgstr_index] = $msgstr_copy;
            }
        }
    }

    /**
     * Processes the plural msgstr strings: if in the base locale the translations
     * don't exist, search for them in the msgstr plural strings extracted from the copy locale.
     * If the translations have been found, copy them to the base locale.
     * 
     * For the plural cases, the msgstr will have the index of:
     * (msgid_plural index * number of plural forms) + its number.
     * Example: index of the msgid_plural = 1, plural forms = 2, searching msgstr[0]
     * Index of msgstr[0]: (1 * 2) + 0
     * Index of msgstr[1]: (1 * 2) + 1
     * Etc... 
     * 
     * @param int $msgid_index Index of an msgid in the base locale.
     * @param int $msgid_plural_index Index of an msgid_plural in the base locale.
     * @param string $msgid_plural msgid_plural of the base locale.
     */
    public function process_plural_msgstrs( $msgid_index, $msgid_plural_index, $msgid_plural ) 
    {   
        // Get the msgid from the base locale.
        $msgid = $this->merged_content[$msgid_index];
        
        // Get the index of the msgid_plural in the plural msgids extracted from the copy locale.
        $msgid_plural_copy_index = array_search( $msgid_plural, $this->msgids_plural_copy );

        // Indicates if $is_mcaf and $fuzzy_strings were processed already.
        $params_processed = false;
         
        // See the formula in the description of the function.
        if ( $msgid_plural_copy_index > 0 ) 
        {
            $msgid_plural_copy_index *= count ( $this->plural_forms );
        }

        // Based on the nubmer of the plural forms, veirfy each plural msgstr.
        for ( $i = 0; $i < count( $this->plural_forms ); ++$i ) 
        {
            // Get the msgstr plural from the base locale content.
            $msgstr = $this->merged_content[$msgid_plural_index + $i + 1];
            $msgstr_content = $this->get_msg_and_content( $msgstr )['content'];

            // If the translation doesn't exist in the base locale.
            if ( empty( $msgstr_content ) ) 
            {
                // Search for the translation in the plural msgstr strings extracted from the copy locale.
                $msgstr_copy = $this->msgstrs_plural_copy[$msgid_plural_copy_index + $i];
                $content_msgstr_copy = $this->get_msg_and_content( $msgstr_copy )['content'];

                if ( !empty( $content_msgstr_copy ) ) 
                {
                    if ( !$params_processed ) 
                    {
                        // Apply the "#, fuzzy" tag to the line preceding the msgid, if required.
                        if ( $this->is_mcaf && $msgid != 'msgid ""' ) 
                        {
                            $this->merged_content[$msgid_index - 1] .= self::FUZZY_TAG . "\n";
                        }
                        
                        // Verify if a translation contains the strings that require revison and apply the "#, fuzzy" tag.
                        if ( !is_null( $this->fuzzy_strings ) ) 
                        {
                            $this->process_fuzzy_strings( $msgid_index, $content_msgstr_copy );
                        }
                        $params_processed = true;
                    }
                    
                    // Copy the found translation to the base locale.
                    $this->merged_content[$msgid_plural_index + $i + 1] = $msgstr_copy;
                }
            }
        }
    }

    /**
     * Verifies if the translation contains the strings specified in the $fuzzy_strings array. 
     * If it's the case, applies the @see self::Fuzzy_TAG to the line in the base locale preceding the 
     * specified msgid.
     * 
     * @param int $msgid_index Index of an msgid in the base locale.
     * @param string $translation Translation to verify.
     */
    public function process_fuzzy_strings( $msgid_index, $translation ) 
    {
        foreach ( $this->fuzzy_strings as $fuzzy_string ) 
        {
            if ( mb_stripos( $translation, $fuzzy_string ) !== false ) 
            {
                $this->merged_content[$msgid_index - 1] .= self::FUZZY_TAG . "\n";
                break;
            }
        }
    }
}