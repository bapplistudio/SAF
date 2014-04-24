<?php
namespace SAF\Framework\Reflection;

use Exception;
use ReflectionProperty;
use SAF\Framework\Reflection\Annotation\Annoted;
use SAF\Framework\Reflection\Annotation\Class_\Override_Annotation;
use SAF\Framework\Tools\Date_Time;
use SAF\Framework\Tools\Field;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Namespaces;

/**
 * A rich extension of the PHP ReflectionProperty class
 */
class Reflection_Property extends ReflectionProperty implements Field, Has_Doc_Comment
{
	use Annoted;

	//------------------------------------------------------------------------------------------- ALL
	/**
	 * Another constant for default Reflection_Class::getProperties() filter
	 *
	 * @var integer
	 */
	const ALL = 1793;

	//---------------------------------------------------------------------------------- $doc_comment
	/**
	 * Cached value for the doc comment (set by getDocComment() only when $use is true)
	 *
	 * @var string
	 */
	private $doc_comment;

	//---------------------------------------------------------------------------------- $final_class
	/**
	 * Final class asked when calling getInstanceOf().
	 * It may not be the class where the property is declared, but the class which was asked.
	 *
	 * @var string
	 */
	public $final_class;

	//---------------------------------------------------------------------------- $override_property
	/**
	 * Only if the property is declared into a parent class as well as into the child class.
	 * If not, this will be false.
	 *
	 * @var Reflection_Property|boolean
	 */
	private $overridden_property;

	//----------------------------------------------------------------------------------------- $path
	/**
	 * Full path of the property, if built with getInstanceOf() and a $property.path
	 *
	 * @var string
	 */
	public $path;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class_name    string
	 * @param $property_name string
	 */
	public function __construct($class_name, $property_name)
	{
		$this->path = $property_name;
		$i = 0;
		while (($j = strpos($property_name, DOT, $i)) !== false) {
			$property = new Reflection_Property($class_name, substr($property_name, $i, $j - $i));
			$class_name = $property->getType()->getElementTypeAsString();
			$i = $j + 1;
		}
		if ($i) {
			$property_name = substr($property_name, $i);
		}
		$this->final_class = $class_name;
		parent::__construct($class_name, $property_name);
	}
	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string The name of the property
	 */
	public function __toString()
	{
		return $this->name;
	}

	//---------------------------------------------------------------------------------------- exists
	/**
	 * @param $class_name    string a class name
	 * @param $property_name string a property name or a property path starting from the class
	 * @return boolean true if the property exists
	 */
	public static function exists($class_name, $property_name)
	{
		if (strpos($property_name, DOT) !== false) {
			$properties_name = explode(DOT, $property_name);
			foreach (array_slice($properties_name, 0, -1) as $property_name) {
				if (!property_exists($class_name, $property_name)) {
					return false;
				}
				$property = new Reflection_Property($class_name, $property_name);
				$class_name = $property->getType()->getElementTypeAsString();
			}
			$property_name = end($properties_name);
		}
		return property_exists($class_name, $property_name);
	}

	//------------------------------------------------------------------------ getAnnotationCachePath
	/**
	 * @return string[]
	 */
	protected function getAnnotationCachePath()
	{
		return [$this->final_class, $this->name];
	}

	//----------------------------------------------------------------------------- getDeclaringClass
	/**
	 * Gets the declaring class for the reflected property
	 *
	 * @return Reflection_Class
	 */
	public function getDeclaringClass()
	{
		return new Reflection_Class(parent::getDeclaringClass()->name);
	}

	//----------------------------------------------------------------------------- getDeclaringTrait
	/**
	 * Gets the real declaring trait (or class if declared in class) of a property
	 *
	 * @return Reflection_Class
	 */
	public function getDeclaringTrait()
	{
		foreach ($this->getDeclaringClass()->getTraits() as $trait) {
			$properties = $trait->getProperties();
			if (isset($properties[$this->name])) {
				$property = $properties[$this->name];
				$declaring_trait = $property->getDeclaringTrait();
				return isset($declaring_trait) ? $declaring_trait : $property->getDeclaringClass();
			}
		}
		return $this->getDeclaringClass();
	}

	//------------------------------------------------------------------------------- getDefaultValue
	/**
	 * Gets the default value for the property
	 *
	 * This is not optimized and could be slower than getting the class's default values one time
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return $this->getDeclaringClass()->getDefaultProperties()[$this->name];
	}

	//--------------------------------------------------------------------------------- getDocComment
	/**
	 * @param $get_use boolean
	 * @return string
	 */
	public function getDocComment($get_use = true)
	{
		if (!isset($this->doc_comment)) {
			$overridden_property = $this->getOverriddenProperty();
			$this->doc_comment =
				$this->getOverrideDocComment()
				. parent::getDocComment()
				. ((isset($overridden_property)) ? $overridden_property->getDocComment() : '');
		}
		return $this->doc_comment;
	}

	//--------------------------------------------------------------------------------- getFinalClass
	/**
	 * @return Reflection_Class
	 */
	public function getFinalClass()
	{
		return new Reflection_Class($this->final_class);
	}

	//------------------------------------------------------------------------- getOverrideDocComment
	/**
	 * Gets the class @override property doc comment that overrides the original property doc comment
	 *
	 * @return Override_Annotation[]
	 */
	private function getOverrideDocComment()
	{
		$comment = '';
		foreach (
			(new Reflection_Class($this->final_class))->getListAnnotations('override') as $annotation
		) {
			/** @var $annotation Override_Annotation */
			if ($annotation->property_name === $this->name) {
				$comment .= '/**' . LF;
				foreach ($annotation->values() as $key => $value) {
					if (in_array($key, ['var'])) {
						$value = explode(SP, $value);
						$value[0] = Namespaces::defaultFullClassName($value[0], $annotation->class->name);
						$value = join(SP, $value);
					}
					$comment .= TAB . SP . '*' . SP . '@' . $key . SP . $value . LF;
				}
				$comment .= TAB . SP . '*/';
			}
		}
		return $comment;
	}

	//------------------------------------------------------------------------- getOverriddenProperty
	/**
	 * Gets the parent property overridden by the current one from the parent class
	 *
	 * @return Reflection_Property
	 */
	public function getOverriddenProperty()
	{
		if (!isset($this->overridden_property)) {
			$parent = $this->getDeclaringClass()->getParentClass();
			$this->overridden_property = $parent ? ($parent->getProperty($this->name) ?: false) : false;
		}
		return $this->overridden_property ?: null;
	}

	//----------------------------------------------------------------------------- getParentProperty
	/**
	 * Gets the parent property for a $property.path
	 *
	 * @return Reflection_Property|null
	 */
	public function getParentProperty()
	{
		if (!empty($this->path) && ($i = strrpos($this->path, DOT))) {
			return new Reflection_Property($this->class, substr($this->path, 0, $i));
		}
		return null;
	}

	//--------------------------------------------------------------------------------------- getType
	/**
	 * @return Type
	 * @throws Exception
	 */
	public function getType()
	{
		$type = new Type($this->getAnnotation('var')->value);
		// automatically add current class namespace
		if ($type->isClass()) {
			$element_class_name = $type->getElementTypeAsString();
			if (Namespaces::isShortClassName($element_class_name)) {
				$declaring_trait = $this->getDeclaringTrait()->name;
				$class_name = Namespaces::defaultFullClassName($element_class_name, $declaring_trait);
				$type = $type->isMultiple()
					? (new Type($class_name, $type->canBeNull()))->multiple()
					: new Type($class_name, $type->canBeNull());
			}
		}
		if ($type->isNull()) {
			throw new Exception(
				$this->class . '::$' . $this->name . ' type not set using @var annotation',
				E_USER_ERROR
			);
		}
		return $type;
	}

	//------------------------------------------------------------------------- isValueEmptyOrDefault
	/**
	 * Returns true if property is empty or equals to the default value
	 *
	 * Date_Time properties are null if '0000-00-00' or empty date
	 *
	 * @param $value mixed
	 * @return boolean
	 */
	public function isValueEmptyOrDefault($value)
	{
		return empty($value)
			|| ($value === $this->getDefaultValue())
			|| (($value === '0000-00-00') && $this->getType()->isDateTime())
			|| (($value instanceof Date_Time) && $value->isEmpty());
	}

	//----------------------------------------------------------------------------------- pathAsField
	/**
	 * Returns path formatted as field : uses [] instead of .
	 *
	 * @example if $this->path is 'a.field.path', will return 'a[field][path]'
	 * @return string
	 */
	public function pathAsField()
	{
		return Names::propertyPathToField($this->path ? $this->path : $this->name);
	}

}