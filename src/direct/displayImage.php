<?php
use Mouf\MoufManager;
require_once '../../../../../mouf/Mouf.php';

if (!defined('ROOT_URL') && function_exists('apache_getenv')) {
	// FIXME: ROOT_URL is probably wrong because apache_getenv("BASE") is not relative to this directory but
	// is relative to the image directory 
	define('ROOT_URL', apache_getenv("BASE")."/../../../../../");
}

$diplayerInstance = $_GET['instance'];
$sourceFileName = $_GET['url'];

$imageDisplay = MoufManager::getMoufManager()->getInstance($diplayerInstance);

$imageDisplay->sourceFileName = $sourceFileName;
$imageDisplay->outputImage();
