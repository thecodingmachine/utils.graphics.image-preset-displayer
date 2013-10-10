<?php
use Mouf\MoufManager;
use Mouf\MoufUtils;

// Controller declaration
$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('staticimagedisplayer', 'Mouf\\Utils\\Graphics\\ImagePresetDisplayer\\Controller\\ImagePresetDisplayerController', true);
$moufManager->bindComponents('staticimagedisplayer', 'template', 'moufTemplate');
$moufManager->bindComponents('staticimagedisplayer', 'content', 'block.content');
