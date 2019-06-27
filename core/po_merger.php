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

use Gettext\Translations;
use Gettext\Merge;

/**
 * Creates a merged content, using the content of the base locale and the
 * existing translations from the copy locale.
 * 
 * @see Po
 */
class Po_Merger
{
    /**
     * Base PO filename
     */
    protected $base_filename = null;

    /**
     * Copy PO filename
     */
    protected $copy_filename = null;

    /**
     * Dictionary PO filename
     */
    protected $dictionary_filename = null;
    
    /**
     * Strings (words, expressions, etc) to search in a translation obtained from
     * the copy locale. If found, the msgid of the translation will be marked as fuzzt.
     */
    protected $fuzzy_strings = null;

    /**
     * Indicates if the msgid of a translation found in the copy locale and copied to the base locale
     * should be marked as fuzzy.
     */
    protected $is_mcaf = false;

    /**
     * Should we only output the difference in the output file ?
     * In other words, output only new translations.
     */
    protected $is_difference_only = false;


    protected $stats = array(
        'total'                   => 0,
        'used-from-copy'          => 0,
        'used-from-dictionary'    => 0,
        'contained-fuzzy-strings' => 0
    );

    /**
     * Constructor.
     * 
     * @param array $fuzzy_string Strings (words, expressions, etc) that need to be revised in a translation.
     * @param boolean $is_mcaf Is "marked copy as fuzzy" activated?
     
     */
    public function __construct( $fuzzy_strings, $is_mcaf )
    {
        $this->fuzzy_strings      = $fuzzy_strings;
        $this->is_mcaf            = $is_mcaf;
        
    }

    /**
     * Sets the parameters.
     * 
     * @param array $base_content Content of the base locale PO. 
     * @param array $copy_content Content of the copy locale PO.
     * @param array $dictionary_content Content of the dictionary PO.
     * @param boolean $is_difference_only Should we only output new translations ?
     */
    public function initialize( $base_filename, $copy_filename, $dictionary_filename = null, $is_difference_only = false ) 
    {
        // Base filename
        $this->base_filename = $base_filename;
        
        // Copy filename
        $this->copy_filename = $copy_filename;
        
        // Dictionary filename
        $this->dictionary_filename = $dictionary_filename;

        // Is difference only ?
        $this->is_difference_only = $is_difference_only;
    }

    /**
     * Verifies if in the base locale a given translation doesn't exist.
     * If it's the case, searches for the translation in the extracted msg
     * strings from the copy locale.
     * 
     * @return array Content of the base locale with the merged translations from the copy locale.
     */
    public function merge( $filename )
    {
        $merged = new Translations();
        $base = Translations::fromPoFile( $this->base_filename );
        $copy = Translations::fromPoFile( $this->copy_filename );
        $dict = ( $this->dictionary_filename != null ? Translations::fromPoFile( $this->dictionary_filename ) : null );
        
        // Init
        $merged->mergeWith( $base, Merge::HEADERS_ADD | Merge::LANGUAGE_OVERRIDE | Merge::DOMAIN_OVERRIDE );

        // Stats
        $used_from_dictionary = 0;
        $used_from_copy = 0;
        $contained_fuzzy_strings = 0;

        $count_progress = count( $base );
        $progress = \WP_CLI\Utils\make_progress_bar( '', $count_progress );

        foreach( $base as $tr ) 
        {
            // Check the dictionary, if defined.
            $dict_tr = false;
            if ( $dict != null)
            {
                $dict_tr = $dict->find( $tr->getContext(), $tr->getOriginal() );
            }

            // If dictionary contains translation and it's different, we use it.
            if ( $dict_tr !== false && $dict_tr->getTranslation() != $tr->getTranslation() ) 
            {
                $tr->setTranslation( $dict_tr->getTranslation() );
        
                if ( $tr->hasPluralTranslations() ) 
                {
                    $tr->setPluralTranslations( $dict_tr->getPluralTranslations() );
                }

                if ( $this->is_mcaf )
                {
                    $tr->addFlag( 'fuzzy' );
                }
                elseif ( $this->has_fuzzy_strings( $tr ) )
                {
                    $tr->addFlag( 'fuzzy' );

                    $contained_fuzzy_strings++;
                }

                $merged[] = $tr;

                $used_from_dictionary++;
            }
            elseif ( ! $tr->hasTranslation() ) 
            {
                $copy_tr = $copy->find( $tr->getContext(), $tr->getOriginal() );
        
                if ( $copy_tr !== false ) {
                    $tr->setTranslation( $copy_tr->getTranslation() );
        
                    if ( $tr->hasPluralTranslations() ) 
                    {
                        $tr->setPluralTranslations( $copy_tr->getPluralTranslations() );
                    }

                    // If we want copy to be marked as fuzzy, we add a flag.
                    if ( $this->is_mcaf )
                    {
                        $tr->addFlag( 'fuzzy' );
                    }
                    // If it contains a fuzzy string, we add a flag.
                    elseif ( $this->has_fuzzy_strings( $tr ) )
                    {
                        $tr->addFlag( 'fuzzy' );

                        $contained_fuzzy_strings++;
                    }

                    $merged[] = $tr;

                    $used_from_copy++;
                }
            }
            else
            {
                // If it contains a fuzzy string, we add it
                if ( $this->has_fuzzy_strings( $tr ) ) 
                {
                    $tr->addFlag( 'fuzzy' );

                    $merged[] = $tr;

                    $contained_fuzzy_strings++;
                }
                // If we want all string, we add it
                elseif ( ! $this->is_difference_only )
                {
                    $merged[] = $tr;
                }
            }
            $progress->tick();
        }
        $progress->finish();

        $result = count( $merged );
        if ( $result > 0 )
        {
            $merged->toPoFile( $filename );
        }

        $this->stats = array(
            'total'                   => $result,
            'used-from-copy'          => $used_from_copy,
            'used-from-dictionary'    => $used_from_dictionary,
            'contained-fuzzy-strings' => $contained_fuzzy_strings
        );
        
        return $result;
    }

    /**
     * Returns statistics compiled when a merge occured.
     * 
     * @return array Associative array containing statistics
     */
    public function get_stats()
    {
        return $this->stats;
    }


    /**
     * Verifies if the translation contains the strings specified in the $fuzzy_strings array. 
     * 
     * @param string $translation Translation to verify.
     * 
     * @return boolean True if contains fuzzy string. Otherwise, false.
     */
    public function has_fuzzy_strings( $translation ) 
    {
        $result = false;

        $to_check = array();

        if ( $translation->hasTranslation() )
        {
            $to_check[] = $translation->getTranslation();
        }

        if ( $translation->hasPluralTranslations() )
        {
            $to_check = $to_check + $translation->getPluralTranslations();
        }

        foreach ( $this->fuzzy_strings as $fuzzy_string ) 
        {
            foreach($to_check as $str)
            {
                if ( mb_stripos( $str, $fuzzy_string ) !== false ) 
                {
                    $result = true;
                    break 2;
                }
            }
        }

        return $result;
    }
}