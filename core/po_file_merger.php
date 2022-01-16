<?php

/**
 * WP PO File Merger - Po_File_Merger Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together. 
 *
 * @version 1.2.0
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
     * Mark merged translations as fuzzy for manual validation
     * Boolean
     */
    protected $mark_copy_as_fuzzy = true;

    /**
     * Keep translations comments?
     */
    protected $keep_comments = false;

    /**
     * Keep translations references?
     */
    protected $keep_references = false;

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
     * @param string $base_content Content of the base locale PO. 
     * @param string $copy_content Content of the copy locale PO.
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
     * Mark copy as fuzzy ?
     * 
     * @param bool $value Mark merged translations as fuzzy for manual validation
     */
    public function set_mark_copy_as_fuzzy( $value )
    {
        $this->mark_copy_as_fuzzy = $value;
    }

    /**
     * Verifies if, in the base file, a given translation doesn't exist from the copy file.
     * If it does not exist, it is added to the base file.
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
                if ( strlen( $tr->getOriginal() ) <= $this->max_length || $this->max_length < 0 )
                {
                    $translation = $this->base->find( $tr->getContext(), $tr->getOriginal() );
                    
                    if ( $translation === false )
                    {
                        if ( $this->mark_copy_as_fuzzy ) 
                        {
                            $tr->addFlag( 'fuzzy' ) ;
                        }

                        if ( ! $this->keep_comments )
                        {
                            $tr->deleteComments();
                        }

                        if ( ! $this->keep_references )
                        {
                            $tr->deleteReferences();
                        }

                        // Add translation to base
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

    /**
     * Verifies if, in the base file, a given translation exist from the copy file.
     * If it does not exist, it is added to the a new file.
     */
    public function diff()
    {
        $new = new Translations();
        $count_progress = count( $this->copy );
        $progress = \WP_CLI\Utils\make_progress_bar( 'Processing ' . $count_progress . ' translations of Copy PO file...', $count_progress );

        $added = 0;
        foreach( $this->copy as $tr ) 
        {
            $flags = $tr->getFlags();

            if ( $tr->hasTranslation() && ! in_array( 'fuzzy', $flags ) ) 
            {
                if ( strlen( $tr->getOriginal() ) <= $this->max_length || $this->max_length < 0 )
                {
                    $translation = $this->base->find( $tr->getContext(), $tr->getOriginal() );
                    
                    if ( $translation === false )
                    {
                        $new[] = $tr;
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
            //$file = 'diff-' . date( 'Y-m-d-H-i-s' ) . '.po';
            $file = 'diff.po';
            $new->toPoFile( $file );

            $text = $added > 1 ? $added  . ' translations were found.' : $added . ' translation was found.';
            \WP_CLI::success( $text . ' You can find them in the following file: ' . $file );
        }
        else
        {
            \WP_CLI::warning( 'No translations were found.' );
        }
    }

}