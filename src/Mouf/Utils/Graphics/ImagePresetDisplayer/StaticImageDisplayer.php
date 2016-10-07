<?php
namespace Mouf\Utils\Graphics\ImagePresetDisplayer;

use Mouf\Validator\MoufValidatorResult;

use Mouf\MoufManager;

use Mouf\Validator\MoufValidatorInterface;

use Mouf\Utils\Graphics\MoufImage\MoufImageInterface;
use Mouf\Utils\Graphics\MoufImage\Filters\MoufImageFromFile;
/**
 * This Class will handle the display of MoufImages. 
 * <p>Images are successively processed by a set of instances implementing MoufImageInterface, and then, the final image resource will be outputed.
 * The first time the image is generated, it is saved. If you ask for the same image, the saved copy will be served, in order to save some time.</p>
 * <p>The StaticImageDisplayer is called by using a direct URL inside this package:</p> 
 *   <em>ROOT_URL/plugins/utils/graphics/moufimage/1.0/direct/displayImage.php</em>
 *  <p>This URL should be called using 2 parameters:</p>
 *  <ul>
 *  	<li>instance: name of the StaticImageDisplayer instance</li> 
 *      <li>path: relative path of the original image</li>
 *  </ul>
 *       
 *  <p>This class has helpers that will generate the given URL:</p> 
 *  <ul>
 *  	<li>Use $displayerInstance->getURL($path);</li>
 *    	<li>or $displayerInstance->toHTML($path);</li>
 * </ul>
 * 
 * @author Kevin
 * @ExtendedAction {"name":"Generate .htaccess", "url":"staticimagedisplayer/", "default":false}
 */
class StaticImageDisplayer implements MoufValidatorInterface {
	
	/**
	 * The name of the MoufImageFromFile instance that will be the first to load the image from the given input $sourceFileName
	 * @Property
	 * @Compulsory
	 * @var MoufImageFromFile
	 */
	public $initialImageFilter;
	
	/**
	 * The MoufImage instance's name that delivers the final image resource, with all applied effects
	 * @Property
	 * @Compulsory
	 * @var MoufImageInterface
	 */
	public $imageSource;
	
	/**
	 * The path into which the image file will be saved if it doesn't exist.
	 * This path is relative to the applcation's ROOT_PATH, and should have not have trailing slashes.
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $savePath;
	
	/**
	 * The path to the original image that will be loaded (by the $initialImageFilter, transformed by a set of MoufImage instances.
	 * This path is relative to the application's ROOT_PATH, and should have trailing slashes.
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $basePath;
	
	
	/**
	 * The path to the original image file, relative to the $basePath.
	 * The file name should not contain any '..' strings (for security reasons, the component dosn't allow users to access files outside the $basePath),
	 * but it may contain folders (ex: sub_folder/image.jpeg).
	 * @var string
	 */
	public $sourceFileName;
	
	/**
	 * The Quality that should be applied in case the original image is of JPEG type (0 to 100).
	 * @Property
	 * @var int
	 */
	public $jpegQuality = 75;
	
	/**
	 * The Quality that should be applied in case the original image is of PNG type (0 to 9).
	 * @Property
	 * @var int
	 */
	public $pngQuality = 6;
	
	/**
	 * The path to the default image if not found
	 * @Property
	 * @var int
	 */
	public $defaultImagePath;
	
	/**
	 * Output the image: 
	 *   - original image is loaded by the $initialImageFilter, 
	 *   - final image (given by the $imageSource) is outputed (and saved if it doesn't exist yet)
	 * @throws \Exception
	 */
	public function outputImage(){
		//Prevent from acessing parent folders
		if (strpos($this->sourceFileName, '..')) throw new \Exception("Trying to access file in parent folders : '$sourceFileName'");
		
		//rebuild the original file pathe from the root image folder and the relative file's pathe
		$originalFilePath = ROOT_PATH . $this->basePath . DIRECTORY_SEPARATOR . $this->sourceFileName;
// 		echo "$originalFilePath exits? ".(file_exists($originalFilePath) ? "ok" : "ko");exit;
		$is404 = false;
		if (!file_exists($originalFilePath) || !is_file($originalFilePath)){
			error_log("file not exists : $originalFilePath");
			if (empty($this->defaultImagePath)){
				$originalFilePath = dirname(__FILE__).DIRECTORY_SEPARATOR."404_image.png";
			}else{
				$originalFilePath = ROOT_PATH.$this->defaultImagePath;
			}
			$is404 = true;
		}
		$this->initialImageFilter->path = $originalFilePath;
		
		//Get the image after all effects have been applied
		$moufImageResource = $this->imageSource->getResource();
		$finalImage = $moufImageResource->resource;
		$image_info = $moufImageResource->originInfo;
		$image_type = $image_info[2];
		//Original file's relative path is the file's key, so no need to check whether there is already an image with the same file name
		$finalPath = ROOT_PATH . $this->getSavePath() . DIRECTORY_SEPARATOR . $this->sourceFileName;
		
		$created = true;
		if (!file_exists($finalPath) && !$is404){
			if($finalImage) {
				//if sourceFileName contains sub folders, create them
				$subPath = substr($this->sourceFileName, 0, strrpos($this->sourceFileName, "/"));
				if ($subPath != '.' && !file_exists(ROOT_PATH . $this->getSavePath() . "/" . $subPath)){
					$oldUmask = umask();
					umask(0);
					$dirCreate = mkdir(ROOT_PATH . $this->getSavePath() . DIRECTORY_SEPARATOR . $subPath, 0775, true);
					umask($oldUmask);
					if (!$dirCreate) {
						throw new \Exception("Could't create subfolders '$subPath' in " . ROOT_PATH . $this->getSavePath());
					}
				}
				
				//create the image
				if( $image_type == IMAGETYPE_JPEG ) {
					$created = imagejpeg($finalImage, $finalPath, $this->jpegQuality);
				} elseif( $image_type == IMAGETYPE_GIF ) {
					$created = imagegif($finalImage, $finalPath);
				} elseif( $image_type == IMAGETYPE_PNG ) {
					$created = imagepng($finalImage, $finalPath, $this->pngQuality);
				}
				chmod($finalPath, 0664);
			}
			else {
				$is404 = true;
				if (empty($this->defaultImagePath)){
					$originalFilePath = dirname(__FILE__).DIRECTORY_SEPARATOR."404_image.png";
				}else{
					$originalFilePath = ROOT_PATH.$this->defaultImagePath;
				}
			}
		}
		
		if (!$created && !$is404) throw new \Exception("File could not be created: $finalPath");
		
		// FIXME: si on recalcule l'image à chaque fois, il n'y a aucun intérêt à avoir un cache!!!!
		if( $image_type == IMAGETYPE_JPEG ) {
			header('Content-Type: image/jpeg');
			imagejpeg($finalImage);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			header('Content-Type: image/gif');
			imagegif($finalImage);
		} elseif( $image_type == IMAGETYPE_PNG ) {
			header('Content-Type: image/png');
			imagepng($finalImage);
		}
		imagedestroy($finalImage);
	}
	
	/**
	 * Returns the URL of an image based on the file name.
	 * 
	 * @param string $path The filename, with directory, related to the $savePath declared in the configuration.
	 */
	public function getURL($path){
		return ROOT_URL. $this->getSavePath() . "/" . $path;
	}
	
	public function toHTML($path){
		echo "<img src='" . $this->getURL($path) . "'/>";
	}
	
	public function validateInstance(){
		$instanceName = MoufManager::getMoufManager()->findInstanceName($this);
		$htAccessPath = ROOT_PATH.$this->getSavePath().DIRECTORY_SEPARATOR.".htaccess";
		if (!file_exists($htAccessPath)){
			return new MoufValidatorResult(MoufValidatorResult::ERROR, "<b>Image Displayer: </b>Unable to find .htaccess file for instance: $instanceName <br/>" .
					"<a href='".MOUF_URL."staticimagedisplayer/?name=$instanceName' class='btn btn-primary'>Create .htaccess file</a>");
		}else{
			return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "<b>Image Displayer: </b>.htaccess file found for instance $instanceName.");
		}
	}
	
	/**
	 * Writes the .htaccess file.
	 */
	public function writeHtAccess() {
		$instanceName = MoufManager::getMoufManager()->findInstanceName($this);
		$savePath = $this->getSavePath();
		$htAccessPath = ROOT_PATH.$savePath."/.htaccess";
		
		// Let's count the number of '/' in the savePath.
		$nbLevels = substr_count($savePath, '/')+1;
				
		$str = "<IfModule mod_rewrite.c>
    RewriteEngine On

	# .htaccess RewriteBase related tips courtesy of Symfony 2's skeleton app.

    # Determine the RewriteBase automatically and set it as environment variable.
    # If you are using Apache aliases to do mass virtual hosting or installed the
    # project in a subdirectory, the base path will be prepended to allow proper
    # resolution of the base directory and to redirect to the correct URI. It will
    # work in environments without path prefix as well, providing a safe, one-size
    # fits all solution. But as you do not need it in this case, you can comment
    # the following 2 lines to eliminate the overhead.
    RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\\2$
    RewriteRule ^(.*) - [E=BASE:%1]
    

    # If the requested filename exists, and has an allowed extension, simply serve it.
    # We only want to let Apache serve files and not directories.
    RewriteCond %{REQUEST_FILENAME} !-f
    
    # Rewrite all other queries to the front controller.
    RewriteRule ^(.*)$ %{ENV:BASE}".str_repeat("/..", $nbLevels)."/vendor/mouf/utils.graphics.image-preset-displayer/src/direct/displayImage.php?instance=$instanceName&url=$1 [B,L]
</IfModule>

#<IfModule !mod_rewrite.c>
	# Use an error page as index file. It makes sure a proper error is displayed if
	# mod_rewrite is not available. Additionally, this reduces the matching process for the
	# start page (path \"/\") because otherwise Apache will apply the rewriting rules
	# to each configured DirectoryIndex file (e.g. index.php, index.html, index.pl).
#	DirectoryIndex vendor/mouf/mvc.splash/src/rewrite_missing.php
#</IfModule>";
		
		$savePath = ROOT_PATH . $this->savePath;
		if (!file_exists($savePath)){
			$oldUmask = umask();
			umask(0);
			mkdir($savePath, 0775, true);
			umask($oldUmask);
		}
		
		file_put_contents($htAccessPath, $str);
		chmod($htAccessPath, 0664);
	}
	
	/**
	 * Returns the save path in "clean form" (no slashes before or after).
	 */
	private function getSavePath() {
		return str_replace("\\", "/", trim($this->savePath, "/\\"));
	}
	
	public static function purgeAllPresets($path = null){
		$instances = MoufManager::getMoufManager()->findInstances("Mouf\\Utils\\Graphics\\ImagePresetDisplayer\\StaticImageDisplayer");
		foreach ($instances as $instanceName){
			/* @var $instance StaticImageDisplayer */
			$instance =  MoufManager::getMoufManager()->get($instanceName);
			unlink(ROOT_PATH . $instance->savePath . $path);
		}
	}
}