<?php

include 'vendor/autoload.php';
require_once('core/pos_info.php');

use Gettext\Translations;
use satellitewp\po;


$pi = new \satellitewp\po\Pos_Info( 'fr-ca', 'fr' );
$pi->set_url( 'https://wordpress.org/plugins/wordpress-seo/' );

$vars = $pi->get_internal();

#var_dump( $vars );
/*
echo 'name: ' . $pi->get_project_name();
echo "\n";
echo 'type: ' . $pi->get_project_type();
echo "\n";
echo "\n";*/
echo $pi->get_base_download_url();
echo "\n";
echo $pi->get_copy_download_url();


exit;

$param = $argv[1];

$base = Translations::fromPoFile('tests/locale/fr-ca/'. $param .'-fr-ca.po');
$copy = Translations::fromPoFile('tests/locale/fr/'. $param .'-fr.po');

//var_dump( count( $base ) ); exit;

foreach( $base as $tr) {
    
    if ( ! $tr->hasTranslation() ) {
        $copy_tr = $copy->find( $tr->getContext(), $tr->getOriginal() );

        if ($copy_tr !== false) {
            $tr->setTranslation( $copy_tr->getTranslation() );

            if ($tr->hasPluralTranslations()) {
                $tr->setPluralTranslations( $copy_tr->getPluralTranslations() );
            }
        }

        //$tr->addFlag('Maxime');
    }
}

$base->toPoFile('result.po');

$res = Translations::fromPoFile('result.po');
$exp = Translations::fromPoFile('tests/locale/expected/'. $param .'-expected-fr-ca.po');

if ($res->toPoString() != $exp->toPoString()) {
    echo "different";
}
else {
    echo "ok";
}