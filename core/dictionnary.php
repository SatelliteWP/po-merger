<?php

/**
 * WP PO Merger - Dictionnary Class
 * 
 * WP PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * @version 1.0.0
 * @author Cédric Béthencourt
 */

namespace satellitewp\po;
use \Sepia\PoParser\Catalog\CatalogArray;

/**
 * Downloads and merges two WordPress plugin/theme *.po files.
 */
class Dictionnary {
    
    /**
     * Parameters.
     */

    /**
     * Constructor. 
     * 
     */
    public function __construct($name)
    {
        $this->name = $name;

        if(false === file_exists($name)) {
            echo 'Dictionnary '.$name.' is missing'."\n";
            echo 'Creating '.$name."\n";
            $poFile = fopen($name, 'w');
            $txt = "msgid \"\"\nmsgstr \"\"\n";
            fwrite($poFile, $txt);
            fclose($poFile);
            echo $name.' created!'."\n";
        }
    }
}
