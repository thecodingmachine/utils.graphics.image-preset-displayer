<?php
require_once dirname(__FILE__)."/../../../../../../Mouf.php";

$instances = $_GET['instances'];

$instances = explode("|", $instances);

foreach ($instances as $instance) {
	$instanceObj = MoufManager::getMoufManager()->getInstance($instance);
	
	$dirName = dirname(__FILE__);
	$dir = dirname($dirName);
	$versionFolder = basename($dir);
	
	$htAccessPath = ROOT_PATH.$instanceObj->savePath.DIRECTORY_SEPARATOR.".htaccess";
	$str = "Options FollowSymLinks
RewriteEngine on
RewriteBase ".ROOT_URL."$instance->savePath
	
RewriteCond %{REQUEST_FILENAME} !-f
	
RewriteRule ^(.*)$ ".ROOT_URL."plugins/utils/graphics/imagepresetdisplayer/$versionFolder/direct/displayImage.php?instance=$instance&url=$1";

// 	echo $htAccessPath;
	$savePath = ROOT_PATH . $instanceObj->savePath;
	if (!file_exists()){
		mkdir($savePath, 0777, true);
	}
	
	file_put_contents($htAccessPath, $str);
}
header("Location: ".ROOT_URL."mouf");
