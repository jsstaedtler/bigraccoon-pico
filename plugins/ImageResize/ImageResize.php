<?php
/**
 * Pico ImageResize plugin - extends Twig with image resizing capabilities
 *
 * @author  AngryUbuntuNerd
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 2.0
 */
class ImageResize extends AbstractPicoPlugin
{
    /**
     * API version used by this plugin
     *
     * @var int
     */
    const API_VERSION = 2;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var int
     */
    private $quality;

    /**
     * @var boolean
     */
    private $useImagick = false;

    /**
     * Triggered after Pico has read its configuration
     *
     * @see Pico::getConfig()
     * @see Pico::getBaseUrl()
     * @see Pico::getBaseThemeUrl()
     * @see Pico::isUrlRewritingEnabled()
     *
     * @param array &$config array of config variables
     *
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
        $this->folder = '.resized';
        if (isset($config['ImageResize']['folder']))
            $this->folder = $config['ImageResize']['folder'];

        $this->quality = 85;
        if (isset($config['ImageResize']['quality']))
            $this->quality = $config['ImageResize']['quality'];
    }

    /**
     * Triggered when Pico registers the twig template engine
     *
     * @see Pico::getTwig()
     *
     * @param Twig_Environment &$twig Twig instance
     *
     * @return void
     */
    public function onTwigRegistered(Twig_Environment &$twig)
    {
        if (extension_loaded('imagick'))
            $this->useImagick = true;
        elseif (!extension_loaded('gd'))
            exit('PHP extension "imagick" or "gd" is not installed, or not enabled in php.ini');

        $twig->addFunction(new Twig_SimpleFunction('resize', array($this, 'resize')));
    }

    /**
     * Resize an image, optionally apply a pixellate filter, then save it to a temporary folder and return new filename
     * @param string $file
     * @param int $width
     * @param int $height
	 * @param int $pixels
     * @return string
     */
    public function resize($file, $width = null, $height = null, $pixels = null)
    {
        if (is_null($width) && is_null($height) && is_null($pixels)) {
            error_log(new InvalidArgumentException("Width and height can't both be null when not applying pixellation"));
            return;
        }
		
		if (!file_exists($file)) {
			error_log(new InvalidArgumentException("File not found: " . $file));
			return;
		}

        // determine resized filename
		$pxstr = is_null($pixels) ? '' : ('p' . $pixels);
        $newFile = sprintf('%s/%s/%s-%dx%d%s.jpg',
            dirname($file),
            $this->folder,
            pathinfo($file, PATHINFO_FILENAME),
            $width,
            $height,
			$pxstr
        );

        // if we have already resized, just return the existing file
        if (file_exists($newFile))
            return $newFile;

        // load file dimensions
        $dimensions = getimagesize($file);
        $originalWidth = $dimensions[0];
        $originalHeight = $dimensions[1];

        // calculate the final width and height (keeping aspect ratio)
        $widthRatio = $width ? $originalWidth/$width : 0;			// If >1, the image must shrink to the desired size along that dimension
        $heightRatio = $height ? $originalHeight/$height : 0;		// If the dimension wasn't specified, no need to shrink it
        if ($widthRatio < 1 && $heightRatio < 1) {		// Both original dimensions are already within the requested dimensions
            $resizedWidth = $originalWidth;
            $resizedHeight = $originalHeight;
        } else if ($widthRatio > $heightRatio) {		// This image's width must shrink to fit
            $resizedWidth = $width;
            $resizedHeight = round($originalHeight / $widthRatio);
        } else {										// This image's height must shrink to fit
            $resizedWidth = round($originalWidth / $heightRatio);
            $resizedHeight = $height;
        }

        // make sure folder exists
        if (!file_exists(pathinfo($newFile, PATHINFO_DIRNAME)))
            mkdir(pathinfo($newFile, PATHINFO_DIRNAME));

        // resize and save
        if ($this->useImagick) {
            $image = new Imagick($file);
            $image->setImageCompressionQuality($this->quality);
            $image->thumbnailImage($resizedWidth, $resizedHeight);
            $image->writeImage($newFile);
        } else {
            $image = imagecreatefromstring(file_get_contents($file));

			if (!(is_null($width) && is_null($height))) {
				$newResource = imagecreatetruecolor($resizedWidth, $resizedHeight);
				
				// Since we save to JPG, any transparency will be lost, so we're going to blend it against a white background
				imagealphablending($newResource, true);		// Any alpha value in the original will be blended with the background
				imagesavealpha($newResource, false);		// Don't preserve the alpha channel
				if (function_exists('imageantialias'))
				{
				  imageantialias($newResource, true);
				}
				$white = imagecolorallocate($newResource, 255, 255, 255);
				imagefill($newResource, 0, 0, $white);		// The new image will start off completely white

				// This function provides better resulting image quality than imagescale()
				imagecopyresampled($newResource, $image, 0, 0, 0, 0, $resizedWidth, $resizedHeight, $originalWidth, $originalHeight);
				imagedestroy($image);
				$image = $newResource;
			}
			
			if (!is_null($pixels)) {
				imagefilter($image, IMG_FILTER_PIXELATE, $pixels, true);	// true means to use a newer pixellation algorithm
			}
			
            imagejpeg($image, $newFile, $this->quality);
			imagedestroy($image);
        }

        return $newFile;
    }
}
