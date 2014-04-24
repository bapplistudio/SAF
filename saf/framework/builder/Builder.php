<?php
namespace SAF\Framework;

use ReflectionClass;
use SAF\Framework\Builder\Class_Builder;
use SAF\Framework\Mapper\Getter;
use SAF\Framework\Mapper\Search_Object;
use SAF\Framework\Plugin\Activable;
use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Reflection\Type;
use SAF\Framework\Sql\Join\Joins;
use SAF\Framework\Tools\Current_With_Default;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Namespaces;
use SAF\Framework\Tools\Set;
use Serializable;

/**
 * The Builder plugin replaces 'new Class_Name' calls by 'Builder::create('Class_Name')' in order to
 * enable objects substitution
 *
 * You should always use these calls for SAF business objects instantiations.
 *
 * @todo remove dependencies
 */
class Builder implements Activable, Registerable, Serializable
{
	use Current_With_Default { current as private dCurrent; }

	//--------------------------------------------------------------------------------- $compositions
	/**
	 * Backup of the replacement compositions for built composed classes
	 * Once a class replaced by a string[] of interfaces and traits is compiled, its replacement
	 * structure is stored into compositions for hot recompiling on demand.
	 *
	 * @var array[]
	 */
	private $compositions = [];

	//--------------------------------------------------------------------------------- $replacements
	/**
	 * The key is an original class name to replace by a replacement class
	 * If the value is a string, this is the replacement class name
	 * If the value is a string[], this is the list of interfaces and traits to use to build a
	 * replacement class.
	 *
	 * The first time it is used, the replacement class is built and the value is replaced by the
	 * built class name.
	 *
	 * @var array[]|string[]
	 */
	private $replacements = [];

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $replacements string[]|array[] key is parent class name associated to replacement class
	 *        values can be a class name or a string[] of interfaces and traits to add to the class
	 */
	public function __construct($replacements = null)
	{
		if (isset($replacements)) {
			$this->replacements = $replacements;
		}
	}

	//-------------------------------------------------------------------------------------- activate
	public function activate()
	{
		self::current($this);
	}

	//------------------------------------------------------------------ afterNamespacesFullClassName
	/**
	 * @param $short_class_name string
	 * @param $result           string
	 * @return string
	 */
	public static function afterNamespacesFullClassName($short_class_name, $result)
	{
		return (Namespaces::isShortClassName($short_class_name))
			? Builder::current()->replacementClassName($result)
			: $result;
	}

	//------------------------------------------------------------------------------------- className
	/**
	 * @param $class_name string
	 * @return string
	 */
	public static function className($class_name)
	{
		return self::current()->replacementClassName($class_name);
	}

	//---------------------------------------------------------------------------------------- create
	/**
	 * @param $class_name string
	 * @param $args       mixed[]|mixed some arguments into an array, or a single non-array argument
	 * @return object
	 */
	public static function create($class_name, $args = null)
	{
		return isset($args)
			? self::current()->newInstanceArgs($class_name, is_array($args) ? $args : [$args])
			: self::current()->newInstance($class_name);
	}

	//----------------------------------------------------------------------------------- createClone
	/**
	 * Create a clone of the object, using a built class if needed
	 *
	 * @param $object     object
	 * @param $class_name string if set, the new object will use the matching built class
	 *        this class name must inherit from the object's class
	 * @param $properties_values array some properties values for the cloned object
	 * @return object
	 */
	public static function createClone($object, $class_name = null, $properties_values = [])
	{
		$source_class_name = get_class($object);
		if (!isset($class_name)) {
			$class_name = self::className($object);
		}
		if ($class_name !== $source_class_name) {
			// initialises cloned object
			$clone = self::create($class_name);
			// deactivate AOP
			if (isset($clone->_)) {
				$save_aop = $clone->_;
				unset($clone->_);
			}
			// copy official properties values from the source object
			$properties = (new Reflection_Class($source_class_name))->accessProperties();
			foreach ($properties as $property) {
				if (!isset($save_aop[$property->name])) {
					$property->setValue($clone, $property->getValue($object));
				}
			}
			// copy unofficial properties values from the source object (ie AOP properties aliases)
			foreach (get_object_vars($object) as $property_name => $value) {
				if (!isset($properties[$property_name])) {
					$object->$property_name = $value;
				}
			}
			// reactivate AOP
			if (isset($save_aop)) {
				$clone->_ = $save_aop;
			}
			// copy added properties values to the cloned object
			if ($properties_values) {
				$properties = (new Reflection_Class($class_name))->accessProperties();
				foreach ($properties_values as $property_name => $value) {
					$properties[$property_name]->setValue($clone, $value);
				}
			}
			// disconnect the clone from datalink if the clone class is a @link of the source object
			if (
				(new Reflection_Class($class_name))->getAnnotation('link')->value
				!= (new Reflection_Class($source_class_name))->getAnnotation('link')->value
			) {
				Dao::disconnect($clone);
			}
			return $clone;
		}
		return clone $object;
	}

	//--------------------------------------------------------------------------------------- current
	/**
	 * @param $set_current Builder
	 * @return Builder
	 */
	public static function current(Builder $set_current = null)
	{
		return self::dCurrent($set_current);
	}

	//------------------------------------------------------------------------------------- fromArray
	/**
	 * Changes an array into an object
	 *
	 * You should set only public and non-static properties values
	 *
	 * @param $class_name string
	 * @param $array      array
	 * @return object
	 */
	public static function fromArray($class_name, $array)
	{
		$object = self::create($class_name);
		if (isset($array)) {
			foreach ($array as $property_name => $value) {
				if (is_array($value)) {
					$property = new Reflection_Property($class_name, $property_name);
					if ($property->getType()->isClass()) {
						$property_class_name = $property->getType()->getElementTypeAsString();
						if ($property->getType()->isMultiple()) {
							foreach ($value as $key => $val) {
								$value[$key] = self::fromArray($property_class_name, $val);
							}
						}
						else {
							$value = self::fromArray($property_class_name, $value);
						}
						$property->setValue($object, $value);
					}
				}
				$object->$property_name = $value;
			}
		}
		return $object;
	}

	//-------------------------------------------------------------------------------- getComposition
	/**
	 * Gets original replacement composition of the class name
	 *
	 * @param $class_name string
	 * @return string|string[]
	 */
	public function getComposition($class_name)
	{
		return isset($this->compositions[$class_name]) ? $this->compositions[$class_name] : (
			isset($this->replacements[$class_name]) ? $this->replacements[$class_name] : $class_name
		);
	}

	//------------------------------------------------------------------------------- getCompositions
	/**
	 * Gets all original replacements compositions
	 *
	 * @return array[]|string[]
	 */
	public function getCompositions()
	{
		return array_merge($this->replacements, $this->compositions);
	}

	//----------------------------------------------------------------------------------- isObjectSet
	/**
	 * Returns true if any property of $object is set and different than its default value
	 *
	 * @param $object
	 * @return boolean
	 */
	public static function isObjectSet($object)
	{
		$result = false;
		$class = new Reflection_Class(get_class($object));
		$defaults = $class->getDefaultProperties();
		foreach ($class->accessProperties() as $property) if (!$property->isStatic()) {
			$value = $property->getValue($object);
			if (isset($value)) {
				$default = isset($defaults[$property->name])
					? $defaults[$property->name]
					: ($property->getType()->getDefaultValue());
				if (is_object($value) && !self::isObjectSet($value)) {
					$value = null;
				}
				if ($value != $default) {
					$result = true;
					break;
				}
			}
		}
		return $result;
	}

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * Return a new instance of given $class_name, using replacement class if exist
	 *
	 * @param $class_name string may be short or full class name
	 * @return object
	 */
	public function newInstance($class_name)
	{
		$class_name = $this->replacementClassName($class_name);
		return new $class_name();
	}

	//------------------------------------------------------------------------------- newInstanceArgs
	/**
	 * Return a new instance of given $class_name, using replacement class if exist
	 *
	 * @param $class_name string may be short or full class name
	 * @param $args       array
	 * @return object
	 */
	public function newInstanceArgs($class_name, $args)
	{
		$class_name = $this->replacementClassName($class_name);
		return (new ReflectionClass($class_name))->newInstanceArgs($args);
	}

	//------------------------------------------------------------------------- onMethodWithClassName
	/**
	 * @param $class_name string
	 */
	public function onMethodWithClassName(&$class_name)
	{
		$class_name = $this->replacementClassName($class_name);
	}

	//------------------------------------------------------------------------- onMethodReturnedValue
	/**
	 * @param $result string
	 * @return string
	 */
	public function onMethodReturnedValue($result)
	{
		return $this->replacementClassName($result);
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$aop = $register->aop;
		$aop->beforeMethod([Getter::class, 'getCollection'],        [$this, 'onMethodWithClassName']);
		$aop->beforeMethod([Getter::class, 'getObject'],            [$this, 'onMethodWithClassName']);
		$aop->afterMethod( [Namespaces::class, 'fullClassName'],    [$this, 'afterNamespacesFullClassName']);
		$aop->beforeMethod([Search_Object::class, 'create'],        [$this, 'onMethodWithClassName']);
		$aop->afterMethod( [Set::class, 'elementClassNameOf'],      [$this, 'onMethodReturnedValue']);
		$aop->afterMethod( [Joins::class, 'addSimpleJoin'],         [$this, 'onMethodReturnedValue']);
		$aop->afterMethod( [Type::class, 'getElementTypeAsString'], [$this, 'onMethodReturnedValue']);
	}

	//-------------------------------------------------------------------------- replacementClassName
	/**
	 * Gets replacement class name for a parent class name or a list of traits to implement
	 *
	 * @param $class_name string can be short or full class name
	 * @return string
	 */
	private function replacementClassName($class_name)
	{
		$result = isset($this->replacements[$class_name])
			? $this->replacements[$class_name]
			: $class_name;
		if (is_array($result)) {
			$this->compositions[$class_name] = $result;
			$built_class_name = Class_Builder::builtClassName($class_name);
			if (file_exists(
				Application::current()->getCacheDir() . '/compiled/'
				. str_replace('/', '-', Names::classToPath($built_class_name))
			)) {
				$result = $built_class_name;
			}
			else {
				$result = Class_Builder::build($class_name, $result);
			}
			$this->replacements[$class_name] = $result;
		}
		return $result;
	}

	//------------------------------------------------------------------------------------- serialize
	/**
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		return serialize([$this->compositions, $this->replacements]);
	}

	//-------------------------------------------------------------------------------- setReplacement
	/**
	 * Sets a new replacement
	 *
	 * Returns the hole replacement class name as you can set it back at will
	 *
	 * @param $class_name             string
	 * @param $replacement_class_name string|string[]null if null, the replacement class is removed.
	 *        string value for a replacement class, string[] for a list of interfaces and traits.
	 * @return string|null old replacement class name
	 */
	public function setReplacement($class_name, $replacement_class_name)
	{
		$result = isset($this->replacements[$class_name]) ? $this->replacements[$class_name] : null;
		if (!isset($replacement_class_name)) {
			unset($this->compositions[$class_name]);
			unset($this->replacements[$class_name]);
		}
		else {
			$this->replacements[$class_name] = $replacement_class_name;
		}
		return $result;
	}

	//------------------------------------------------------------------------------- sourceClassName
	/**
	 * Gets source class name for a replacement class name
	 *
	 * @param $class_name
	 */
	public function sourceClassName($class_name)
	{
		return array_search($class_name, $this->replacements) ?: $class_name;
	}

	//----------------------------------------------------------------------------------- unserialize
	/**
	 * @param $serialized string
	 */
	public function unserialize($serialized)
	{
		list($this->compositions, $this->replacements) = unserialize($serialized);
	}

}