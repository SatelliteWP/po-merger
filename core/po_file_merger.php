<?php

/**
 * WP PO File Merger - Po_File_Merger Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together. 
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
class Po_File_Merger
{
    /**
     * Base PO filename.
     */
    protected $base_filename = null;

    /**
     * Copy PO filename.
     */
    protected $copy_filename = null;

    /**
     * Base object
     */
    protected $base = null;

    /**
     * Copy object
     */
    protected $copy = null;

    /**
     * Max length of the original copy string to consider.
     */
    protected $max_length = 50;


    protected $stats = array(
        'total'                   => 0,
        'used-from-copy'          => 0,
        'used-from-dictionary'    => 0,
        'contained-fuzzy-strings' => 0
    );

    /**
     * Constructor.
     * 
     * @param array $base_content Content of the base locale PO. 
     * @param array $copy_content Content of the copy locale PO.
     
     */
    public function __construct( $base_filename, $copy_filename )
    {
        // Base filename
        $this->base_filename = $base_filename;
        
        // Copy filename
        $this->copy_filename = $copy_filename;
    }

    /**
     * Load files
     */
    public function load_files()
    {
        $this->base = Translations::fromPoFile( $this->base_filename );
        $this->copy = Translations::fromPoFile( $this->copy_filename );
    }

    /**
     * Maximum string length to consider when merging
     * 
     * @param int $max Max string length
     */
    public function set_max_length( $max )
    {
        $this->max_length = (int)$max;
    }

    /**
     * Verifies if in the base locale a given translation doesn't exist.
     * If it's the case, searches for the translation in the extracted msg
     * strings from the copy locale.
     * 
     * @return array Content of the base locale with the merged translations from the copy locale.
     */
    public function merge()
    {
        $count_progress = count( $this->copy );
        $progress = \WP_CLI\Utils\make_progress_bar( 'Processing ' . $count_progress . ' translations of Copy PO file...', $count_progress );

        $added = 0;
        foreach( $this->copy as $tr ) 
        {
            $flags = $tr->getFlags();

            if ( $tr->hasTranslation() && ! in_array( 'fuzzy', $flags ) ) 
            {
                #\WP_CLI::warning( 'Has translation. Not fuzzy.' );
                if ( strlen( $tr->getOriginal() ) <= $this->max_length || $this->max_length < 0 )
                {
                    #\WP_CLI::warning( 'Is under character length.' );
                    $translation = $this->base->find( $tr->getContext(), $tr->getOriginal() );
                    
                    if ( $translation === false )
                    {
                        #\WP_CLI::warning( 'Translation does not exist.' );
                        $this->base[] = $tr;
                        $added++;
                    }
                    else 
                    {
                        //We do nothing                        
                    }
                    
                }
            }
            $progress->tick();
        }
        $progress->finish();

        if ( $added > 0 )
        {
            $this->base->toPoFile( $this->base_filename );

            $text = $added > 1 ? $added  . ' translations were merged.' : $added . ' translation was merged.';
            \WP_CLI::success( $text );
        }
        else
        {
            \WP_CLI::warning( 'No translations were merged.' );
        }
    }
}