<?php
namespace SAF\Framework\Dao;

use SAF\Framework\Application;
use SAF\Framework\Builder;
use SAF\Framework\Dao\File\Spreadsheet_File;
use SAF\Framework\Dao\File\Type;
use SAF\Framework\Dao\File\Type_Builder;
use SAF\Framework\Tools\Date_Time;
use SAF\Framework\Tools\Files;
use SAF\Framework\Tools\Paths;
use SAF\Framework\Traits\Has_Name;

/**
 * File is a simple business object that stores files
 *
 * @before_write getContent
 */
class File
{
	use Has_Name;

	//-------------------------------------------------------------------------------------- $content
	/**
	 * @binary
	 * @getter getContent
	 * @max_length 4000000000
	 * @setter setContent
	 * @var string
	 */
	public $content;

	//----------------------------------------------------------------------------------------- $hash
	/**
	 * @getter getHash
	 * @var string
	 */
	public $hash;

	//-------------------------------------------------------------------------- $temporary_file_name
	/**
	 * Temporary file name where the file is stored, used to get content into $content only if needed
	 *
	 * @getter getTemporaryFileName
	 * @var string
	 */
	public $temporary_file_name;

	//----------------------------------------------------------------------------------- $updated_on
	/**
	 * @link DateTime
	 * @mandatory
	 * @var Date_Time
	 */
	public $updated_on;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $temporary_file_name string
	 */
	public function __construct($temporary_file_name = null)
	{
		if (isset($temporary_file_name)) {
			if (!isset($this->name)) {
				$this->name = rLastParse($temporary_file_name, SL, 1, true);
			}
			$this->temporary_file_name = $temporary_file_name;
		}
		if (!isset($this->updated_on)) {
			$this->updated_on = Builder::create(Date_Time::class);
		}
	}

	//-------------------------------------------------------------------------------------- calcHash
	/**
	 * Calculate hash code
	 */
	protected function calcHash()
	{
		$this->hash = md5($this->content);
	}

	//--------------------------------------------------------------------------------- getCsvContent
	/**
	 * @param $errors string[]
	 * @return array Two dimensional array (keys are row, column)
	 */
	public function getCsvContent(&$errors = [])
	{
		return Spreadsheet_File::readCsvFile($this->temporary_file_name, $errors);
	}

	//------------------------------------------------------------------------------------ getContent
	/**
	 * Gets $this->content, or load it from temporary file name if not set
	 *
	 * @return string
	 */
	public function getContent()
	{
		if (isset($this->temporary_file_name) && !isset($this->content)) {
			$this->content = file_get_contents($this->temporary_file_name);
		}
		return $this->content;
	}

	//--------------------------------------------------------------------------------------- getHash
	/** @noinspection PhpUnusedPrivateMethodInspection @getter */
	/**
	 * Gets $hash, or calculate it from content if not set
	 *
	 * @return string
	 */
	private function getHash()
	{
		if (!isset($this->hash)) {
			$this->calcHash();
		}
		return $this->hash;
	}

	//-------------------------------------------------------------------------- getTemporaryFileName
	/** @noinspection PhpUnusedPrivateMethodInspection @getter */
	/**
	 * Gets temporary file name, or write content into a temporary file name and get this name if not
	 * set or file does not exist
	 *
	 * @return string
	 */
	private function getTemporaryFileName()
	{
		if (
			isset($this->content)
			&& (empty($this->temporary_file_name) || !file_exists($this->temporary_file_name))
		) {
			if (empty($this->temporary_file_name)) {
				$this->temporary_file_name = Application::current()->getTemporaryFilesPath() . SL
					. uniqid() . '_' . $this->name;
			}
			if (strpos($this->temporary_file_name, SL) !== false) {
				Files::mkdir(lLastParse($this->temporary_file_name, SL));
			}
			file_put_contents($this->temporary_file_name, $this->content);
		}
		return $this->temporary_file_name;
	}

	//--------------------------------------------------------------------------- getTemporaryFileUri
	/**
	 * Gets the temporary file URI, relative to the document root
	 *
	 * @return string
	 */
	public function getTemporaryFileUri()
	{
		return Paths::$project_uri . SL . $this->temporary_file_name;
	}

	//--------------------------------------------------------------------------------------- getType
	/**
	 * @return Type
	 */
	public function getType()
	{
		return Type_Builder::build($this->name);
	}

	//------------------------------------------------------------------------------------ setContent
	/** @noinspection PhpUnusedPrivateMethodInspection @setter */
	/**
	 * @param $content string
	 */
	private function setContent($content)
	{
		$this->content = $content;
		$this->calcHash();
		$this->updated_on = new Date_Time();
	}

}
