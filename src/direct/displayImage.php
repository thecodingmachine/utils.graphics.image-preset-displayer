<?php
require_once dirname(__FILE__).'/../../../../../../Mouf.php';

$diplayerInstance = $_GET['instance'];
$sourceFileName = $_GET['url'];

$imageDisplay = MoufManager::getMoufManager()->getInstance($diplayerInstance);

$imageDisplay->sourceFileName = $sourceFileName;
$imageDisplay->outputImage();
