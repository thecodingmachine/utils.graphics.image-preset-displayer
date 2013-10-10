<?php
namespace Mouf\Utils\Graphics\Controller;

use Mouf\Controllers\AbstractMoufInstanceController;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\MoufUtils;
use Mouf\InstanceProxy;

/**
 * The controller to generate automatically the .htaccess file.
 * Sweet!
 * 
 */
class ImagePresetDisplayerController extends AbstractMoufInstanceController {
	
	protected $targetDir;
	
	/**
	 * Admin page used to display the button to generate the .htaccess file.
	 *
	 * @Action
	 */
	public function index($name, $selfedit="false") {
		$this->initController($name, $selfedit);
		
		$instanceDescriptor = $this->moufManager->getInstanceDescriptor($name);
		$this->targetDir = $instanceDescriptor->getProperty('savePath')->getValue();
		
		$this->content->addFile(dirname(__FILE__)."/../../../../views/htaccessGenerate.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Admin page used to display the button to generate the .htaccess file.
	 *
	 * @Action
	 * @Post
	 */
	public function createHtAccess($name) {
		$this->initController($name, "false");

		$this->targetDir = $instanceDescriptor->getProperty('savePath')->getValue();
		
		$staticImageDisplayer = new InstanceProxy($name);
		$staticImageDisplayer->writeHtAccess();
		
		header("Location: ".ROOT_URL."ajaxinstance/?name=".urlencode($name));
	}
	
	
	
}