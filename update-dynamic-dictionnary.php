<?php

/**
 * PO Merger
 *
 * PO Merger is a WP-CLI command that merges two PO files together to
 * make translation of two similar languages faste (e.g. fr vs fr-ca)
 *
 * Dictionnary management
 *
 * Process:
 * Download 100% translated po files from themes, plugins, core...
 * 
 *
 * @version 1.0.0
 * @author Cédric Béthencourt
 */

//namespace satellitewp\po;
//require_once 'command.php';
//require_once 'core/merger.php';
require_once 'vendor/autoload.php';
require_once 'core/dictionnary.php';
use Goutte\Client;
use satellitewp\po\Merger;
use satellitewp\po\Dictionnary;

// @TODO : remove
//$argv[1] = 'fr-ca';
//$argv[2] = 'https://wordpress.org/themes/weaver-xtreme/';



$args[0] = $argv[1];
$args[1] = $argv[1];
$args[2] = $argv[2];


$dictionnaries = array();
$pos_save_paths = array();

$locale = $argv[1];
$poExtension = $locale.'.po';

$themeUrl = 'https://translate.wordpress.org/locale/'.$locale.'/default/stats/themes/';
$pluginUrl = 'https://translate.wordpress.org/locale/'.$locale.'/default/stats/plugins/';

$dictionnariesPath = './dictionnaries/'.$locale.'/';
$dictionnaryName = $dictionnariesPath.'dictionnary-'.$poExtension;
//$dictionnaryName = $dictionnariesPath.'fake-static-dictionnary-'.$poExtension; // @TODO: remove
$dynamicDictionnaryName = $dictionnariesPath.'dynamic-dictionnary-'.$poExtension;

$start = time();
echo "Strarting...";
echo "\n";

// Go get all themes 100% translated in $locale
echo 'Getting themes 100% translated from '.$themeUrl."\n";
/*
*/
$client = new Client();
$crawler = $client->request('GET', $themeUrl);
$dictionnaries = $crawler->filter('td[data-sort-value="100"] a')->each(function ($node) {
    return $node->attr('href');
});


$merger = new Merger($args);
if(false === $merger->has_valid_parameters()) {
	die('Invalid merger params');
}

if(false === $merger->can_start()) {
	die('Merger cannot start');
}

/*
*/
echo 'Building theme dictionnaries path'."\n";
foreach ($dictionnaries as $key => $value) {
	$dictionnaries[$key] = 'https://translate.wordpress.org'.$value.'export-translations/';
	$parts = explode('/', $value);
	$pos_save_paths[$key] = $merger->get_download_folder_path().$parts[2].'-'.$parts[3].'-'.$poExtension;
	echo 'Building '.$parts[3].'...'."\n";;
}


echo 'Downloading themes 100% translated as dictionnaries'."\n";
//$merger->download_multiple_pos($pos_save_paths, $dictionnaries);

$dynamicDictionnary = new Dictionnary($dynamicDictionnaryName);

// @TODO : remove
//$pos_save_paths = array('C:\xampp\php\www\po-merger/downloads/wp-themes-twentyseventeen-fr-ca.po');
//$pos_save_paths[] = 'C:\xampp\php\www\po-merger/downloads/wp-a-fake-theme-fr-ca.po';


// Parse static dictionnary file containing translations
//echo 'Parsing static dictionnary'."\n";
$staticDictionnaryHandler = new Sepia\PoParser\SourceHandler\FileSystem($dictionnaryName);
$staticDictionnaryParser = new Sepia\PoParser\Parser($staticDictionnaryHandler);
$staticDictionnaryCatalog = $staticDictionnaryParser->parse();
$staticDictionnaryEntries = $staticDictionnaryCatalog->getEntries();

$dynamicDictionnaryHandler = new Sepia\PoParser\SourceHandler\FileSystem($dynamicDictionnaryName);
$dynamicDictionnaryParser = new Sepia\PoParser\Parser($dynamicDictionnaryHandler);
$dynamicDictionnaryCatalog = $dynamicDictionnaryParser->parse();
$dynamicDictionnaryEntries = $dynamicDictionnaryCatalog->getEntries();

echo 'Fetching interesting translations'."\n";
echo "\n";
echo "\n";
echo 'Used symbols legend:'."\n";
echo '. = entry'."\n";
echo '. = single string'."\n";
echo '+ = plural string'."\n";
echo '> = skipped translation'."\n";
echo '* = executed translation'."\n";
echo "\n";
foreach ($pos_save_paths as $key => $value) {
	$fileToTranslateHandler = new Sepia\PoParser\SourceHandler\FileSystem($value);
	$guessbookParser = new Sepia\PoParser\Parser($fileToTranslateHandler);
	$guessbook = $guessbookParser->parse();

	echo "\n";
	echo 'Getting translations from '.$value;
	echo "\n";
	$entries = $guessbook->getEntries();


	foreach ($entries as $entry) {
		echo'-';
		if (empty($entry->getMsgId()) or empty($entry->getMsgStr())) {
			echo'>';
			continue;
		}

		if ($staticDictionnaryCatalog->getEntry($entry->getMsgId())) {
			echo'>';
			continue;
		}

		if ($dynamicDictionnaryCatalog->getEntry($entry->getMsgId())) {
			echo'>';
			continue;
		}

		// not found in static dictionnary so add new entry in dynamic dictionnary
		$entry->setDeveloperComments(null);
		$entry->setTranslatorComments(null);
		$entry->setReference(null);
		$entry->setMsgCtxt(null);

		echo'*';
		$dynamicDictionnaryCatalog->addEntry($entry);
	}
}

echo "\n";
echo 'Saving dynamic dictionnary update '.$dynamicDictionnaryName.'...';
echo "\n";
$compiler = new Sepia\PoParser\PoCompiler();
$dynamicDictionnaryHandler->save($compiler->compile($dynamicDictionnaryCatalog));

//$merger->delete_downloaded_pos($pos_save_paths);

$end = time();
$time = ($end - $start);
echo "\n";
echo 'Executed in '.$time.' s.';
echo "\n";
echo "End";
echo "\n";