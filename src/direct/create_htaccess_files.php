<?php
use Mouf\MoufUtils;
use Mouf\MoufManager;
require_once '../../../../../mouf/Mouf.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
MoufUtils::checkRights();

$instanceName = $_GET['instanceName'];
$instanceObj = MoufManager::getMoufManager()->getInstance($instanceName);
	
$htAccessPath = ROOT_PATH.$instanceObj->savePath.".htaccess";
	$str = "Options FollowSymLinks
RewriteEngine on
RewriteBase ".ROOT_URL."$instanceObj->savePath
	
RewriteCond %{REQUEST_FILENAME} !-f
	
RewriteRule ^(.*)$ ".ROOT_URL."vendor/mouf/utils.graphics.image-preset-displayer/src/direct/displayImage.php?instance=$instanceName&url=$1";

	
$savePath = ROOT_PATH . $instanceObj->savePath;
if (!file_exists()){
	mkdir($savePath, 0777, true);
}
	
file_put_contents($htAccessPath, $str);
header("Location: ".ROOT_URL."vendor/mouf/mouf");
