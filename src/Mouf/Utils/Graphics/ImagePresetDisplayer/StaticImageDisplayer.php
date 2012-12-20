<?php
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
 * @Component
 * 
 * @author Kevin
 *
 */
class StaticImageDisplayer{
	
	/**
	 * The name of the MoufImageFromFile instance that will be the first to load the image from the given input $sourceFileName
	 * @Property
	 * @Compulsory
	 * @var MoufImageFromFile $initialImageFilter
	 */
	public $initialImageFilter;
	
	/**
	 * The MoufImage instance's name that delivers the final image resource, with all applied effects
	 * @Property
	 * @Compulsory
	 * @var MoufImageInterface $imageSource
	 */
	public $imageSource;
	
	/**
	 * The path into which the image file will be saved if it doesn't exist.
	 * This path is relative to the applcation's ROOT_PATH, and should have trailing slashes.
	 * @Property
	 * @Compulsory
	 * @var string $savePath
	 */
	public $savePath;
	
	/**
	 * The path to the original image that will be loaded (by the $initialImageFilter, transformed by a set of MoufImage instances.
	 * This path is relative to the application's ROOT_PATH, and should have trailing slashes.
	 * @Property
	 * @Compulsory
	 * @var string $basePath
	 */
	public $basePath;
	
	
	/**
	 * The path to the original image file, relative to the $basePath.
	 * The file name should not contain any '..' strings (for security reasons, the component dosn't allow users to access files outside the $basePath),
	 * but it may contain folders (ex: sub_folder/image.jpeg).
	 * @var string $sourceFileName
	 */
	public $sourceFileName;
	
	/**
	 * The Quality that should be applied in case the original image is of JPEG type (0 to 100).
	 * @Property
	 * @var int $jpegQuality
	 */
	public $jpegQuality = 75;
	
	/**
	 * The Quality that should be applied in case the original image is of PNG type (0 to 9).
	 * @Property
	 * @var int $pngQuality
	 */
	public $pngQuality = 6;
	
	/**
	 * The path to the default image if not found
	 * @Property
	 * @var int $pngQuality
	 */
	public $defaultImagePath;
	
	/**
	 * Output the image: 
	 *   - original image is loaded by the $initialImageFilter, 
	 *   - final image (given by the $imageSource) is outputed (and saved if it doesn't exist yet)
	 * @throws Exception
	 */
	public function outputImage(){
		//Prevent from acessing parent folders
		if (strpos($this->sourceFileName, '..')) throw new Exception("Trying to access file in parent folders : '$sourceFileName'");
		
		//rebuild the original file pathe from the root image folder and the relative file's pathe
		$originalFilePath = ROOT_PATH . $this->basePath . DIRECTORY_SEPARATOR . $this->sourceFileName;
// 		echo "$originalFilePath exits? ".(file_exists($originalFilePath) ? "ok" : "ko");exit;
		$is404 = false;
		if (!file_exists($originalFilePath)){
			//error_log("file not exists : $originalFilePath");
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
		$finalPath = ROOT_PATH . $this->savePath . DIRECTORY_SEPARATOR . $this->sourceFileName;
		
		
		$created = true;
		if (!file_exists($finalPath) && !$is404){
			//if sourceFileName contains sub folders, create them
			$subPath = dirname($this->sourceFileName);
			if ($subPath != '.' && !file_exists(ROOT_PATH . $this->savePath . DIRECTORY_SEPARATOR . $subPath)){
				$dirCreate = mkdir(ROOT_PATH . $this->savePath . DIRECTORY_SEPARATOR . $subPath, 0777, true);
				if (!$dirCreate) throw new Exception("Could't create subfolders '$subPath' in " . ROOT_PATH . $this->savePath);
			}
			
			//create the image
			if( $image_type == IMAGETYPE_JPEG ) {
				$created = imagejpeg($finalImage, $finalPath, $this->jpegQuality);
			} elseif( $image_type == IMAGETYPE_GIF ) {
				$created = imagegif($finalImage, $finalPath);
			} elseif( $image_type == IMAGETYPE_PNG ) {
				$created = imagepng($finalImage, $finalPath, $this->pngQuality);
			}
		}
		
		if (!$created && !$is404) throw new Exception("File could not be created: $finalPath");
		
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
		return ROOT_URL. $this->savePath . "/" . $path;
	}
	
	public function toHTML($path){
		echo "<img src='" . $this->getURL($path) . "'/>";
	}
}