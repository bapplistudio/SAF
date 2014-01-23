<?php
namespace SAF\Framework;

use ReflectionClass;
use ReflectionMethod;

/** @noinspection PhpIncludeInspection */
require_once "framework/core/reflection/annotations/Annotation.php";
/** @noinspection PhpIncludeInspection */
require_once "framework/core/reflection/annotations/Annotation_Parser.php";
/** @noinspection PhpIncludeInspection */
require_once "framework/core/reflection/annotations/Annoted.php";
/** @noinspection PhpIncludeInspection */
require_once "framework/core/reflection/Has_Doc_Comment.php";
/** @noinspection PhpIncludeInspection */
require_once "framework/core/reflection/Reflection_Class.php";

/**
 * A rich extension of the PHP ReflectionMethod class, adding :
 * - annotations management
 */
class Reflection_Method extends ReflectionMethod implements Has_Doc_Comment
{
	use Annoted;

	//------------------------------------------------------------------------------------------- ALL
	/**
	 * Another constant for default Reflection_Class::getMethods() filter
	 *
	 * @var integer
	 */
	const ALL = 1799;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class_name  string
	 * @param $method_name string
	 */
	public function __construct($class_name, $method_name)
	{
		if (!(is_string($class_name) && is_string($method_name))) {
			trigger_error(__CLASS__ . " constructor needs strings", E_USER_ERROR);
		}
		parent::__construct($class_name, $method_name);
	}

	//--------------------------------------------------------------------------------- getDocComment
	/**
	 * @param $parent boolean
	 * @return string
	 */
	public function getDocComment($parent = false)
	{
		// TODO parent methods read
		return parent::getDocComment();
	}

}
