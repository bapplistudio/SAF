<?php
namespace SAF\Framework;

/**
 * Image tools class
 */
class Image
{

	//------------------------------------------------------------------------------------- $resource
	/**
	 * @var resource
	 */
	public $resource;

	//----------------------------------------------------------------------------------------- $type
	/**
	 * The image type : one of the IMAGETYPE_XXX constants
	 *
	 * @var integer
	 */
	public $type;

	//---------------------------------------------------------------------------------------- $width
	/**
	 * @var integer
	 */
	private $width;

	//--------------------------------------------------------------------------------------- $height
	/**
	 * @var integer
	 */
	private $height;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Constructs an image
	 *
	 * @param $width    integer the image width (mandatory)
	 * @param $height   integer the image height (mandatory)
	 * @param $resource resource the image resource. If not set, an empty image will be created
	 * @param $type     integer one of the IMAGETYPE_XXX constants, for automatic save
	 */
	public function __construct($width = null, $height = null, $resource = null, $type = null)
	{
		if (isset($width))  $this->width  = $width;
		if (isset($height)) $this->height = $height;
		if (isset($type))   $this->type   = $type;
		$this->resource = isset($resource)
			? $resource
			: imagecreatetruecolor($this->width, $this->height);
	}

	//------------------------------------------------------------------------------ createFromString
	/**
	 * @param $image string
	 * @return Image
	 */
	public static function createFromString($image)
	{
		$size = getimagesizefromstring($image);
		return new Image($size[0], $size[1], imagecreatefromstring($image), $size[2]);
	}

	//---------------------------------------------------------------------------------------- resize
	/**
	 * Gets a resized version of the image
	 *
	 * @param $width      integer the width of the new image. null for automatic
	 * @param $height     integer the height of the new image. null for automatic
	 * @param $keep_ratio boolean keep image ratio (margins are added if image ratio changes)
	 * @return Image
	 */
	public function resize($width = null, $height = null, $keep_ratio = true)
	{
		$source_ratio = round($this->width / $this->height, 6);
		if (is_null($width) && is_numeric($height)) {
			$width = round($this->height / $this->width * $height);
		}
		elseif (is_null($height) && is_numeric($width)) {
			$height = round($source_ratio * $width);
		}
		elseif (is_null($width) && is_null($height)) {
			$width = $height = 140;
		}
		$destination_ratio = round($width / $height, 6);
		$dx = $dy = 0;
		$dw = $width; $dh = $height;
		if ($keep_ratio) {
			// source is wider than destination : top and bottom margins
			if ($destination_ratio < $source_ratio) {
				$dh = $source_ratio * $width;
				$dy = ceil(($height - $dh) / 2);
			}
			// destination is wider than source : left and right margins
			elseif ($destination_ratio > $source_ratio) {
				$dw = $this->height / $this->width * $height;
				$dx = ceil(($width - $dw) / 2);
			}
		}
		$destination = new Image($width, $height, null, $this->type);
		imagecopyresampled(
			$destination->resource, $this->resource, $dx, $dy, 0, 0, $dw, $dh, $this->width, $this->height
		);
		return $destination;
	}

	//------------------------------------------------------------------------------------------ save
	/**
	 * @param $filename string
	 * @param $type     integer Image type is one of the IMAGETYPE_XXX image types, or current if null
	 * @param $quality  integer Image quality (percent)
	 * @return Image
	 */
	public function save($filename, $type = null, $quality = null)
	{
		if (!isset($type))    $type = $this->type;
		if (!isset($quality)) $quality = 80;
		switch ($type) {
			case IMAGETYPE_BMP: image2wbmp($this->resource, $filename); break;
			case IMAGETYPE_GIF: imagegif($this->resource, $filename); break;
			case IMAGETYPE_PNG: imagepng($this->resource, $filename, $quality); break;
			default: imagejpeg($this->resource, $filename, $quality); break;
		}
		return $this;
	}

	//------------------------------------------------------------------------- stringToThumbnailFile
	/**
	 * Transforms an image (binary data) into a thumbnail image file
	 *
	 * @param $image          string binary data of the original image
	 * @param $thumbnail_file string the thumbnail image file name
	 * @param $width          integer the thumbnail image file width. null for automatic
	 * @param $height         integer the thumbnail image file height. null for automatic
	 * @param $type           integer IMAGETYPE_XXX image type constant
	 * @param $quality        integer
	 * @return Image
	 */
	public static function stringToThumbnailFile(
		$image, $thumbnail_file, $width = null, $height = null, $type = null, $quality = null
	) {
		return self::createFromString($image)->resize(
			$width, $height)->save($thumbnail_file, $type, $quality
		);
	}

}